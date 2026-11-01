<?php
$dir=sys_get_temp_dir().'/henotis-currency-'.bin2hex(random_bytes(5));mkdir($dir,0700,true);putenv('ENOTIS_DATA='.$dir);
require __DIR__.'/../app/bootstrap.php';
$p=new PDO('sqlite:'.DB_FILE,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$p->exec('PRAGMA foreign_keys=ON');
foreach(glob(APP_DIR.'/migrations/*.sql') as $f)if((int)basename($f)<=6)$p->exec(file_get_contents($f));
$p->exec('CREATE TABLE schema_version(version INTEGER NOT NULL,applied_at TEXT DEFAULT CURRENT_TIMESTAMP)');$p->exec('INSERT INTO schema_version(version)VALUES(6)');
$p->exec("INSERT INTO shop_countries(id,code)VALUES(100,'GE');INSERT INTO shop_monasteries(id,country_id,slug)VALUES(100,100,'currency-fixture');INSERT INTO shop_products(id,monastery_id,sku,slug,price_minor)VALUES(100,100,'CURRENCY','currency-fixture',2000);INSERT INTO shop_orders(id,public_token,idempotency_key,total_minor,customer_name,customer_contact)VALUES(100,'currency-token','currency-key',2000,'Fixture','qa@example.invalid');INSERT INTO shop_order_items(id,order_id,product_id,kind,name_snapshot,quantity,unit_price_minor)VALUES(100,100,100,'product','Original',1,2000)");
$p->exec("INSERT OR IGNORE INTO languages(code,name,position,is_default,is_active)VALUES('ru','Русский',0,1,1);INSERT INTO shop_product_i18n(product_id,lang,name,grape,description)VALUES(100,'ru','Original product','Original grape','Original description')");
$p=null;$pdo=db();
function expect_currency($ok,$message){if(!$ok)throw new RuntimeException($message);}
expect_currency((int)$pdo->query('PRAGMA foreign_keys')->fetchColumn()===1,'Foreign keys restored');
expect_currency(!$pdo->query('PRAGMA foreign_key_check')->fetch(),'References retained');
expect_currency((int)$pdo->query('SELECT product_id FROM shop_order_items WHERE id=100')->fetchColumn()===100,'Order item product retained');
expect_currency($pdo->query('SELECT name FROM shop_product_i18n WHERE product_id=100')->fetchColumn()==='Original product','Translations retained');
expect_currency($pdo->query('SELECT currency FROM shop_orders WHERE id=100')->fetchColumn()==='EUR','Old order currency retained');
$lastQuote=null;
foreach(['EUR'=>'€','GEL'=>'₾','RUB'=>'₽','USD'=>'$'] as $currency=>$symbol){
 set_setting('currency',$currency);$quote=shop_quote([['price'=>2000]]);expect_currency(str_contains(shop_money(2000),$symbol),'Currency formatting');expect_currency($quote!==$lastQuote,'Currency bound to quote');$lastQuote=$quote;
 $pdo->prepare('UPDATE shop_products SET currency=? WHERE id=100')->execute([$currency]);
 $pdo->prepare('INSERT INTO shop_orders(public_token,idempotency_key,total_minor,customer_name,customer_contact,currency)VALUES(?,?,2000,?,?,?)')->execute(['currency-'.$currency,'key-'.$currency,'Fixture','qa@example.invalid',$currency]);
}
set_setting('currency','GEL');expect_currency(str_contains(shop_money(2000,'EUR'),'€'),'Receipt snapshot formats in original currency');
try{$pdo->exec("UPDATE shop_products SET currency='BAD' WHERE id=100");throw new RuntimeException('Invalid currency accepted');}catch(PDOException $e){}
echo "PASS: v6 currency migration preserves rows/relations, foreign keys restored, four currencies, quote currency, historical order formatting and invalid currency rejection\n";
