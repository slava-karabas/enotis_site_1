<?php
$GLOBALS['h1_done'] = true;
$t = $tile;
$hasPlace = $t['place'] || has_coords($t);
// Guide profile: no product blocks. Existing editorial files keep the other modes separately.
$blocks = array_values(array_filter($blocks, fn($b) => !(($b['type'] === 'tiles' && ($b['tiles_kind'] ?? '') === 'wine') || in_array(panel_block_extension($b)['kind'] ?? '', ['products','gift'], true))));
?>
<article class="tpage guide-page">
  <div class="wrap guide-page__heading">
    <h1><?= e($t['title']) ?></h1>
    <p class="guide-location"><?= e($tab['title'] ?? '') ?></p>
    <nav class="guide-nav" aria-label="Разделы страницы">
      <a href="#monastery-about">Об обители</a>
<?php foreach ($blocks as $b): if (!is_visible($b)) continue; $title=$b['title'] ?: ((panel_block_extension($b)['kind'] ?? '') === 'contacts' ? t('contacts.title') : ''); if (!$title) continue; ?>
      <a href="#block-<?= (int)$b['id'] ?>"><?= e($title) ?></a>
<?php endforeach ?>
<?php if ($hasPlace): ?><a href="#directions"><?= e(t('tile.how_to_get')) ?></a><?php endif ?>
    </nav>
  </div>
  <div id="monastery-about" class="tpage__top wrap<?= !$t['photo'] ? ' tpage__top--no-photo' : '' ?>"<?= eid($t['id']) ?>>
<?php if ($t['photo']): ?>
    <div class="tpage__photo frame frame--3x2"><?= photo_img($t['photo'], $t['title'], false, true, '(min-width: 1440px) 674px, (min-width: 1024px) calc((100vw - 92px) / 2), (min-width: 600px) calc(100vw - 56px), calc(100vw - 32px)') ?></div>
<?php endif ?>
    <div class="tpage__text"><?php panel_description($t['body']); ?></div>
  </div>
  <div class="feed"><?= view('blocks', ['blocks' => $blocks]) ?></div>
<?php if ($hasPlace): ?><?= view('directions', ['t' => $t]) ?><?php endif ?>
  <p class="wrap guide-source"><a href="<?= e(url('/sources')) ?>#<?= e($t['slug']) ?>">Источники сведений и фотографий</a></p>
</article>
<?= view('popups', ['blocks' => $blocks, 'open' => $popup, 'pageUrl' => url(feed_prefix($t['section_id']) . '/' . $t['slug'])]) ?>
