<?php
$wine = $b['tiles_kind'] === 'wine';
$size = $wine ? 'compact' : ($b['tiles_size'] ?: 'large');
$frame = $wine ? '4x5' : str_replace(':', 'x', $b['frame'] ?: '4:5');
$tiles = visible_children($b);
$filter=panel_block_extension($b);
$filterLabels=($filter['kind']??'')==='filters'?array_slice($filter['labels']??[],0,2):[];
$GLOBALS['eager'] ??= 3;
// Бейдж таба на плитках уровня 1 в ленте таба; на адресе одного таба его нет (раздел 5.4).
$badge = ($b['section_id'] !== null && $b['parent_id'] === null && !single_tab()) ? (sections()[$b['section_id']]['title'] ?? null) : null;
$sizes = $size === 'large' ? '(min-width: 1440px) 446px, (min-width: 1024px) calc((100vw - 104px) / 3), (min-width: 600px) calc((100vw - 80px) / 2), calc(100vw - 32px)'
                           : '(min-width: 1440px) 328px, (min-width: 1024px) calc((100vw - 128px) / 4), (min-width: 600px) calc((100vw - 104px) / 3), calc((100vw - 56px) / 2)';
?>
<section class="wrap blk tiles tiles--<?= e($size) ?><?= $wine ? ' tiles--wine' : '' ?>"<?= eid($b['id']) ?><?php if (!empty($b['_page_anchor'])): ?> id="block-<?= (int)$b['id'] ?>"<?php endif ?> data-tile-filters>
<?php if ($b['title']): ?>
  <h2 class="tiles__h"><?= e($b['title']) ?></h2>
<?php endif ?>
<?php foreach($filterLabels as $index=>$label):$values=[];foreach($tiles as $tile){$ext=panel_block_extension($tile);$v=$ext['values'][$index]??'';if($v!=='')$values[$v]=$v;} ?><label style="display:inline-block;margin:8px"><?=e($label)?> <select data-tile-filter="<?=(int)$index?>"><option value=""><?=e($filter['all']??'Все')?></option><?php foreach($values as $v):?><option value="<?=e($v)?>"><?=e($v)?></option><?php endforeach ?></select></label><?php endforeach ?>
  <ul class="grid">
<?php foreach ($tiles as $t):
    $opens = tile_opens($t);
    $lazy = $GLOBALS['eager']-- <= 0;
    $tag = $opens ? 'a' : 'div';
    $attr = $opens ? ' href="' . e(tile_url($t)) . '"' . ($opens === 'popup' ? ' data-popup-link="' . (int) $t['id'] . '"' : '') : '';
?>
    <li<?= eid($t['id']) ?> data-filter-values="<?=e(json_encode(panel_block_extension($t)['values']??[]))?>">
      <<?= $tag ?> class="card<?= $wine ? ' card--wine product-card' : '' ?>"<?= $attr ?>>
<?php if ($t['photo']): ?>
        <span class="frame frame--<?= $frame ?><?= $wine ? ' frame--wine product-image' : '' ?>"><?= photo_img($t['photo'], $t['title'], $lazy, false, $sizes) ?><?php if ($badge && !$wine): ?><span class="badge"><?= e($badge) ?></span><?php endif ?></span>
<?php elseif ($wine): ?>
        <span class="frame frame--4x5 frame--wine product-image"><?= view('bottle') ?></span>
<?php endif ?>
        <span class="card__plate">
          <span class="card__text">
            <span class="card__title"><?= e($t['title']) ?></span>
<?php if ($wine): ?>
            <?= view('wine_facts', ['t' => $t, 'tag' => 'span']) ?>
<?php elseif ($t['subtitle']): ?>
            <span class="card__sub"><?= e($t['subtitle']) ?></span>
<?php endif ?>
<?php if ($wine && show_prices() && $t['price'] !== null): ?>
            <span class="price"><?= e(money((float) $t['price'])) ?></span>
<?php endif ?>
          </span>
<?php if ($opens): ?>
          <span class="card__icon" aria-hidden="true"><?= $opens === 'page' ? '→' : '+' ?></span>
<?php endif ?>
        </span>
      </<?= $tag ?>>
    </li>
<?php endforeach ?>
  </ul>
</section>
