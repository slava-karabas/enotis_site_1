<?php
$service = feed_setting('maps', 'google');
$mapId = 'place-map-'.(int)$t['id'];
$points = [];
if (has_coords($t)) {
    $lat=(float)$t['lat']; $lng=(float)$t['lng'];
    $route=$service === 'yandex' ? "https://yandex.ru/maps/?rtext=~{$lat},{$lng}&rtt=auto" : "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
    $points[]=['lat'=>$lat,'lng'=>$lng,'title'=>$t['title'],'url'=>null,'popup'=>null,'route'=>$route];
}
?>
<section class="place map directions-card">
  <div class="map__head">
    <h2 class="map__h"><?=e(t('tile.how_to_get'))?></h2>
<?php if($points): ?>
    <button type="button" class="map__expand" data-map-expand aria-controls="<?=e($mapId)?>" aria-expanded="false" hidden><?=e(t('map.expand'))?></button>
<?php endif ?>
  </div>
  <div class="directions-card__surface">
<?php if($points): ?>
    <div id="<?=e($mapId)?>" class="map__box" data-map data-place-map data-title="<?=e($t['title'])?>" data-close="<?=e(t('map.collapse'))?>" data-route-label="<?=e(t('map.route'))?>" data-attr="<?=e(t('map.attribution'))?>" data-points="<?=e(json_encode($points,JSON_UNESCAPED_UNICODE))?>"><ul class="map__list"><li><?=e($t['title'])?></li></ul></div>
<?php endif ?>
    <div class="directions-card__footer">
      <div class="directions-card__details"><p class="directions-card__name"><?=e($t['title'])?></p>
<?php if(!empty($t['place'])): ?><p class="directions-card__address"><?=e($t['place'])?></p><?php endif ?>
      </div>
<?php if($points): ?><a class="btn directions-card__route" href="<?=e($route)?>" target="_blank" rel="noopener noreferrer"><?=e(t('map.route'))?></a><?php endif ?>
    </div>
  </div>
</section>
