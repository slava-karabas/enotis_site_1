<?php
$all=[];$names=[];
foreach(visible_tabs() as $tab)foreach(tiles_in(root_blocks($tab['id'])) as $mon){if(is_wine($mon))continue;$names[$mon['id']]=$mon['title'];foreach(visible_children($mon) as $block)if(($block['tiles_kind']??'')==='wine')foreach(visible_children($block) as $wine)if((panel_block_extension($wine)['kind']??'')!=='wine_placeholder'){$wine['_monastery']=$mon['id'];$all[]=$wine;}}
?>
<section class="wrap blk"><h1>Каталог вин</h1><p>Документированные вина и сортовые линии. Текущая доступность и точные фотографии части позиций уточняются.</p><div class="wine-catalog-layout"><aside><label>Монастырь<select id="catalog-monastery"><option value="">Все монастыри</option><?php foreach($names as $id=>$name):?><option value="<?=$id?>"><?=e($name)?></option><?php endforeach?></select></label></aside><div>
<?php $b=['id'=>0,'title'=>'','tiles_kind'=>'wine','tiles_size'=>'compact','frame'=>'4:5','section_id'=>null,'parent_id'=>null];echo view('blocks/tiles',['b'=>$b,'tiles'=>$all]);echo view('popups',['blocks'=>array_merge(...array_map(fn($tab)=>root_blocks($tab['id']),visible_tabs())),'open'=>null,'pageUrl'=>'/catalog']);?>
</div></div></section>
<script>document.getElementById('catalog-monastery').addEventListener('change',function(){var map=<?=json_encode(array_column($all,'_monastery','id'))?>;document.querySelectorAll('.tiles li[data-wine-node]').forEach(function(li){li.hidden=!!this.value&&String(map[li.getAttribute('data-wine-node')])!==this.value;},this);});</script>
