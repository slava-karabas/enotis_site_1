<?php
declare(strict_types=1);
putenv('ENOTIS_DATA='.sys_get_temp_dir().'/photo-catalog-'.bin2hex(random_bytes(5)));putenv('ENOTIS_PHOTO_FAKE=1');
require dirname(__DIR__).'/app/bootstrap.php';
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException($name);}
$pdo=db();$src=__DIR__.'/fixtures/sample/photos/wine-alaverdi.jpg';$original=store_original($src,'jpg');
$pdo->prepare('INSERT INTO photos(id,original_path,aspect) VALUES(1,?,?)')->execute([$original,'4:5']);photo_store_paths(1);
$in=['producer_name'=>'Photo QA','country_name'=>'Грузия','country_code'=>'GE','category'=>'Вина','sku'=>'PHOTO-01','slug'=>'photo-test','name'=>'Тест фото','price'=>'32.50','status'=>'published','available'=>'1','position'=>'0','beverage_type'=>'wine','color'=>'red','sweetness'=>'dry','photo_id'=>'1'];
$id=shop_save('products',$in,'ru',0);$result=photo_process(1,'enhance','4:5',true);check($result['ok'],'processing failed');check(!(int)shop_one('photos',1)['use_processed'],'processing applied without acceptance');
shop_save('products',$in+['photo_source'=>'processed'],'ru',$id);$p=shop_one('photos',1);check((int)$p['use_processed']===1&&$p['path_m']&&is_file(ORIGINALS_DIR.'/'.$original),'acceptance, generated path or original missing');
$active=$p['processed_path'];$copy=shop_photo_process_copy(1,'studio');check($copy['ok']&&$copy['copy_id']!==1,'copy not created');check(shop_one('photos',1)['processed_path']===$active&&(int)shop_one('photos',1)['use_processed']===1,'retry replaced accepted image');
$path=$p['path_m'];shop_save('products',$in+['photo_source'=>'original'],'ru',$id);$p=shop_one('photos',1);check((int)$p['use_processed']===0&&$p['path_m']!==$path,'original restoration failed');
$pdo->exec('UPDATE photos SET processed_path=NULL');$rejected=false;try{shop_save('products',$in+['photo_source'=>'processed'],'ru',$id);}catch(InvalidArgumentException $e){$rejected=true;}check($rejected,'missing processed file accepted');
check(hash_file('sha256',$src)===hash_file('sha256',ORIGINALS_DIR.'/'.$original),'original changed');
echo "PASS: catalogue photo processing, explicit acceptance on save, original retained, source restoration, missing result denied\n";
