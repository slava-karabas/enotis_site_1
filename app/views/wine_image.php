<?php
// Images are content: selected through the editor and included in JSON/ZIP transfers.
$p=$wine_photo??null;
if(!$p && !empty($wine_slug)){$q=db()->prepare('SELECT photo_id FROM elements WHERE slug=? LIMIT 1');$q->execute([$wine_slug]);$id=$q->fetchColumn();if($id){$q=db()->prepare('SELECT * FROM photos WHERE id=?');$q->execute([$id]);$p=$q->fetch();}}
$src=null;
if($p&&!wine_photo_demo($p))$src=wine_photo_url($p);
elseif($p)$src='/assets/wines/henotis.png';
elseif(!empty($wine_fallback))$src=$wine_fallback;
else $src='/assets/wines/henotis.png';
?>
<img class="wine-photo-normalized" src="<?=e($src)?>" alt="<?=e($wine_alt??'Демонстрационная бутылка Henotis')?>" width="1200" height="1500" loading="<?=empty($wine_eager)?'lazy':'eager'?>" decoding="async">
