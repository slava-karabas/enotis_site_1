<?php
$gift=$gift??false;$href=shop_link(($gift?'/gift/':'/wine/').$p['slug']);
$colors=['red'=>'красное','white'=>'белое','rose'=>'розовое','amber'=>'янтарное'];$sugars=['dry'=>'сухое','semi_dry'=>'полусухое','semi_sweet'=>'полусладкое','sweet'=>'сладкое'];
$countries=array_column(shop_choices('countries',current_lang()),'name','id');
$facts=$gift?[]:array_filter([$countries[$p['country_id']??0]??'', $p['monastery_name']??'',implode(', ',array_filter([$sugars[$p['sweetness']]??'',$colors[$p['color']]??''])),$p['grape']??'',!empty($p['vintage'])?(string)$p['vintage']:'']);
$available=$gift||(!empty($p['available'])&&(int)$p['price_minor']>0);
$name='<span class="wine-initial">'.e(mb_substr($p['name'],0,1)).'</span>'.e(mb_substr($p['name'],1));
$image=shop_image($p['photo_id']?(int)$p['photo_id']:null);
$factHtml=function()use($facts){foreach($facts as $fact)echo '<p class="wine-fact">'.e($fact).'</p>';};
$buy=function()use($available,$gift,$p){if(!$available){echo '<p class="muted">Сейчас недоступно</p>';return;}?>
<form method="post" action="<?=e(shop_link('/cart'))?>"><input type="hidden" name="csrf" value="<?=e($_SESSION['shop_csrf']??'')?>"><input type="hidden" name="lang" value="<?=e(current_lang())?>"><input type="hidden" name="action" value="add"><input type="hidden" name="key" value="<?=e(($gift?'g:':'p:').$p['id'])?>"><input type="hidden" name="quantity" value="1"><button class="button primary">В корзину</button></form>
<?php };
$photo=function()use($image,$p){if($image):?><img src="<?=e($image)?>" alt="<?=e($p['name'])?>" loading="lazy"><?php else:?><span class="frame frame--4x5"><?=view('bottle')?><span class="wine-photo-note">Фото вина уточняется</span></span><?php endif;};
?>
<article class="card product-card" data-panel-card data-name="<?=e(mb_strtolower($p['name']))?>">
<a class="product-image" href="<?=e($href)?>" data-shop-details><?php $photo();?></a>
<div class="card-content"><h3><a href="<?=e($href)?>" data-shop-details><?=$name?></a></h3>
<div class="wine-facts"><?php $factHtml();?></div><div class="shop-card-actions"><div class="shop-price-row"><span class="price"><?=(int)$p['price_minor']>0?e(shop_money((int)$p['price_minor'])):'Цена уточняется'?></span><a class="shop-more" href="<?=e($href)?>" data-shop-details aria-label="<?=e('Подробнее: '.$p['name'])?>">+</a></div><?php $buy();?></div></div>
<template data-shop-detail><div class="shop-detail-layout"><div class="product-image shop-detail-image"><?php $photo();?></div><div class="shop-detail-text"><h2 id="shop-detail-title"><?=$name?></h2><div class="wine-facts"><?php $factHtml();?></div><p class="shop-description"><?=nl2br(e($p['description']??''))?></p><p class="price"><?=(int)$p['price_minor']>0?e(shop_money((int)$p['price_minor'])):'Цена уточняется'?></p><?php $buy();?></div></div></template>
</article>