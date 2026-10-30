<?php
declare(strict_types=1);

function integration_config(): array {
    return ['email'=>(string)setting('integration_email',''),'webhook'=>(string)setting('integration_webhook',''),
        'read'=>setting('integration_api_read','0')==='1','write'=>setting('integration_api_write','0')==='1'];
}
/** Reject local/private targets. Pin the verified address during delivery, never follow redirects. */
function integration_target(string $url): array {
    $p=parse_url($url);
    if(!$p||($p['scheme']??'')!=='https'||!empty($p['user'])||!empty($p['pass'])||isset($p['fragment'])||isset($p['port'])&&$p['port']!==443)throw new InvalidArgumentException('Нужен публичный HTTPS-адрес без пароля и нестандартного порта.');
    $host=$p['host']??'';
    if(!$host||filter_var($host,FILTER_VALIDATE_IP))throw new InvalidArgumentException('Нужен публичный домен получателя.');
    $addresses=gethostbynamel($host)?:[];
    if(!$addresses)throw new InvalidArgumentException('Домен получателя не найден.');
    foreach($addresses as $ip)if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))throw new InvalidArgumentException('Локальные адреса получателя запрещены.');
    return [$host,$addresses[0]];
}
/** Called INSIDE the business transaction, before commit. No network IO here. */
function integration_enqueue(string $type, int $objectId, array $payload): void {
    $cfg=integration_config();$event=bin2hex(random_bytes(16));
    $body=json_encode(['version'=>1,'event_id'=>$event,'event'=>$type,'object_id'=>$objectId,'occurred_at'=>gmdate('c'),'data'=>$payload],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    foreach(['email','webhook'] as $channel)if($cfg[$channel]!=='')db()->prepare('INSERT INTO integration_outbox(id,event_type,object_id,payload,channel) VALUES(?,?,?,?,?)')->execute([$event.'-'.$channel,$type,$objectId,$body,$channel]);
}
function integration_worker(int $limit=20): int {
    $done=0;
    for($i=0;$i<$limit;$i++) {
        $now=time();$pdo=db();$pdo->beginTransaction();
        $st=$pdo->prepare("SELECT * FROM integration_outbox WHERE state='pending' AND next_at<=? AND lease_until<=? ORDER BY created_at,id LIMIT 1");$st->execute([$now,$now]);$r=$st->fetch();
        if(!$r){$pdo->commit();break;}
        $claim=$pdo->prepare("UPDATE integration_outbox SET lease_until=?,attempts=attempts+1 WHERE id=? AND lease_until<=? AND state='pending'");$claim->execute([$now+180,$r['id'],$now]);$pdo->commit();
        if(!$claim->rowCount())continue;
        $error=null;
        try {
            $cfg=integration_config();
            if($r['channel']==='email') {
                if(!$cfg['email'])throw new RuntimeException('Канал отключён');
                $error=send_mail($cfg['email'],'Энотис: новая заявка №'.$r['object_id'],"На сайте сохранена заявка №".$r['object_id'].".\nОткройте панель управления для просмотра и обработки.");
                if($error)throw new RuntimeException('Почта не приняла отправление');
            } else {
                if(!$cfg['webhook'])throw new RuntimeException('Канал отключён');
                if(!function_exists('curl_init'))throw new RuntimeException('Нужен PHP cURL');
                [$host,$ip]=integration_target($cfg['webhook']);$secret=(string)setting('integration_webhook_secret','');
                if(!$secret)throw new RuntimeException('Нет ключа подписи');
                $ch=curl_init($cfg['webhook']);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$r['payload'],CURLOPT_RETURNTRANSFER=>false,
                    CURLOPT_WRITEFUNCTION=>static fn($ch,$data)=>strlen($data),CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,
                    CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_RESOLVE=>[$host.':443:'.$ip],CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
                    CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-Enotis-Event: '.$r['id'],'X-Enotis-Signature: sha256='.hash_hmac('sha256',$r['payload'],$secret)]]);
                $ok=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
                if($ok===false||$code<200||$code>=300)throw new RuntimeException('Получатель не подтвердил приём');
            }
        } catch(Throwable $e){$error='Доставка не подтверждена. Проверьте подключение.';}
        $attempt=(int)$r['attempts']+1;
        $pdo->prepare('UPDATE integration_outbox SET state=?,lease_until=0,next_at=?,sent_at=?,last_error=? WHERE id=?')->execute([$error?($attempt>=5?'failed':'pending'):'sent',$now+min(3600,60*(2**min($attempt,6))),$error?null:gmdate('Y-m-d H:i:s'),$error,$r['id']]);$done++;
    }
    return $done;
}
/** Machine access is separate from admin sessions, limited to order reading/status. */
function integration_machine_route(string $path): bool {
    if(!str_starts_with($path,'/automation/v1/'))return false;
    header('Cache-Control: no-store');header('X-Robots-Tag: noindex');
    $auth=$_SERVER['HTTP_AUTHORIZATION']??'';$token=str_starts_with($auth,'Bearer ')?substr($auth,7):'';$hash=(string)setting('integration_token_hash','');
    if(!$hash||!$token||!hash_equals($hash,hash('sha256',$token))){admin_json(['error'=>'unauthorized'],401);return true;}
    $https=($_SERVER['HTTPS']??'')==='on';$local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);
    if(!$https&&!$local){admin_json(['error'=>'https_required'],403);return true;}
    if(!function_exists('shop_query')){admin_json(['error'=>'orders_unavailable'],404);return true;}
    $cfg=integration_config();$method=$_SERVER['REQUEST_METHOD']??'GET';
    if($method==='GET'&&$path==='/automation/v1/orders'&&$cfg['read']) {
        $after=max(0,(int)(is_scalar($_GET['after']??0)?($_GET['after']??0):0));$rows=shop_query('SELECT id,status,total_minor,customer_name,customer_contact,greeting,created_at FROM shop_orders WHERE id>? ORDER BY id LIMIT 50',[$after]);admin_json(['orders'=>$rows]);return true;
    }
    if($method==='POST'&&preg_match('~^/automation/v1/orders/([1-9][0-9]*)/status$~D',$path,$m)&&$cfg['write']) {
        $in=admin_input();$status=$in['status']??'';
        if(!in_array($status,['confirmed','completed','cancelled'],true)){admin_json(['error'=>'invalid_status'],422);return true;}
        $st=db()->prepare("UPDATE shop_orders SET status=? WHERE id=? AND status IN ('new','confirmed')");$st->execute([$status,(int)$m[1]]);admin_json(['ok'=>(bool)$st->rowCount()],$st->rowCount()?200:409);return true;
    }
    admin_json(['error'=>'forbidden'],403);return true;
}
