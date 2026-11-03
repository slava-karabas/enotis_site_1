<?php
if (setting('header_show', '1') !== '1') {
    return;
}
$langs = site_languages();
$logo = setting('logo_path');
$withTitle = !$logo || setting('logo_with_title', '1') === '1';
$menu = [];
foreach (tiles_in(home_feed_blocks()) as $t) {
    if (!is_wine($t) && tile_opens($t)) {
        $menu[] = $t;
    }
}
$burger = match (setting('header_burger', 'auto')) {
    'on'    => true,
    'off'   => false,
    default => count($menu) > 6,
};
$here = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$plain = preg_replace('~^/(' . implode('|', LANGS) . ')(?=/|$)~', '', $here) ?: '/';
?>
<header class="hdr" data-header<?= eid('header') ?>>
  <div class="hdr__in">
<?php if ($back): ?>
    <a class="hdr__back" href="<?= e($back) ?>"><?= e(t('nav.back')) ?></a>
<?php endif ?>
    <a class="hdr__brand" href="<?= e(url('/')) ?>">
<?php if (!$logo): ?>
      <span class="hdr__title"><?= e(site_title()) ?></span>
<?php endif ?>
<?php if ($logo): ?>
      <img class="hdr__logo" src="/uploads/<?= e($logo) ?>" alt="<?= $withTitle ? '' : e(site_title()) ?>" height="40">
<?php endif ?>
<?php if ($withTitle && $logo): ?>
      <span class="hdr__title"><?= e(site_title()) ?></span>
<?php endif ?>
    </a>
<?php if (count($langs) > 1): ?>
    <nav class="langs" aria-label="<?= e(t('nav.language')) ?>">
<?php   foreach ($langs as $i => $l): ?>
      <?= $i ? '<span aria-hidden="true">·</span>' : '' ?><a href="<?= e(url($plain, $l['code'])) ?>?switch=1" hreflang="<?= e($l['code']) ?>" lang="<?= e($l['code']) ?>" title="<?= e($l['name']) ?>"<?= $l['code'] === current_lang() ? ' aria-current="true"' : '' ?>><?= e(strtoupper($l['code'])) ?></a>
<?php   endforeach ?>
    </nav>
<?php endif ?>
<?php if ($burger): ?>
    <details class="menu" data-menu>
      <summary aria-label="<?= e(t('nav.menu')) ?>"><svg class="menu__icon" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M3 5h18M3 12h18M3 19h18"/></svg></summary>
      <nav class="menu__list" aria-label="Навигация по сайту">
<?php foreach (presentation_navigation() as $item): ?>
        <a href="<?=e($item['url'])?>"><?=e($item['title'])?></a>
<?php endforeach ?>
<?php if ($menuPhone = setting('footer_phone')): ?>
        <a class="menu__phone" href="tel:<?= e(preg_replace('/[^\d+]/', '', $menuPhone)) ?>"><?= e($menuPhone) ?></a>
<?php endif ?>
      </nav>
    </details>
<?php endif ?>
  </div>
</header>
