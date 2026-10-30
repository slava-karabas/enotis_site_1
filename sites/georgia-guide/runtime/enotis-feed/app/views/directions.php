<?php
$lat = has_coords($t) ? (float) $t['lat'] : null;
$lng = has_coords($t) ? (float) $t['lng'] : null;
$svc = feed_setting('maps', 'google');
$route = $svc === 'yandex' ? "https://yandex.ru/maps/?rtext=~{$lat},{$lng}&rtt=auto" : "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
$open = $svc === 'yandex' ? "https://yandex.ru/maps/?pt={$lng},{$lat}&z=15&l=map" : "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
?>
<div id="directions" class="tpage__directions">
<?php if (has_coords($t)): ?>
  <?= view('blocks/map', ['b' => ['id' => $t['id'], 'title' => t('tile.how_to_get')], 'mapTiles' => [$t], 'mapSelf' => true]) ?>
<?php else: ?>
  <h2 class="wrap"><?= e(t('tile.how_to_get')) ?></h2>
<?php endif ?>
  <div class="wrap tpage__route-info">
<?php if ($t['place']): ?><p><?= e($t['place']) ?></p><?php endif ?>
<?php if (has_coords($t)): ?>
    <p class="place__btns"><a class="btn" href="<?= e($route) ?>" target="_blank" rel="noopener"><?= e(t('map.route')) ?></a><a class="btn btn--ghost" href="<?= e($open) ?>" target="_blank" rel="noopener"><?= e(t($svc === 'yandex' ? 'map.open_yandex' : 'map.open_google')) ?></a></p>
<?php endif ?>
  </div>
</div>
