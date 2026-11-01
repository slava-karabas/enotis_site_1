<?php
$manifestFile=ROOT_DIR.'/public/assets/wines/manifest.json';
$images=is_file($manifestFile)?json_decode(file_get_contents($manifestFile),true):[];
$slug=(string)($wine_slug??'');$file=$images[$slug]??($images['_default']??null);
if($file&&is_file(ROOT_DIR.'/public/assets/wines/'.basename($file))): ?>
<img class="wine-photo-normalized" src="/assets/wines/<?=e(rawurlencode(basename($file)))?>" alt="<?=e($wine_alt??'Демонстрационная бутылка Henotis')?>" width="1200" height="1500" loading="<?=empty($wine_eager)?'lazy':'eager'?>" decoding="async">
<?php elseif(!empty($wine_fallback)): ?>
<img src="<?=e($wine_fallback)?>" alt="<?=e($wine_alt??'')?>" loading="lazy">
<?php else: echo view('bottle'); endif ?>
