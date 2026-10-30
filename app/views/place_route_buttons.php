<?php
$lat=(float)$t['lat'];$lng=(float)$t['lng'];$service=feed_setting('maps','google');
[$route,$open]=$service==='yandex'
    ? ["https://yandex.ru/maps/?rtext=~{$lat},{$lng}&rtt=auto","https://yandex.ru/maps/?pt={$lng},{$lat}&z=15&l=map"]
    : ["https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}","https://www.google.com/maps/search/?api=1&query={$lat},{$lng}"];
?>
<p class="wrap place__btns">
  <a class="btn btn--ghost" href="<?= e($route) ?>" target="_blank" rel="noopener"><?= e(t('map.route')) ?></a>
  <a class="btn btn--ghost" href="<?= e($open) ?>" target="_blank" rel="noopener"><?= e(t($service==='yandex'?'map.open_yandex':'map.open_google')) ?></a>
</p>
