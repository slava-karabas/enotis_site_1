<?php
declare(strict_types=1);
// php bin/build-release.php /path/to/output. No Git, credentials or demo content.
require dirname(__DIR__).'/app/bootstrap.php';
$out=rtrim($argv[1]??ROOT_DIR.'/dist','/');if(!is_dir($out)&&!mkdir($out,0775,true))throw new RuntimeException('Нет папки результата');
$path=$out.'/enotis-shop-'.ENGINE_VERSION.'.zip';$z=new ZipArchive();if($z->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('ZIP недоступен');foreach(shop_code_files() as $n=>$f)$z->addFile($f,$n);$z->addFromString('public/uploads/.gitkeep','');$z->addFromString('public/uploads/.htaccess',"Options -Indexes\n<FilesMatch \"(?i)\\.(php[0-9]?|phtml|phar|shtml)$\">\nRequire all denied\n</FilesMatch>\n");$z->close();file_put_contents($path.'.sha256',hash_file('sha256',$path).'  '.basename($path)."\n");echo basename($path)." создан\n";
