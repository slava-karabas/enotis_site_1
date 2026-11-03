<?php
/** Shared editable introduction; photo HTML comes exclusively from server photo helpers. */
$links = $links ?? [];
?>
<header class="tpage__intro"<?= eid($id) ?>>
  <div class="wrap tpage__heading">
    <h1><?= e($title) ?></h1>
<?php if ($eyebrow): ?><p class="tpage__location"><?= e($eyebrow) ?></p><?php endif ?>
<?php if ($links): ?>
    <nav class="tpage__nav" aria-label="<?= e(t('place.sections')) ?>">
<?php foreach ($links as $link): ?><a href="<?= e($link['href']) ?>"><?= e($link['title']) ?></a><?php endforeach ?>
    </nav>
<?php endif ?>
  </div>
<?php if ($photoHtml || trim($body ?? '') !== ''): ?>
  <div id="place-about" class="tpage__top wrap<?= !$photoHtml ? ' tpage__top--no-photo' : '' ?>">
<?php if ($photoHtml): ?><div class="tpage__photo frame frame--3x2"><?= $photoHtml ?></div><?php endif ?>
    <div class="tpage__text"><?php panel_description($body); ?></div>
  </div>
<?php endif ?>
</header>
