<?php
/** Лента таба с её поп-апами — и на странице, и во фрагменте для переключения без перезагрузки. */
if ($tab['id'] === -1) {
    $tiles = [];
    foreach (visible_tabs() as $region) {
        foreach (tiles_in(root_blocks($region['id'])) as $item) {
            if (!is_wine($item)) $tiles[] = $item;
        }
    }
    $b = ['id' => 0, 'title' => '', 'tiles_kind' => 'normal', 'tiles_size' => 'large',
          'frame' => '3:2', 'section_id' => null, 'parent_id' => null];
    echo view('blocks/tiles', ['b' => $b, 'tiles' => $tiles, 'aggregate' => true]);
    return;
}
$blocks = root_blocks($tab['id']);
$prev = feed($tab['id']);
echo view('blocks', ['blocks' => $blocks]);
echo view('popups', ['blocks' => $blocks, 'open' => $popup, 'pageUrl' => url('/' . $tab['slug'])]);
feed($prev);
