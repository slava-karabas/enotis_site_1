<?php
declare(strict_types=1);

function shop_public_products(string $lang): array
{
    $mon=[];foreach(shop_choices('monasteries',$lang) as $m){if($m['status']==='published')$mon[$m['id']]=$m;}
    $out=[];foreach(shop_choices('products',$lang) as $p){if($p['status']==='published'&&isset($mon[$p['monastery_id']])){$p['available']=!empty($p['available'])&&(int)$p['price_minor']>0;$p['monastery_name']=$mon[$p['monastery_id']]['name'];$p['country_id']=$mon[$p['monastery_id']]['country_id'];$out[$p['id']]=$p;}}
    return $out;
}
function shop_public_gifts(string $lang): array
{
    $products=shop_public_products($lang);$out=[];
    foreach(shop_choices('gifts',$lang) as $g){
        if($g['status']!=='published')continue;
        $items=shop_query('SELECT product_id,quantity FROM shop_gift_products WHERE gift_id=?',[$g['id']]);$ok=(bool)$items;
        foreach($items as $i){if(!isset($products[$i['product_id']])||!$products[$i['product_id']]['available'])$ok=false;}
        foreach(shop_query('SELECT o.active FROM shop_gift_extras e JOIN shop_gift_options o ON o.id=e.option_id WHERE e.gift_id=?',[$g['id']]) as $o){if(!$o['active'])$ok=false;}
        if($g['packaging_id']&&!shop_one('shop_gift_options',(int)$g['packaging_id'])['active'])$ok=false;
        if($ok){$g['price_minor']=shop_gift_total((int)$g['id']);$out[$g['id']]=$g;}
    }
    return $out;
}
function shop_image(?int $id): ?string
{
    if(!$id)return null;$p=shop_one('photos',$id);return $p?'/uploads/'.photo_paths($p)['m']:null;
}
function shop_public_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    ini_set('session.use_strict_mode','1');session_name('enotis_shop');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>($_SERVER['HTTPS']??'')==='on','httponly'=>true,'samesite'=>'Lax']);session_start();
    $_SESSION['shop_csrf']??=bin2hex(random_bytes(24));$_SESSION['shop_cart']??=[];$_SESSION['shop_checkout']??=bin2hex(random_bytes(24));
}
function shop_link(string $path): string {return $path.(current_lang()===default_lang()?'':(str_contains($path,'?')?'&':'?').'lang='.current_lang());}
function shop_store_redirect(string $path): void {header('Location: '.shop_link($path),true,303);}
function shop_cart_lines(): array
{
    $products=shop_public_products(current_lang());$gifts=shop_public_gifts(current_lang());$lines=[];
    foreach($_SESSION['shop_cart'] as $key=>$qty){
        if(str_starts_with((string)$key,'c:')){if(!isset($_SESSION['shop_custom'][$key]))throw new InvalidArgumentException('Набор недоступен');$lines[]=panel_custom_gift_line($_SESSION['shop_custom'][$key],shop_integer((string)$qty,'Количество',1,99));continue;}
        if(!preg_match('/^([pg]):([1-9][0-9]*)$/D',(string)$key,$m))continue;
        $row=($m[1]==='p'?$products:$gifts)[(int)$m[2]]??null;
        if(!$row||($m[1]==='p'&&!$row['available']))throw new InvalidArgumentException('Одна из позиций больше недоступна. Удалите её из корзины.');
        $qty=shop_integer((string)$qty,'Количество',1,99);$parts=[];
        if($m[1]==='p'){$parts[]=['product_id'=>$row['id'],'option_id'=>null,'kind'=>'product','sku'=>$row['sku'],'name'=>$row['name'],'quantity'=>$qty,'price'=>(int)$row['price_minor']];}
        else{
            foreach(shop_query('SELECT product_id,quantity FROM shop_gift_products WHERE gift_id=?',[$row['id']]) as $i){$p=$products[$i['product_id']];$parts[]=['product_id'=>$p['id'],'option_id'=>null,'kind'=>'product','sku'=>$p['sku'],'name'=>$row['name'].' / '.$p['name'],'quantity'=>$qty*(int)$i['quantity'],'price'=>(int)$p['price_minor']];}
            $opts=shop_query('SELECT o.* FROM shop_gift_extras e JOIN shop_gift_options o ON o.id=e.option_id WHERE e.gift_id=?',[$row['id']]);
            if($row['packaging_id'])$opts[]=shop_one('shop_gift_options',(int)$row['packaging_id']);
            $names=array_column(shop_choices('options',current_lang()),'name','id');
            foreach($opts as $o){$parts[]=['product_id'=>null,'option_id'=>$o['id'],'kind'=>$o['kind'],'sku'=>'','name'=>$row['name'].' / '.($names[$o['id']]??'Оформление'),'quantity'=>$qty,'price'=>(int)$o['price_minor']];}
        }
        $lines[]=['key'=>$key,'name'=>$row['name'],'quantity'=>$qty,'price'=>(int)$row['price_minor'],'parts'=>$parts];
    }
    return $lines;
}
function shop_quote(array $lines): string {return hash('sha256',json_encode(['currency'=>shop_currency(),'lines'=>$lines],JSON_UNESCAPED_UNICODE));}
function shop_cart_total(array $lines): int {return array_sum(array_map(fn($l)=>$l['price']*$l['quantity'],$lines));}
function shop_checkout(array $in): string
{
    $key=shop_scalar($in,'checkout_key');
    if(isset($_SESSION['shop_last_checkout'])&&hash_equals($_SESSION['shop_last_checkout'],$key))return $_SESSION['shop_receipt'];
    if(!hash_equals($_SESSION['shop_checkout'],$key))throw new InvalidArgumentException('Обновите страницу корзины.');
    $existing=shop_query('SELECT public_token FROM shop_orders WHERE idempotency_key=?',[$key])[0]??null;
    if($existing){$_SESSION['shop_cart']=[];$_SESSION['shop_last_checkout']=$key;$_SESSION['shop_receipt']=$existing['public_token'];$_SESSION['shop_checkout']=bin2hex(random_bytes(24));return $existing['public_token'];}
    $name=shop_scalar($in,'customer_name');$contact=shop_scalar($in,'customer_contact');$comment=shop_scalar($in,'comment');
    if($name===''||mb_strlen($name)>120||mb_strlen($contact)>160||mb_strlen($comment)>1000)throw new InvalidArgumentException('Проверьте имя и контакт. Комментарий — до 1000 знаков.');
    $phone=preg_match('/^\+?[0-9 ()\-.]{7,30}$/D',$contact)&&strlen(preg_replace('/\D/','',$contact))>=7;
    if(!filter_var($contact,FILTER_VALIDATE_EMAIL)&&!$phone)throw new InvalidArgumentException('Укажите действительный email или телефон.');
    if(shop_scalar($in,'consent')!=='1')throw new InvalidArgumentException('Подтвердите согласие на связь по заказу.');
    if(shop_scalar($in,'website')!=='')throw new InvalidArgumentException('Не удалось отправить заявку.');
    $pdo=db();$pdo->beginTransaction();
    try{
        $secret=setting('shop_abuse_key');if(!$secret){$secret=bin2hex(random_bytes(32));set_setting('shop_abuse_key',$secret);}
        $ip=hash_hmac('sha256',$_SERVER['REMOTE_ADDR']??'unknown',$secret);$now=time();
        $pdo->prepare('DELETE FROM shop_checkout_limits WHERE started < ?')->execute([$now-600]);
        $pdo->prepare('INSERT INTO shop_checkout_limits(token,started,attempts) VALUES(?,?,1) ON CONFLICT(token) DO UPDATE SET attempts=attempts+1')->execute([$ip,$now]);
        $attempts=(int)shop_query('SELECT attempts FROM shop_checkout_limits WHERE token=?',[$ip])[0]['attempts'];
        if($attempts>5){$pdo->commit();throw new InvalidArgumentException('Слишком много заявок. Попробуйте через 10 минут.');}
        $lines=shop_cart_lines();if(!$lines)throw new InvalidArgumentException('Корзина пуста.');
        if(!hash_equals(shop_quote($lines),shop_scalar($in,'quote')))throw new InvalidArgumentException('Цена или состав изменились. Проверьте обновлённую сумму и отправьте заявку ещё раз.');
        $total=shop_cart_total($lines);if($total>999999999)throw new InvalidArgumentException('Для такого заказа свяжитесь с нами напрямую.');
        $token=bin2hex(random_bytes(24));
        $pdo->prepare('INSERT INTO shop_orders(public_token,idempotency_key,total_minor,customer_name,customer_contact,greeting,currency,consent_at) VALUES(?,?,?,?,?,?,?,CURRENT_TIMESTAMP)')->execute([$token,$key,$total,$name,$contact,$comment,shop_currency()]);
        $id=(int)$pdo->lastInsertId();$st=$pdo->prepare('INSERT INTO shop_order_items(order_id,product_id,option_id,kind,group_key,sku_snapshot,name_snapshot,quantity,unit_price_minor) VALUES(?,?,?,?,?,?,?,?,?)');
        foreach($lines as $l)foreach($l['parts'] as $p){$st->execute([$id,$p['product_id'],$p['option_id'],$p['kind'],$l['key'],$p['sku'],$p['name'],$p['quantity'],$p['price']]);}
        integration_enqueue('order.created',$id,['name'=>$name,'contact'=>$contact,'comment'=>$comment,'total_minor'=>$total,'currency'=>shop_currency(),'items'=>$lines]);
        $pdo->commit();$_SESSION['shop_cart']=[];$_SESSION['shop_last_checkout']=$key;$_SESSION['shop_receipt']=$token;$_SESSION['shop_checkout']=bin2hex(random_bytes(24));return $token;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function shop_store_route(string $path): bool
{
    if(!($path==='/'||$path==='/catalog'||$path==='/cart'||$path==='/gifts'||preg_match('~^/(wine|monastery|order|gift|doc)/[^/]+$~',$path)))return false;
    if(preg_match('~^/monastery/(monastery-[0-9]+)$~',$path,$routeMatch)) {
        $q=db()->prepare("SELECT id FROM elements WHERE type='tile' AND slug=? AND hidden=0 LIMIT 1");$q->execute([$routeMatch[1]]);
        if($tileId=$q->fetchColumn()){redirect(tile_url(node((int)$tileId)),302);return true;}
    }
    if(!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)){http_response_code(405);header('Allow: GET, POST');return true;}
    shop_public_session();header('Cache-Control: no-store');header('X-Frame-Options: SAMEORIGIN');
    $lang=$_GET['lang']??$_POST['lang']??default_lang();$active=array_column(site_languages(),'code');current_lang(is_string($lang)&&in_array($lang,$active,true)?$lang:default_lang());
    $view='catalogue';$error=null;$detail=null;$receipt=null;
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if($path!=='/cart'){http_response_code(405);return true;}
        if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['shop_csrf'],$_POST['csrf'])){http_response_code(403);echo 'Обновите страницу и повторите действие.';return true;}
        try{
            $action=shop_scalar($_POST,'action');
            if($action==='checkout'){$token=shop_checkout($_POST);shop_store_redirect('/order/'.$token);return true;}
            if($action==='custom_gift') {
                $id=shop_integer(shop_scalar($_POST,'block_id'),'Конструктор',1,2147483647);$raw=$_POST['products']??[];
                if(!is_array($raw)||count($raw)>30)throw new InvalidArgumentException('Выберите не более 30 товаров');
                $ids=[];foreach($raw as $v){if(!is_scalar($v))throw new InvalidArgumentException('Неверный товар');$ids[]=shop_integer((string)$v,'Товар',1,2147483647);}$ids=array_values(array_unique($ids));sort($ids);
                $spec=['block'=>$id,'products'=>$ids,'box'=>shop_scalar($_POST,'box')==='1','card'=>shop_scalar($_POST,'card')==='1'];$line=panel_custom_gift_line($spec,1);$key=$line['key'];
                if(count($_SESSION['shop_cart'])>=20&&!isset($_SESSION['shop_cart'][$key]))throw new InvalidArgumentException('В корзине не более 20 позиций');
                $_SESSION['shop_custom'][$key]=$spec;$_SESSION['shop_cart'][$key]=min(99,($_SESSION['shop_cart'][$key]??0)+1);shop_store_redirect('/cart');return true;
            }
            $key=shop_scalar($_POST,'key');if(!preg_match('/^(?:[pg]:[1-9][0-9]{0,9}|c:[a-f0-9]{64})$/D',$key))throw new InvalidArgumentException('Позиция не найдена.');
            if($action==='remove'){unset($_SESSION['shop_cart'][$key]);}
            elseif($action==='add'||$action==='update'){
                $qty=shop_integer(shop_scalar($_POST,'quantity'),'Количество',1,99);
                $old=$_SESSION['shop_cart'];$_SESSION['shop_cart'][$key]=$action==='add'?min(99,($old[$key]??0)+$qty):$qty;
                try{if(count($_SESSION['shop_cart'])>20)throw new InvalidArgumentException('В корзине может быть до 20 разных позиций.');shop_cart_lines();}catch(Throwable $e){$_SESSION['shop_cart']=$old;throw $e;}
            }else throw new InvalidArgumentException('Неизвестное действие.');
            shop_store_redirect('/cart');return true;
        }catch(InvalidArgumentException $e){http_response_code(422);$error=$e->getMessage();}
         catch(Throwable $e){http_response_code(503);error_log('[checkout] '.$e->getMessage());$error='Не удалось сохранить заявку. Попробуйте ещё раз.';}
    }
    $products=shop_public_products(current_lang());$gifts=shop_public_gifts(current_lang());$monasteries=array_filter(shop_choices('monasteries',current_lang()),fn($m)=>$m['status']==='published');
    if($path==='/cart')$view='cart';elseif($path==='/gifts')$view='gifts';
    elseif(preg_match('~^/doc/(offer|privacy|cookies)$~',$path,$m)){ $detail=doc($m[1]);$view=$detail?'doc':'missing';if(!$detail)http_response_code(404); }
    elseif(preg_match('~^/(wine|monastery|gift)/([^/]+)$~',$path,$m)){
        $pool=$m[1]==='wine'?$products:($m[1]==='gift'?$gifts:$monasteries);
        foreach($pool as $r){if($r['slug']===$m[2]){$detail=$r;break;}}
        if(!$detail){http_response_code(404);$view='missing';}else{$view=$m[1];if($view==='monastery')$products=array_filter($products,fn($p)=>$p['monastery_id']===$detail['id']);}
    }elseif(preg_match('~^/order/([a-f0-9]{48})$~',$path,$m)){
        // The receipt reveals no contact details and is bound to this browser session.
        if(!hash_equals($_SESSION['shop_receipt']??'',$m[1])){http_response_code(404);$view='missing';}
        else{$receipt=shop_query('SELECT id,total_minor FROM shop_orders WHERE public_token=?',[$m[1]])[0]??null;$view=$receipt?'receipt':'missing';if(!$receipt)http_response_code(404);}
    }elseif(str_starts_with($path,'/order/')){http_response_code(404);$view='missing';}
    $filters=[];foreach(['q','country','monastery','category','color','sweetness','sort'] as $k)$filters[$k]=is_string($_GET[$k]??null)?mb_substr($_GET[$k],0,120):'';
    if(in_array($view,['catalogue','monastery'],true)){
        $products=array_filter($products,function($p)use($filters){
            return ($filters['q']===''||mb_stripos($p['name'].' '.$p['sku'],$filters['q'])!==false)
            &&($filters['country']===''||(string)$p['country_id']===$filters['country'])&&($filters['monastery']===''||(string)$p['monastery_id']===$filters['monastery'])
            &&($filters['category']===''||$p['category']===$filters['category'])&&($filters['color']===''||$p['color']===$filters['color'])&&($filters['sweetness']===''||$p['sweetness']===$filters['sweetness']);});
        if(in_array($filters['sort'],['price_up','price_down'],true))usort($products,fn($a,$b)=>($a['price_minor']<=>$b['price_minor'])*($filters['sort']==='price_down'?-1:1));
    }
    $lines=[];$cartError=null;if($view==='cart'){try{$lines=shop_cart_lines();}catch(InvalidArgumentException $e){$cartError=$e->getMessage();}}
    if($path==='/'&&home_blocks()){ $page=resolve_page([]);if($page){render($page['view'],$page);return true;} }
    $catalogOnly=$path==='/catalog';
    echo view('shop/store',['catalogOnly'=>$catalogOnly]+compact('view','error','detail','receipt','products','gifts','monasteries','filters','lines','cartError')+['csrf'=>$_SESSION['shop_csrf']]);return true;
}

function shop_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach(site_languages() as $l){current_lang($l['code']);$paths=['/','/gifts'];foreach(shop_public_products($l['code']) as $p)$paths[]='/wine/'.$p['slug'];foreach(shop_public_gifts($l['code']) as $g)$paths[]='/gift/'.$g['slug'];foreach(shop_choices('monasteries',$l['code']) as $m)if($m['status']==='published')$paths[]='/monastery/'.$m['slug'];foreach(array_keys(visible_docs()) as $k)$paths[]='/doc/'.$k;foreach($paths as $p)echo '<url><loc>'.htmlspecialchars(abs_url(shop_link($p)),ENT_XML1|ENT_QUOTES,'UTF-8').'</loc></url>'; }echo '</urlset>';
}
