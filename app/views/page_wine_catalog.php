<?php
$all=[];$names=[];
$catalogBlocks=root_blocks(0);foreach(visible_tabs()as$tab)$catalogBlocks=array_merge($catalogBlocks,root_blocks($tab['id']));
foreach($catalogBlocks as $block)if(($block['tiles_kind']??'')==='wine')foreach(visible_children($block)as$wine)if((panel_block_extension($wine)['kind']??'')!=='wine_placeholder'){$names[0]='Без отдельной страницы';$wine['_monastery']=0;$all[]=$wine;}
foreach(tiles_in($catalogBlocks) as $mon){if(is_wine($mon))continue;$names[$mon['id']]=$mon['title'];foreach(visible_children($mon) as $block)if(($block['tiles_kind']??'')==='wine')foreach(visible_children($block) as $wine)if((panel_block_extension($wine)['kind']??'')!=='wine_placeholder'){$wine['_monastery']=$mon['id'];$all[]=$wine;}}
?>
<section class="wrap blk"><h1><?=e(presentation('catalog_title'))?></h1><?php if(presentation('catalog_description')): ?><p><?=e(presentation('catalog_description'))?></p><?php endif ?><div class="wine-catalog-layout"><aside><label><?=e(presentation('catalog_filter'))?><select id="catalog-monastery"><option value="">Все монастыри</option><?php foreach($names as $id=>$name):?><option value="<?=$id?>"><?=e($name)?></option><?php endforeach?></select></label></aside><div>
<?php $b=['id'=>0,'title'=>'','tiles_kind'=>'wine','tiles_size'=>'compact','frame'=>'4:5','section_id'=>null,'parent_id'=>null];echo view('blocks/tiles',['b'=>$b,'tiles'=>$all]);echo view('popups',['blocks'=>$catalogBlocks,'open'=>null,'pageUrl'=>'/catalog']);?>
</div></div></section>
<script>document.getElementById('catalog-monastery').addEventListener('change',function(){var map=<?=json_encode(array_column($all,'_monastery','id'))?>;document.querySelectorAll('.tiles li[data-wine-node]').forEach(function(li){li.hidden=!!this.value&&String(map[li.getAttribute('data-wine-node')])!==this.value;},this);});</script>
