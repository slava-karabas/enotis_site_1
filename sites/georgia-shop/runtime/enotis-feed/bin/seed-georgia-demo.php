<?php
require __DIR__.'/../app/bootstrap.php';
if(getenv('HENOTIS_DEMO_CHECKOUT')!=='1')throw new RuntimeException('Demo seed is only allowed in isolated demo installation');
$pdo=db();$pdo->beginTransaction();
try{
$pdo->exec("UPDATE shop_products SET status='draft',available=0");
$photo=$pdo->query("SELECT photo_id FROM elements WHERE type='tile' AND photo_id IS NOT NULL LIMIT 1")->fetchColumn();
foreach([['demo-one','ТЕСТ: Henotis — образец 1',100],['demo-two','ТЕСТ: Henotis — образец 2',200]] as $i=>$a){
 $q=$pdo->prepare("INSERT OR IGNORE INTO shop_products(monastery_id,sku,slug,price_minor,photo_id,status,available,position,category)VALUES(2,?,?,?,NULL,'published',1,?,'wine')");$q->execute([$a[0],$a[0],$a[2],$i]);
 $q=$pdo->prepare('SELECT id FROM shop_products WHERE sku=?');$q->execute([$a[0]]);$id=$q->fetchColumn();
 $q=$pdo->prepare("INSERT OR REPLACE INTO shop_product_i18n(product_id,lang,name,grape,description)VALUES(?,'ru',?,'','Демонстрационный товар. Не является вином монастыря. Цена условная. Оплата не списывается; заказ сохраняется только в отдельной тестовой базе.')");$q->execute([$id,$a[1]]);
}
$pdo->commit();echo "Demo checkout products prepared\n";
}catch(Throwable $e){$pdo->rollBack();throw $e;}
