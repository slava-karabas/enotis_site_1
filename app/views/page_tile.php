<?php
$GLOBALS['h1_done'] = true;
$t = $tile;
$hasPlace = $t['place'] || has_coords($t);
$links=[];
if ($t['photo'] || trim($t['body'] ?? '') !== '') $links[]=['href'=>'#place-about','title'=>t('place.about')];
foreach ($blocks as &$block) {
    if (!is_visible($block)) continue;
    $block['_page_anchor']=true;
    if (!in_array($block['type'],['text','photo','tiles','map'],true)) continue;
    $label=$block['title'] ?: ((panel_block_extension($block)['kind']??'')==='contacts'?t('contacts.title'):'');
    if ($label) $links[]=['href'=>'#block-'.(int)$block['id'],'title'=>$label];
}
unset($block);
if ($hasPlace) $links[]=['href'=>'#directions','title'=>t('tile.how_to_get')];
?>
<article class="tpage">
<?= view('place_intro', ['id'=>$t['id'], 'title'=>$t['title'], 'body'=>$t['body'],
    'links'=>$links, 'eyebrow'=>implode(' · ',array_filter([$tab['title']??null,$t['subtitle']])),
    'photoHtml'=>$t['photo']?photo_img($t['photo'],$t['title'],false,true,'(min-width: 1440px) 674px, (min-width: 1024px) calc((100vw - 92px) / 2), (min-width: 600px) calc(100vw - 56px), calc(100vw - 32px)'):'']) ?>
  <div class="feed">
<?= view('blocks', ['blocks' => $blocks]) ?>
  </div>
<?php if ($hasPlace): ?>
  <div id="directions" class="tpage__place">
<?php if (has_coords($t)): ?>
<?= view('blocks/map', ['b'=>['id'=>(int)$t['id'],'title'=>t('tile.how_to_get')], 'mapPlaces'=>[$t], 'mapAddress'=>$t['place'], 'mapId'=>'map-place-'.(int)$t['id']]) ?>
<?= view('place_route_buttons',['t'=>$t]) ?>
<?php elseif ($t['place']): ?>
<section class="wrap blk"><h2><?= e(t('tile.how_to_get')) ?></h2><p class="place__address"><?= e($t['place']) ?></p></section>
<?php endif ?>
  </div>
<?php endif ?>
</article>
<?= view('popups', ['blocks' => $blocks, 'open' => $popup, 'pageUrl' => url(feed_prefix($t['section_id']) . '/' . $t['slug'])]) ?>
