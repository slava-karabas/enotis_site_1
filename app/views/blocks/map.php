<?php
$points = [];
$mapId = $mapId ?? 'map-block-' . (int) $b['id'];
$service = feed_setting('maps', 'google');
foreach ($mapPlaces ?? map_points($b) as $t) {
    $opens = isset($mapPlaces) ? null : tile_opens($t);
    $lat = (float) $t['lat'];
    $lng = (float) $t['lng'];
    $points[] = [
        'lat' => $lat, 'lng' => $lng, 'title' => $t['title'],
        'url' => $opens ? tile_url($t) : null,
        'popup' => $opens === 'popup' ? (int) $t['id'] : null,
        'route' => $service === 'yandex'
            ? "https://yandex.ru/maps/?rtext=~{$lat},{$lng}&rtt=auto"
            : "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}",
    ];
}
?>
<section class="wrap blk map"<?= eid($b['id']) ?><?php if (!empty($b['_page_anchor'])): ?> id="block-<?= (int)$b['id'] ?>"<?php endif ?>>
  <div class="map__head">
<?php if ($b['title']): ?>
    <h2 class="map__h"><?= e($b['title']) ?></h2>
<?php endif ?>
    <button type="button" class="map__expand" data-map-expand aria-controls="<?= e($mapId) ?>" aria-expanded="false" hidden><?= e(t('map.expand')) ?></button>
  </div>
<?php if (!empty($mapAddress)): ?><p class="map__address"><?= e($mapAddress) ?></p><?php endif ?>
  <div id="<?= e($mapId) ?>" class="map__box" data-map data-title="<?= e($b['title'] ?: t('map.title')) ?>" data-close="<?= e(t('map.collapse')) ?>" data-more="<?= e(t('map.more')) ?>" data-route-label="<?= e(t('map.route')) ?>" data-attr="<?= e(t('map.attribution')) ?>" data-points="<?= e(json_encode($points, JSON_UNESCAPED_UNICODE)) ?>">
    <ul class="map__list">
<?php foreach ($points as $p): ?>
      <li><?php if ($p['url']): ?><a href="<?= e($p['url']) ?>"><?= e($p['title']) ?></a><?php else: ?><?= e($p['title']) ?><?php endif ?></li>
<?php endforeach ?>
    </ul>
  </div>
  <p class="map__hint"><?= e(t('map.hint')) ?></p>
</section>
