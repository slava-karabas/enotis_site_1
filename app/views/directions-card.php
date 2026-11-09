<?php
/** Shared single-place directions. Uses existing title/place/lat/lng fields. */
$destination = $mapTile ?? $t;
$hasCoordinates = has_coords($destination);
$context = $directionsContext ?? 'place';
$mapId = 'directions-map-' . preg_replace('/[^a-z0-9-]/i', '', $context) . '-' . (int) $t['id'];
$level = ($directionsHeading ?? 3) === 2 ? 'h2' : 'h3';
$address = trim((string) ($t['place'] ?? ''));
$points = [];
if ($hasCoordinates) {
    $lat = (float) $destination['lat'];
    $lng = (float) $destination['lng'];
    $route = feed_setting('maps', 'google') === 'yandex'
        ? "https://yandex.ru/maps/?rtext=~{$lat},{$lng}&rtt=auto"
        : "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}";
    $points[] = ['lat'=>$lat, 'lng'=>$lng, 'title'=>$destination['title'], 'url'=>null, 'popup'=>null, 'route'=>$route];
}
?>
<section class="place map directions-card">
  <div class="map__head">
    <<?= $level ?> class="map__h"><?= e(t('tile.how_to_get')) ?></<?= $level ?>>
<?php if ($hasCoordinates): ?>
    <button type="button" class="map__expand" data-map-expand aria-controls="<?= e($mapId) ?>" aria-expanded="false" hidden><?= e(t('map.expand')) ?></button>
<?php endif ?>
  </div>
  <div class="directions-card__surface">
<?php if ($hasCoordinates): ?>
    <div id="<?= e($mapId) ?>" class="map__box" data-map data-place-map data-title="<?= e($destination['title']) ?>" data-close="<?= e(t('map.collapse')) ?>" data-more="<?= e(t('map.more')) ?>" data-route-label="<?= e(t('map.route')) ?>" data-attr="<?= e(t('map.attribution')) ?>" data-points="<?= e(json_encode($points, JSON_UNESCAPED_UNICODE)) ?>">
      <ul class="map__list"><li><?= e($destination['title']) ?></li></ul>
    </div>
<?php endif ?>
    <div class="directions-card__footer">
      <div class="directions-card__details">
        <p class="directions-card__name"><?= e($destination['title']) ?></p>
<?php if ($address !== ''): ?>
        <p class="directions-card__address"><?= e($address) ?></p>
<?php endif ?>
<?php if (!empty($locationNote)): ?>
        <p class="directions-card__note"><?= e($locationNote) ?></p>
<?php endif ?>
      </div>
<?php if ($hasCoordinates): ?>
      <a class="btn directions-card__route" href="<?= e($route) ?>" target="_blank" rel="noopener"><?= e(t('map.route')) ?></a>
<?php endif ?>
    </div>
  </div>
</section>
