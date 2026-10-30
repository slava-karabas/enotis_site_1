<?php
declare(strict_types=1);

/**
 * Релиз движка (спецификация 2.1, разделы 14.1–14.2): php bin/build-release.php [папка] [адрес_папки_обновлений]
 * Собирает в папку (по умолчанию dist/):
 *   enotis-admin-1-X.Y.Z.zip   — код движка без data/ (кроме data/.htaccess), без demo/, docs/, tests/;
 *   latest.json        — версия, ссылка на ZIP, sha256, min_php, «Что нового» из docs/releases/X.Y.Z.json;
 * Файлы берутся из git (только то, что в репозитории).
 */

$root = dirname(__DIR__);
$out = rtrim($argv[1] ?? $root . '/dist', '/');
$base = rtrim($argv[2] ?? '', '/');

$src = (string) file_get_contents($root . '/app/bootstrap.php');
if (!preg_match("~const ENGINE_VERSION = '(\d+\.\d+\.\d+)'~", $src, $m)) {
    fwrite(STDERR, "Не найдена ENGINE_VERSION\n");
    exit(1);
}
$version = $m[1];
if (!is_dir($out)) {
    mkdir($out, 0775, true);
}

// Explicit code-only allowlist. Works in a source checkout and an extracted package.
$files=[];
foreach(['app','public/assets','bin'] as $dir){
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir",FilesystemIterator::SKIP_DOTS));
    foreach($it as $f)if($f->isFile()&&!$f->isLink()&&preg_match('/\.(php|sql|json|js|css|svg|png|jpg|jpeg|webp|woff2?|ttf|map|txt)$/i',$f->getFilename()))$files[]=substr($f->getPathname(),strlen($root)+1);
}
foreach(['.htaccess','public/index.php','public/uploads/.htaccess','data/.htaccess'] as $f)if(is_file("$root/$f"))$files[]=$f;
sort($files);

$zipFile = "$out/enotis-admin-1-$version.zip";
@unlink($zipFile);
$z = new ZipArchive();
$z->open($zipFile, ZipArchive::CREATE);
foreach ($files as $f) {
    $z->addFile("$root/$f", $f);
}
$z->addFromString('data/.gitkeep', '');
$z->close();

$notesFile = "$root/docs/releases/$version.json";
$notes = is_file($notesFile) ? json_decode((string) file_get_contents($notesFile), true) : null;
$latest = [
    'product' => 'enotis-feed-free-v2',
    'version' => $version,
    'zip'     => $base === '' ? null : "$base/enotis-admin-1-$version.zip",
    'sha256'  => hash_file('sha256', $zipFile),
    'min_php' => '8.1',
    'notes'   => is_array($notes) ? $notes : ['ru' => [], 'ka' => [], 'en' => [], 'fr' => []],
];
file_put_contents("$out/latest.json", json_encode($latest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");

echo "enotis-admin-1-$version.zip, latest.json → $out\n";
