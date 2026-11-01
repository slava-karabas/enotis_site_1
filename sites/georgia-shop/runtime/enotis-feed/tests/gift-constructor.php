<?php
declare(strict_types=1);
putenv('ENOTIS_DATA='.sys_get_temp_dir().'/gift-qa-'.bin2hex(random_bytes(6)));
require dirname(__DIR__).'/app/bootstrap.php';
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException($name);}
function rejects(callable $fn,string $name):void{try{$fn();}catch(InvalidArgumentException $e){return;}throw new RuntimeException($name);}
$pdo=db();
$in=['producer_name'=>'Учебная винодельня','country_name'=>'Грузия','country_code'=>'GE','category'=>'Красные','sku'=>'QA-01','slug'=>'qa-wine','name'=>'Саперави','price'=>'32.50','status'=>'published','available'=>'1','position'=>'0','beverage_type'=>'wine','color'=>'red','sweetness'=>'dry'];
$id=shop_save('products',$in,'ru',0);$other=shop_save('products',$in+[],'ru',$id);
check((int)$pdo->query('SELECT COUNT(*) FROM shop_monasteries')->fetchColumn()===1,'producer reused');
check(shop_one('shop_products',$id)['category']==='Красные','category persisted');
$cfg=['kind'=>'gift','ids'=>[$id],'box_minor'=>500,'card_minor'=>100,'limit'=>12];
$pdo->prepare("INSERT INTO elements(id,type,position,hidden,panel_json) VALUES(100,'text',1,0,?)")->execute([json_encode($cfg)]);
$spec=['block'=>100,'products'=>[$id],'box'=>true,'card'=>true];$a=panel_custom_gift_line($spec,2);
check($a['price']===3850&&count($a['parts'])===3&&$a['parts'][0]['quantity']===2,'server gift total and quantities');
$pdo->exec('UPDATE shop_products SET price_minor=4000');$b=panel_custom_gift_line($spec,2);check($b['price']===4600&&shop_quote([$a])!==shop_quote([$b]),'price changes invalidate quote');
$pdo->exec('UPDATE shop_products SET available=0');rejects(fn()=>panel_custom_gift_line($spec,1),'unavailable product accepted');$pdo->exec('UPDATE shop_products SET available=1');
$pdo->exec('UPDATE elements SET hidden=1');rejects(fn()=>panel_custom_gift_line($spec,1),'hidden constructor accepted');$pdo->exec('UPDATE elements SET hidden=0');
$pdo->exec("INSERT INTO sections(id,slug,position,hidden) VALUES(50,'hidden',1,1)");$pdo->exec('UPDATE elements SET section_id=50');rejects(fn()=>panel_custom_gift_line($spec,1),'hidden tab accepted');
$before=(int)$pdo->query('SELECT COUNT(*) FROM shop_monasteries')->fetchColumn();rejects(fn()=>shop_save('products',array_merge($in,['producer_name'=>'Rollback test','price'=>'-1']),'ru',0),'bad product accepted');check((int)$pdo->query('SELECT COUNT(*) FROM shop_monasteries')->fetchColumn()===$before,'producer creation rolls back');
echo "PASS: inline producer/category, no duplicate producer, gift prices and quantities, stale quote, unavailable/hidden content rejected, failed save rollback\n";
