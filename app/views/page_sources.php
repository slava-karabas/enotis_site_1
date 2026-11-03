<?php
$credits=[];
foreach(content_tree()['nodes'] as $n){if($n['type']!=='tile'||!is_visible($n)||($n['section_id']&&!empty(sections()[$n['section_id']]['hidden'])))continue;$ext=panel_block_extension($n);if(($ext['kind']??'')!=='sources')continue;$visible=true;$parent=$n['parent_id'];while($parent){$p=node($parent);if(!is_visible($p)){$visible=false;break;}$parent=$p['parent_id'];}if($visible)$credits[]=['title'=>$n['title'],'slug'=>$n['slug'],'author'=>$ext['author'],'links'=>$ext['links']];}
?>
<article class="wrap doc"><h1>Источники и фотографии</h1>
<?php foreach($credits as $credit): ?><section id="<?=e($credit['slug'])?>" class="source-credit"><h2><?=e($credit['title'])?></h2><p><?=e($credit['author'])?></p><p><?php foreach($credit['links']as$link): ?><a href="<?=e($link)?>" target="_blank" rel="noopener">Источник ↗</a> <?php endforeach ?></p></section><?php endforeach ?>
</article>
