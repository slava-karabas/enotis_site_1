<?php
require __DIR__.'/../app/bootstrap.php';
if(!in_array(getenv('HENOTIS_PROFILE'),['shop','showcase'],true))throw new RuntimeException('Only isolated Georgia review profiles');
$pdo=db();$dir=DATA_DIR.'/backups';if(!is_dir($dir))mkdir($dir,0775,true);$backup=$dir.'/before-final-20261031-'.bin2hex(random_bytes(4)).'.sqlite';$pdo->exec('VACUUM INTO '.$pdo->quote($backup));
$rows=json_decode(file_get_contents('/seed/wine-catalog.json'),true,64,JSON_THROW_ON_ERROR);$pdo->beginTransaction();
try{
set_setting('currency','GEL');set_setting('show_prices','1');$pdo->exec("UPDATE sections SET currency='GEL',show_prices=1");
foreach($rows as $w){
 $q=$pdo->prepare("UPDATE elements SET price=20,wine_color=?,wine_sweet=? WHERE type='tile' AND slug=?");$q->execute([$w['display_color']??null,$w['display_sweetness']??null,$w['record_id']]);
 if(PANEL_NUMBER==='2'){$q=$pdo->prepare('UPDATE shop_products SET price_minor=2000,available=1,color=?,sweetness=? WHERE sku=?');$q->execute([$w['display_color']??null,$w['display_sweetness']??null,$w['record_id']]);}
}
$pdo->commit();echo 'Review prices GEL 20 set for '.count($rows).' references; backup retained; placeholder rows untouched'.PHP_EOL;
}catch(Throwable $e){$pdo->rollBack();throw $e;}
