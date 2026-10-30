<?php
declare(strict_types=1);
$dir=sys_get_temp_dir().'/enotis-integration-'.bin2hex(random_bytes(8));mkdir($dir,0700,true);putenv('ENOTIS_DATA='.$dir);putenv('ENOTIS_MAIL_FILE='.$dir.'/mail.log');
require dirname(__DIR__).'/app/bootstrap.php';
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$pdo=db();set_setting('integration_email','owner@example.invalid');
$pdo->beginTransaction();integration_enqueue('order.created',42,['total_minor'=>3200]);$pdo->rollBack();check((int)$pdo->query('SELECT COUNT(*) FROM integration_outbox')->fetchColumn()===0,'event must rollback with order');
$pdo->beginTransaction();integration_enqueue('order.created',42,['total_minor'=>3200]);$pdo->commit();
check(integration_worker()===1,'worker did not consume');check(integration_worker()===0,'sent event was repeated');
check($pdo->query('SELECT state FROM integration_outbox')->fetchColumn()==='sent','email state');check(is_file($dir.'/mail.log'),'mail sink');
set_setting('integration_email','');set_setting('integration_webhook','https://localhost/receiver');set_setting('integration_webhook_secret','test-only');
$pdo->beginTransaction();integration_enqueue('order.created',43,[]);$pdo->commit();integration_worker();$row=$pdo->query("SELECT * FROM integration_outbox WHERE channel='webhook'")->fetch();check($row['state']==='pending'&&(int)$row['attempts']===1,'retry state');
check(integration_worker()===0,'backoff ignored');
for($i=0;$i<4;$i++){$pdo->exec("UPDATE integration_outbox SET next_at=0 WHERE state='pending'");integration_worker();}
check($pdo->query("SELECT state FROM integration_outbox WHERE channel='webhook'")->fetchColumn()==='failed','retry limit');
foreach(['http://example.com','https://127.0.0.1','https://localhost','https://user:pass@example.com','https://example.com:8443'] as $url){$rejected=false;try{integration_target($url);}catch(InvalidArgumentException $e){$rejected=true;}check($rejected,'unsafe target accepted');}
check(panel_extension(['kind'=>'filters','labels'=>['Регион','Винодельня'],'all'=>'Все регионы'])['labels'][1]==='Винодельня','filter labels');
$bad=false;try{panel_extension(['kind'=>'filters','labels'=>[[]]]);}catch(InvalidArgumentException $e){$bad=true;}check($bad,'nested filter accepted');
if(PANEL_NUMBER==='1'){$tree=site_export();check(!payment_data_present($tree),'payment data exposed');check(panel_extension(['kind'=>'values','values'=>['Кахетия']])['values'][0]==='Кахетия','tile value');}
if(PANEL_NUMBER==='1.2'){$tables=$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'pay_method%'")->fetchAll();check(count($tables)===2,'payment tables not restored');$pdo->exec("INSERT INTO elements(type,position,hidden) VALUES('pay',1,1)");}
// Machine API denies missing tokens, insecure transport and disabled scopes.
set_setting('integration_token_hash',hash('sha256','disposable-test-token'));
function machine(string $path):array{ob_start();integration_machine_route($path);$body=ob_get_clean();return [http_response_code(),json_decode($body,true)];}
$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REMOTE_ADDR']='127.0.0.1';
check(machine('/automation/v1/orders')[0]===401,'API without token');
$_SERVER['HTTP_AUTHORIZATION']='Bearer disposable-test-token';$_SERVER['REMOTE_ADDR']='192.0.2.1';
check(machine('/automation/v1/orders')[0]===403,'API over insecure transport');
$_SERVER['REMOTE_ADDR']='127.0.0.1';
if(PANEL_NUMBER==='2'){
 set_setting('integration_api_read','0');check(machine('/automation/v1/orders')[0]===403,'read scope disabled');
 set_setting('integration_api_read','1');$_GET['after']=[];check(machine('/automation/v1/orders')[0]===200,'safe pagination and read permission');
 $_SERVER['REQUEST_METHOD']='POST';set_setting('integration_api_write','0');check(machine('/automation/v1/orders/1/status')[0]===403,'write scope disabled');
}
echo 'PASS '.PANEL_NUMBER.': migrations, transactional outbox, email sink, retries/backoff, private targets, filter validation, product capabilities'.PHP_EOL;
