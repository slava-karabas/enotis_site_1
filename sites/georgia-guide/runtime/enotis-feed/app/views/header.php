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
      <span class="hdr__identity"><span class="hdr__wordmark"><svg xmlns="http://www.w3.org/2000/svg" viewBox="15 25 315 100" width="189" height="60" role="img" aria-label="Enotis"><g fill="#7B2035" transform="translate(20 30) scale(1.1)"><path d="M23 0h2v3h3v2h-3v3h-2V5h-3V3h3zM17 16c0-5 3-8 7-10 4 2 7 5 7 10zM18 17h12v6H18zM8 23l8-6 8 6 8-6 8 6v12H8zM2 35h44v3H2z"/><path d="M20 25h8v10h-8z" fill="var(--c-bg,#f3ede5)"/><path d="M8 41H40V49Q40 53 35 56L29 61V70H19V61L13 56Q8 53 8 49Z"/><path d="M19 72h10v3H19Z"/><path d="M18 47h12v4H18Z" fill="var(--c-bg,#f3ede5)"/></g><text transform="translate(87 71) scale(.9114) translate(-87 -71)" x="87" y="91" fill="#7B2035" font-family="Charter" font-size="60" font-weight="900" letter-spacing="-.8">Enotis</text></svg></span></span>
<?php endif ?>
<?php if ($logo): ?>
      <img class="hdr__logo" src="/uploads/<?= e($logo) ?>" alt="<?= $withTitle ? '' : e(site_title()) ?>" height="40">
<?php endif ?>
<?php if ($withTitle && $logo): ?>
      <span class="hdr__title"><?= e($logo ? site_title() : 'Монастыри Грузии') ?></span>
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
        <a href="<?= e(url('/')) ?>">Все монастыри</a>
        <a href="<?= e(url('/')) ?>#about">О проекте</a>
        <a href="#footer-social">Соцсети</a>
        <a href="#footer-contact">Связь с проектом</a>
<?php if ($menuPhone = setting('footer_phone')): ?>
        <a class="menu__phone" href="tel:<?= e(preg_replace('/[^\d+]/', '', $menuPhone)) ?>"><?= e($menuPhone) ?></a>
<?php endif ?>
      </nav>
    </details>
<?php endif ?>
  </div>
</header>
