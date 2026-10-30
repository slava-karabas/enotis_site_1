<?php
declare(strict_types=1);
require_once __DIR__.'/contacts.php';
function panel_extension(mixed $v, string $type='text'): array {
    if($v===null||$v===[])return [];
    if(!is_array($v)||strlen(json_encode($v))>32000)throw new InvalidArgumentException('Некорректная настройка блока');
    $kind=$v['kind']??'';
    if($kind==='contacts'){if($type!=='text')throw new InvalidArgumentException('Контакты должны быть отдельным блоком');return panel_contacts_config($v);}
    if(!in_array($kind,['products','gift','filters','values'],true)||PANEL_NUMBER!=='2'&&in_array($kind,['products','gift'],true))throw new InvalidArgumentException('Этот блок недоступен');
    $out=['kind'=>$kind];
    foreach(['ids','labels','values'] as $key)if(isset($v[$key])){
        if(!is_array($v[$key])||count($v[$key])>100)throw new InvalidArgumentException('Слишком много вариантов');
        $out[$key]=[];foreach($v[$key] as $x){if($key==='ids'){if(!is_int($x)||$x<1)throw new InvalidArgumentException('Неверный товар');$out[$key][]=$x;}else{if(!is_string($x)||mb_strlen($x)>80)throw new InvalidArgumentException('Название слишком длинное');$out[$key][]=trim($x);}}
    }
    foreach(['box_minor','card_minor'] as $k)if(isset($v[$k])){if(!is_int($v[$k])||$v[$k]<0||$v[$k]>10000000)throw new InvalidArgumentException('Неверная цена');$out[$k]=$v[$k];}
    $out['limit']=max(1,min(100,(int)($v['limit']??12)));$out['filter']=!empty($v['filter']);
    $out['all']=mb_substr(is_string($v['all']??null)?$v['all']:'Все',0,40);
    return $out;
}
function panel_block_extension(array $row): array {return json_decode($row['panel_json']??'[]',true)?:[];}
function panel_shop_block(array $b,array $config): void {
    $rows=shop_public_products(current_lang());$ids=$config['ids']??[];if($ids)$rows=array_filter($rows,fn($p)=>in_array((int)$p['id'],$ids,true));
    $rows=array_slice(array_values($rows),0,$config['limit']??12);$title=$b['title']?:($config['kind']==='gift'?'Подарочный конструктор':'Подборка товаров');
    ?>
<section class="wrap blk panel-shop" data-panel-products><h2><?=e($title)?></h2>
<?php if($config['kind']==='products'): ?>
<?php if(!empty($config['filter'])):?><label>Поиск <input type="search" data-panel-search></label><?php endif ?>
<div class="cards"><?php foreach($rows as $p):?><article class="card" data-panel-card data-name="<?=e(mb_strtolower($p['name']))?>"><div class="card-content"><?php if($url=shop_image($p['photo_id']?(int)$p['photo_id']:null)):?><img src="<?=e($url)?>" alt="<?=e($p['name'])?>" style="width:100%;height:220px;object-fit:contain"><?php endif ?><h3><a href="<?=e(shop_link('/wine/'.$p['slug']))?>"><?=e($p['name'])?></a></h3><p><?=e(shop_money((int)$p['price_minor']))?></p><?php if($p['available']):?><form action="<?=e(shop_link('/cart'))?>" method="post"><input type="hidden" name="csrf" value="<?=e($_SESSION['shop_csrf'])?>"><input type="hidden" name="action" value="add"><input type="hidden" name="key" value="p:<?=(int)$p['id']?>"><input type="hidden" name="quantity" value="1"><button class="button primary">В корзину</button></form><?php endif ?></div></article><?php endforeach ?></div>
<?php else:?>
<form action="<?=e(shop_link('/cart'))?>" method="post" data-gift-form><input type="hidden" name="csrf" value="<?=e($_SESSION['shop_csrf'])?>"><input type="hidden" name="action" value="custom_gift"><input type="hidden" name="block_id" value="<?=(int)$b['id']?>">
<?php foreach($rows as $p):if(!$p['available'])continue;?><label style="display:block;margin:12px 0"><input type="checkbox" name="products[]" value="<?=(int)$p['id']?>" data-gift-price="<?=(int)$p['price_minor']?>"> <?=e($p['name'])?> · <?=e(shop_money((int)$p['price_minor']))?></label><?php endforeach ?>
<label style="display:block;margin:12px 0"><input type="checkbox" name="box" value="1" data-gift-price="<?=(int)($config['box_minor']??0)?>"> Подарочная коробка · <?=e(shop_money($config['box_minor']??0))?></label>
<label style="display:block;margin:12px 0"><input type="checkbox" name="card" value="1" data-gift-price="<?=(int)($config['card_minor']??0)?>"> Открытка · <?=e(shop_money($config['card_minor']??0))?></label><p>Итого: <strong data-gift-total>0,00 €</strong></p><button class="button primary">Добавить набор в корзину</button><p class="muted">Окончательную сумму проверяет сервер.</p></form>
<?php endif ?></section>
<?php }
function panel_gift_config(int $id): array {
    $st=db()->prepare("SELECT * FROM elements WHERE id=? AND hidden=0 AND type='text'");$st->execute([$id]);$b=$st->fetch();
    if(!$b)throw new InvalidArgumentException('Конструктор больше недоступен');
    $cfg=panel_block_extension($b);if(($cfg['kind']??'')!=='gift')throw new InvalidArgumentException('Конструктор не найден');
    $section=$b['section_id']??null;if($section){$st=db()->prepare('SELECT hidden FROM sections WHERE id=?');$st->execute([$section]);$hidden=$st->fetchColumn();if($hidden===false||$hidden)throw new InvalidArgumentException('Вкладка конструктора скрыта');}
    $parent=$b['parent_id'];while($parent){$st=db()->prepare('SELECT parent_id,hidden FROM elements WHERE id=?');$st->execute([$parent]);$p=$st->fetch();if(!$p||$p['hidden'])throw new InvalidArgumentException('Страница конструктора скрыта');$parent=$p['parent_id'];}
    return [$b,$cfg];
}
function panel_custom_gift_line(array $spec,int $qty): array {
    [$b,$cfg]=panel_gift_config((int)$spec['block']);$products=shop_public_products(current_lang());$parts=[];$sum=0;
    $ids=$spec['products']??[];if(!$ids||count($ids)>30)throw new InvalidArgumentException('Выберите от 1 до 30 товаров');
    foreach($ids as $id){$p=$products[$id]??null;if(!$p||!$p['available']||!empty($cfg['ids'])&&!in_array((int)$id,$cfg['ids'],true))throw new InvalidArgumentException('Товар набора недоступен');$sum+=(int)$p['price_minor'];$parts[]=['product_id'=>$p['id'],'option_id'=>null,'kind'=>'product','sku'=>$p['sku'],'name'=>'Набор / '.$p['name'],'quantity'=>$qty,'price'=>(int)$p['price_minor']];}
    foreach(['box'=>'box_minor','card'=>'card_minor'] as $key=>$priceKey)if(!empty($spec[$key])){$price=(int)($cfg[$priceKey]??0);$sum+=$price;$parts[]=['product_id'=>null,'option_id'=>null,'kind'=>$key==='box'?'packaging':'extra','sku'=>'','name'=>$key==='box'?'Подарочная коробка':'Открытка','quantity'=>$qty,'price'=>$price];}
    if($sum>999999999)throw new InvalidArgumentException('Сумма набора слишком большая');
    return ['key'=>'c:'.hash('sha256',json_encode($spec)),'name'=>'Подарочный набор','quantity'=>$qty,'price'=>$sum,'parts'=>$parts];
}
