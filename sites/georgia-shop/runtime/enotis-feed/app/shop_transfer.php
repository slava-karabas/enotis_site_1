<?php
declare(strict_types=1);
// Only these public tables/settings cross installations. Orders and credentials stay local.
const SHOP_CONTENT_TABLES=['languages','settings','settings_i18n','photos','operators','sections','section_i18n','domains','domain_i18n','docs','doc_i18n','elements','element_i18n','pay_methods','pay_method_i18n','social_links','shop_countries','shop_country_i18n','shop_monasteries','shop_monastery_i18n','shop_products','shop_product_i18n','shop_collections','shop_collection_i18n','shop_collection_products','shop_gift_options','shop_gift_option_i18n','shop_gifts','shop_gift_i18n','shop_gift_products','shop_gift_extras'];
const SHOP_PUBLIC_SETTINGS=['shop_catalog_categories','shop_catalog_filters','header_show','header_burger','footer_show','cookie_notice','currency','req_format','maps','show_prices','logo_with_title','operator_id','logo_path','favicon_path','og_image_path','footer_email','footer_phone','footer_address','footer_telegram'];
function shop_workdir(): string {$d=DATA_DIR.'/transfer/'.bin2hex(random_bytes(12));if(!mkdir($d,0700,true))throw new RuntimeException('Нет места для архива.');return $d;}
function shop_remove_dir(string $d): void {if(!is_dir($d))return;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $f){$f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname());}rmdir($d);}
function shop_code_files(): array
{
    $out=[];foreach(['app','public','bin'] as $dir)foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT_DIR.'/'.$dir,FilesystemIterator::SKIP_DOTS)) as $f){$p=substr($f->getPathname(),strlen(ROOT_DIR)+1);if($f->isFile()&&!$f->isLink()&&!str_starts_with($p,'public/uploads/')&&!str_contains($p,'/.'))$out[$p]=$f->getPathname();}
    foreach(['.htaccess','public/.htaccess','data/.htaccess'] as $p)$out[$p]=ROOT_DIR.'/'.$p;
    return $out;
}
function shop_content(PDO $pdo): array
{
    $out=[];foreach(SHOP_CONTENT_TABLES as $t){$out[$t]=$pdo->query('SELECT * FROM '.$t)->fetchAll(PDO::FETCH_ASSOC);}
    $out['settings']=array_values(array_filter($out['settings'],fn($s)=>in_array($s['key'],SHOP_PUBLIC_SETTINGS,true)));
    $out['settings_i18n']=array_values(array_filter($out['settings_i18n'],fn($s)=>in_array($s['key'],['site_title','seo_title','seo_description'],true)));
    return $out;
}
function shop_package(bool $backup=false): string
{
    if(!class_exists('ZipArchive'))throw new RuntimeException('На хостинге нужно расширение PHP ZIP.');
    $d=shop_workdir();$pdo=db();$snapshot=$d.'/site.sqlite';
    try{
        $pdo->exec('VACUUM INTO '.$pdo->quote($snapshot));
        $copy=new PDO('sqlite:'.$snapshot,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$copy->exec('PRAGMA foreign_keys=ON');
        $content=shop_content($copy);
        if(!$backup){$copy->beginTransaction();foreach(['integration_outbox','shop_order_items','shop_orders','shop_checkout_limits','admins'] as $t)$copy->exec('DELETE FROM '.$t);
            $qs=implode(',',array_fill(0,count(SHOP_PUBLIC_SETTINGS),'?'));$copy->prepare('DELETE FROM settings WHERE key NOT IN ('.$qs.')')->execute(SHOP_PUBLIC_SETTINGS);
            $copy->exec("DELETE FROM settings_i18n WHERE key NOT IN ('site_title','seo_title','seo_description')");$copy->commit();$copy->exec('VACUUM');}
        $copy=null;$files=shop_code_files();$files['data/site.sqlite']=$snapshot;
        foreach($content['photos'] as $p)foreach(['original_path','processed_path'] as $k){if(!$p[$k])continue;$n=$p[$k];if(basename($n)!==$n||!preg_match('/\.(jpg|jpeg|png|webp)$/iD',$n)||!is_file(ORIGINALS_DIR.'/'.$n))throw new RuntimeException('Не найден исходник фотографии '.$p['id']);$files['data/uploads/originals/'.$n]=ORIGINALS_DIR.'/'.$n;}
        foreach(['brand','qr'] as $sub)foreach(glob(PUBLIC_UPLOADS.'/'.$sub.'/*')?:[] as $f){if(is_file($f)&&preg_match('/\.(png|jpe?g|webp|svg|ico)$/iD',$f))$files['public/uploads/'.$sub.'/'.basename($f)]=$f;}
        $zip=new ZipArchive();$path=$d.'/enotis-shop.zip';if($zip->open($path,ZipArchive::CREATE)!==true)throw new RuntimeException('Не удалось создать ZIP.');
        $hashes=[];foreach($files as $name=>$file){$hashes[$name]=hash_file('sha256',$file);$zip->addFile($file,$name);}
        $json=json_encode($content,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$zip->addFromString('content.json',$json);$hashes['content.json']=hash('sha256',$json);
        $protect="Options -Indexes\n<FilesMatch \"(?i)\\.(php[0-9]?|phtml|phar|shtml)$\">\nRequire all denied\n</FilesMatch>\n";$zip->addFromString('public/uploads/.htaccess',$protect);$hashes['public/uploads/.htaccess']=hash('sha256',$protect);
        $zip->addFromString('manifest.json',json_encode(['engine'=>ENGINE_ID,'version'=>ENGINE_VERSION,'schema'=>schema_version($pdo),'backup'=>$backup,'files'=>$hashes],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        $zip->close();unlink($snapshot);return $path;
    }catch(Throwable $e){shop_remove_dir($d);throw $e;}
}
function shop_import_package(string $path): string
{
    if(!class_exists('ZipArchive'))throw new InvalidArgumentException('Нужно расширение PHP ZIP.');
    $z=new ZipArchive();if($z->open($path)!==true)throw new InvalidArgumentException('Не удалось открыть ZIP.');$d=shop_workdir();$created=[];
    try{
        $total=0;$names=[];if($z->numFiles>12000)throw new InvalidArgumentException('Слишком много файлов.');
        for($i=0;$i<$z->numFiles;$i++){$s=$z->statIndex($i);$n=$s['name'];$total+=$s['size'];if($s['size']>64*1024*1024||$total>512*1024*1024||str_contains($n,'..')||str_contains($n,'\\')||str_starts_with($n,'/')||isset($names[$n]))throw new InvalidArgumentException('Небезопасный или слишком большой архив.');$names[$n]=true;}
        $manifest=json_decode($z->getFromName('manifest.json')?:'',true,32,JSON_THROW_ON_ERROR);
        if(($manifest['engine']??'')!==ENGINE_ID||($manifest['version']??'')!==ENGINE_VERSION||($manifest['schema']??0)!==schema_version(db())||!empty($manifest['backup']))throw new InvalidArgumentException('Нужна выгрузка магазина этой версии. Резервную копию восстанавливают на сервере.');
        if(!is_array($manifest['files']??null)||count($manifest['files'])!==count($names)-1)throw new InvalidArgumentException('Неполный архив.');
        foreach($manifest['files'] as $n=>$hash){if(!isset($names[$n])||!is_string($hash)||!hash_equals($hash,hash('sha256',$z->getFromName($n))))throw new InvalidArgumentException('Контрольная сумма архива не совпала.');}
        $content=json_decode($z->getFromName('content.json')?:'',true,64,JSON_THROW_ON_ERROR);
        if(!is_array($content)||array_keys($content)!==SHOP_CONTENT_TABLES)throw new InvalidArgumentException('Неполное содержимое.');
        // Import only rows into our own schema, never run SQL or PHP from the archive.
        $pdo=db();foreach(SHOP_CONTENT_TABLES as $t){if(!is_array($content[$t])||count($content[$t])>100000)throw new InvalidArgumentException('Слишком много записей.');$cols=array_column($pdo->query('PRAGMA table_info('.$t.')')->fetchAll(),'name');foreach($content[$t] as $row){if(!is_array($row)||array_keys($row)!==$cols)throw new InvalidArgumentException('Неверные поля: '.$t);foreach($row as $v)if(!is_scalar($v)&&$v!==null)throw new InvalidArgumentException('Неверное значение.');}}
        foreach($content['settings'] as $s)if(!in_array($s['key'],SHOP_PUBLIC_SETTINGS,true))throw new InvalidArgumentException('Закрытая настройка в архиве.');
        foreach($content['settings_i18n'] as $s)if(!in_array($s['key'],['site_title','seo_title','seo_description'],true))throw new InvalidArgumentException('Закрытая настройка.');
        $map=[];foreach($content['photos'] as &$p){foreach(['original_path','processed_path'] as $k){if(!$p[$k])continue;$old=$p[$k];$entry='data/uploads/originals/'.$old;if(basename($old)!==$old||!isset($manifest['files'][$entry]))throw new InvalidArgumentException('Нет исходника фото.');if(!isset($map[$entry])){$bytes=$z->getFromName($entry);$info=@getimagesizefromstring($bytes);if(!$info||!in_array($info['mime'],['image/jpeg','image/png','image/webp'],true)||$info[0]*$info[1]>80000000)throw new InvalidArgumentException('Некорректное изображение.');$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$info['mime']];$new='import-'.bin2hex(random_bytes(16)).'.'.$ext;if(!is_dir(ORIGINALS_DIR))mkdir(ORIGINALS_DIR,0775,true);$file=ORIGINALS_DIR.'/'.$new;if(file_put_contents($file,$bytes)===false)throw new RuntimeException('Нет места для фото.');$created[]=$file;$map[$entry]=$new;}$p[$k]=$map[$entry];}foreach(['path_l','path_m','path_s','head_path_l','head_path_m','head_path_s'] as $k)$p[$k]=null;}unset($p);
        // Public brand assets get new names, so failure cannot overwrite existing files.
        foreach($content['settings'] as &$s){if(in_array($s['key'],['logo_path','favicon_path','og_image_path'],true)&&$s['value']){$old=$s['value'];$entry='public/uploads/'.$old;if(!preg_match('~^brand/[A-Za-z0-9._-]+\.(png|jpe?g|webp|ico|svg)$~iD',$old)||!isset($manifest['files'][$entry]))throw new InvalidArgumentException('Нет файла оформления.');$bytes=$z->getFromName($entry);$ext=strtolower(pathinfo($old,PATHINFO_EXTENSION));if($ext==='svg'){if(preg_match('~<script|on[a-z]+\s*=|(?:href|src)\s*=\s*["\']?(?:https?:|//|data:|javascript:)~i',$bytes))throw new InvalidArgumentException('Небезопасный SVG.');}elseif(!@getimagesizefromstring($bytes))throw new InvalidArgumentException('Некорректное оформление.');$new='brand/import-'.bin2hex(random_bytes(16)).'.'.$ext;if(!is_dir(BRAND_DIR))mkdir(BRAND_DIR,0775,true);$file=PUBLIC_UPLOADS.'/'.$new;if(file_put_contents($file,$bytes)===false)throw new RuntimeException('Нет места.');$created[]=$file;$s['value']=$new;}}unset($s);
        $backup=shop_package(true);$dest=BACKUPS_DIR.'/before-import-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.zip';if(!is_dir(BACKUPS_DIR))mkdir(BACKUPS_DIR,0700,true);if(!rename($backup,$dest))throw new RuntimeException('Не удалось сохранить резервную копию.');shop_remove_dir(dirname($backup));
        $pdo->beginTransaction();try{$pdo->exec('PRAGMA defer_foreign_keys=ON');$pdo->exec('UPDATE shop_order_items SET product_id=NULL, option_id=NULL');
            foreach(array_reverse(SHOP_CONTENT_TABLES) as $t){if($t==='settings'){$qs=implode(',',array_fill(0,count(SHOP_PUBLIC_SETTINGS),'?'));$pdo->prepare('DELETE FROM settings WHERE key IN ('.$qs.')')->execute(SHOP_PUBLIC_SETTINGS);}else $pdo->exec('DELETE FROM '.$t);}
            foreach(SHOP_CONTENT_TABLES as $t)foreach($content[$t] as $row){$st=$pdo->prepare('INSERT INTO '.$t.' ('.implode(',',array_keys($row)).') VALUES ('.implode(',',array_fill(0,count($row),'?')).')');$st->execute(array_values($row));}
            if($pdo->query('PRAGMA foreign_key_check')->fetch())throw new InvalidArgumentException('Связанные записи повреждены.');$pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        $created=[];setting_reset();return basename($dest);
    }finally{foreach($created as $f)@unlink($f);$z->close();shop_remove_dir($d);}
}
function shop_download_package(): void
{
    $file=shop_package();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="enotis-shop-'.ENGINE_VERSION.'-'.date('Y-m-d').'.zip"');header('Content-Length: '.filesize($file));readfile($file);shop_remove_dir(dirname($file));
}
function shop_orders_route(): void
{
    $error=null;$id=0;try{
        if(isset($_GET['id']))$id=shop_integer(shop_scalar($_GET,'id'),'Номер заказа',1,2147483647);
        if($_SERVER['REQUEST_METHOD']==='POST'){admin_check_csrf(shop_scalar($_POST,'csrf'));$id=shop_integer(shop_scalar($_POST,'id'),'Номер заказа',1,2147483647);$status=shop_scalar($_POST,'status');$old=shop_one('shop_orders',$id);$allowed=['new'=>['confirmed','cancelled'],'confirmed'=>['completed','cancelled'],'completed'=>[],'cancelled'=>[]];if(!$old||!in_array($status,$allowed[$old['status']],true))throw new InvalidArgumentException('Этот переход статуса недоступен.');$st=db()->prepare('UPDATE shop_orders SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status=?');$st->execute([$status,$id,$old['status']]);if(!$st->rowCount())throw new InvalidArgumentException('Заказ уже изменён. Обновите страницу.');admin_redirect('/admin/shop/orders?id='.$id.'&saved=1');return;}
    }catch(InvalidArgumentException $e){http_response_code(422);$error=$e->getMessage();}
    $order=$id?shop_one('shop_orders',$id):null;if($id&&!$order){http_response_code(404);echo 'Заказ не найден';return;}
    $status=is_string($_GET['status']??null)&&in_array($_GET['status'],['new','confirmed','completed','cancelled'],true)?$_GET['status']:'';
    $page=max(1,min(100000,(int)(is_scalar($_GET['page']??1)?($_GET['page']??1):1)));$where=$status!==''?' WHERE status=?':'';$args=$status!==''?[$status]:[];
    $total=(int)shop_query('SELECT COUNT(*) n FROM shop_orders'.$where,$args)[0]['n'];$orders=shop_query('SELECT * FROM shop_orders'.$where.' ORDER BY id DESC LIMIT 50 OFFSET '.(($page-1)*50),$args);
    echo view('admin/shop-tools',compact('order','orders','id','status','page','total','error')+['tool'=>'orders','csrf'=>$_SESSION['csrf']]);
}
function shop_transfer_route(): void
{
    $error=null;$backup=null;try{if($_SERVER['REQUEST_METHOD']==='POST'){admin_check_csrf(shop_scalar($_POST,'csrf'));$action=shop_scalar($_POST,'action');if($action==='export'){shop_download_package();return;}if($action==='import'){if(shop_scalar($_POST,'confirm')!=='1')throw new InvalidArgumentException('Подтвердите замену содержимого.');$f=$_FILES['file']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Выберите ZIP. Проверьте лимит загрузки хостинга.');$backup=shop_import_package($f['tmp_name']);}else throw new InvalidArgumentException('Неизвестное действие.');}}
    catch(Throwable $e){http_response_code(422);$error=$e instanceof InvalidArgumentException?$e->getMessage():'Не удалось обработать архив. Данные сохранены; проверьте журнал сервера и свободное место.';error_log('[shop-transfer] '.$e->getMessage());}
    echo view('admin/shop-tools',compact('error','backup')+['tool'=>'transfer','csrf'=>$_SESSION['csrf']]);
}
