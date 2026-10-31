<?php
require __DIR__.'/../app/bootstrap.php';
if(PANEL_NUMBER!=='2')exit;
$catalog=json_decode(file_get_contents('/seed/wine-catalog.json'),true,64,JSON_THROW_ON_ERROR);
$site=json_decode(file_get_contents('/seed/site.json'),true,64,JSON_THROW_ON_ERROR);
$pdo=db();$pdo->beginTransaction();
try{
$pdo->exec("INSERT OR IGNORE INTO shop_countries(id,code,position)VALUES(1,'GE',0)");
$pdo->exec("INSERT OR REPLACE INTO shop_country_i18n(country_id,lang,name)VALUES(1,'ru','Грузия')");
foreach($site['blocks'][1]['tabs'] as $tab)foreach($tab['blocks'] as $b)foreach($b['tiles']??[] as $m){
 $id=(int)substr($m['slug'],-2);$q=$pdo->prepare("INSERT OR IGNORE INTO shop_monasteries(id,country_id,slug,status,position)VALUES(?,1,?,'published',?)");$q->execute([$id,$m['slug'],$id]);
 $q=$pdo->prepare("INSERT OR REPLACE INTO shop_monastery_i18n(monastery_id,lang,name,description)VALUES(?,'ru',?,?)");$q->execute([$id,$m['title']['ru'],$m['text']['ru']]);
}
foreach($catalog as $i=>$wine){
 $q=$pdo->prepare("SELECT id,photo_id FROM elements WHERE type='tile' AND slug=? LIMIT 1");$q->execute([$wine['record_id']]);$t=$q->fetch();
 $q=$pdo->prepare("INSERT OR IGNORE INTO shop_products(monastery_id,sku,slug,beverage_type,color,sweetness,vintage,alcohol_basis_points,volume_ml,price_minor,photo_id,status,available,collectible,position,category)VALUES(?,?,?,'wine',NULL,NULL,?,NULL,NULL,0,?,'published',0,0,?,'wine')");
 $year=$wine['vintage']??null;$year=is_int($year)?$year:null;
 $q->execute([$wine['monastery_id'],$wine['record_id'],$wine['record_id'],$year,$t['photo_id']??null,$i]);
 $q=$pdo->prepare('SELECT id FROM shop_products WHERE sku=?');$q->execute([$wine['record_id']]);$id=(int)$q->fetchColumn();
 $grapes=$wine['grapes']??[];$grapes=is_array($grapes)?implode(', ',$grapes):(string)$grapes;
 $q=$pdo->prepare("INSERT OR REPLACE INTO shop_product_i18n(product_id,lang,name,grape,description)VALUES(?,'ru',?,?,?)");$q->execute([$id,$wine['name'],$grapes,$wine['display_description']]);
}
$pdo->commit();echo "Imported editorial wine references; no prices or sale availability invented\n";
}catch(Throwable $e){$pdo->rollBack();throw $e;}
