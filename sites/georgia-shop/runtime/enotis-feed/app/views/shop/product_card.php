<?php
// One renderer for the catalogue, content selections and ready gifts.
$gift = $gift ?? false;
$href = shop_link(($gift ? '/gift/' : '/wine/') . $p['slug']);
$colors = ['red'=>'Красное','white'=>'Белое','rose'=>'Розовое','amber'=>'Янтарное'];
$sugars = ['dry'=>'Сухое','semi_dry'=>'Полусухое','semi_sweet'=>'Полусладкое','sweet'=>'Сладкое'];
$facts = $gift ? [] : array_filter([$sugars[$p['sweetness']] ?? '', $colors[$p['color']] ?? '', !empty($p['vintage']) ? (string)$p['vintage'] : '']);
$available = $gift || (!empty($p['available']) && (int)$p['price_minor']>0);
?>
<article class="card product-card" data-panel-card data-name="<?=e(mb_strtolower($p['name']))?>">
  <a class="product-image" href="<?=e($href)?>">
<?php if ($img = shop_image($p['photo_id'] ? (int)$p['photo_id'] : null)): ?>
    <img src="<?=e($img)?>" alt="<?=e($p['name'])?>" loading="lazy">
<?php else: ?>
    <span class="frame frame--4x5"><?=view('bottle')?><span class="wine-photo-note">Фото вина уточняется</span></span>
<?php endif ?>
  </a>
  <div class="card-content">
    <h3><a href="<?=e($href)?>"><?=e($p['name'])?></a></h3>
    <p class="muted"><?=e($gift ? 'Готовый подарок' : ($p['monastery_name'] ?? ''))?></p>
<?php if ($facts): ?><p class="wine-meta"><?=e(implode(' · ', $facts))?></p><?php endif ?>
    <p class="price"><?= (int)$p['price_minor']>0 ? e(shop_money((int)$p['price_minor'])) : 'Цена уточняется' ?></p>
<?php if ($available): ?>
    <form method="post" action="<?=e(shop_link('/cart'))?>">
      <input type="hidden" name="csrf" value="<?=e($_SESSION['shop_csrf'] ?? '')?>">
      <input type="hidden" name="lang" value="<?=e(current_lang())?>">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="key" value="<?=e(($gift ? 'g:' : 'p:').$p['id'])?>">
      <input type="hidden" name="quantity" value="1">
      <button class="button primary">В корзину</button>
    </form>
<?php else: ?><p class="muted">Сейчас недоступно</p><?php endif ?>
  </div>
</article>
