<?php
$heading=$heading??'span';if(!in_array($heading,['span','h1','h2','h3'],true))$heading='span';
$title=$card['title'];
?>
<<?=$heading?> class="wine-card-title"<?=!empty($title_id)?' id="'.e($title_id).'"':''?>><?php if(!empty($title_href)):?><a href="<?=e($title_href)?>" data-shop-details><?php endif?><span class="wine-initial"><?=e(mb_substr($title,0,1))?></span><?=e(mb_substr($title,1))?><?php if(!empty($title_href)):?></a><?php endif?></<?=$heading?>>
<span class="wine-card-facts"><?php foreach($card['facts']as$fact):?><span class="wine-card-fact"><?=e($fact)?></span><?php endforeach?></span>
