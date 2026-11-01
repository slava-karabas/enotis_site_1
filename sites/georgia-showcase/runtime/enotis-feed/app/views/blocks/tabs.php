<?php
/** Блок Табы (раздел 5.4): полоса «пилюль» со счётчиком плиток и лента активного таба. */
$tabs = visible_tabs();
$active = active_tab();
?>
<nav class="wrap tabs" data-tabs aria-label="<?= e(t('nav.tabs')) ?>"<?= eid($b['id']) ?>>
<?php if ($tabs): $total = array_sum(array_map(fn($s) => count(tiles_in(root_blocks($s['id']))), $tabs)); ?>
  <a class="tabs__t" href="<?= e(url('/all')) ?>" data-tab="all"<?= ($active['slug'] ?? '') === 'all' ? ' aria-current="page"' : '' ?>>Все <b><?= $total ?></b></a>
<?php endif ?>
<?php foreach ($tabs as $s): ?>
  <a class="tabs__t" href="<?= e(url('/' . $s['slug'])) ?>" data-tab="<?= e($s['slug']) ?>"<?= $active && $active['id'] === $s['id'] ? ' aria-current="page"' : '' ?><?= eid('t' . $s['id']) ?>><?= e($s['title']) ?> <b><?= count(tiles_in(root_blocks($s['id']))) ?></b></a>
<?php endforeach ?>
</nav>
<div class="tabfeed" id="feed" data-tab-feed>
<?= $active ? view('tabfeed', ['tab' => $active, 'popup' => $popup ?? null]) : '' ?>
</div>
