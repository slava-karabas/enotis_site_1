<?php
$val=static fn(string $k,string $default=''):string=>isset($row[$k])&&is_scalar($row[$k])?(string)$row[$k]:$default;
$statusNames=['draft'=>'Черновик','published'=>'Опубликовано','archived'=>'Архив'];
$labels=['category'=>'Категория','name'=>'Название','code'=>'Код страны','position'=>'Порядок','country_id'=>'Страна','monastery_id'=>'Производитель','slug'=>'Адрес (латиницей)','sku'=>'Артикул','vintage'=>'Год урожая','volume_ml'=>'Объём, мл','grape'=>'Сорт винограда','description'=>'Описание'];
$input=static function(string $key,string $label,string $value,string $type='text',bool $required=false):void { ?>
<label class="sf"><span><?= e($label) ?><?= $required?' *':'' ?></span><input name="<?= e($key) ?>" type="<?= e($type) ?>" value="<?= e($value) ?>" <?= $required?'required':'' ?> <?= $type==='number'?'min="0" step="1"':'' ?> maxlength="160"></label>
<?php };
$select=static function(string $key,string $label,array $options,string $selected,bool $blank=false):void { ?>
<label class="sf"><span><?= e($label) ?></span><select name="<?= e($key) ?>"><?php if($blank): ?><option value="">Не выбрано</option><?php endif ?><?php foreach($options as $key2=>$label2): ?><option value="<?= e((string)$key2) ?>" <?= (string)$key2===$selected?'selected':'' ?>><?= e((string)$label2) ?></option><?php endforeach ?></select></label>
<?php };
?>
<?php require __DIR__.'/shop-shell.php'; ?>
<div class="page-head"><div><h1><?= e($c[0]) ?></h1><p><?= $editing?($id?'Редактирование записи':'Новая запись'):'Всего: '.e((string)$total) ?></p></div><?php if(!$editing): ?><a class="button primary" href="/admin/shop/<?= e($type) ?>?new=1&amp;lang=<?= e($lang) ?>">Добавить</a><?php else: ?><a class="button" href="/admin/shop/<?= e($type) ?>?lang=<?= e($lang) ?>">К списку</a><?php endif ?></div>
<div class="languages" aria-label="Язык содержимого"><?php foreach(LANGS as $l): ?><a href="/admin/shop/<?= e($type) ?>?lang=<?= e($l) ?><?= $id?'&amp;id='.$id:($editing?'&amp;new=1':'') ?>" <?= $l===$lang?'aria-current="true"':'' ?>><?= e(strtoupper($l)) ?></a><?php endforeach ?><span>Язык содержимого</span></div>
<?php if($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php elseif(isset($_GET['saved'])): ?><div class="notice" role="status">Изменения сохранены.</div><?php endif ?>
<?php if(!$editing): ?>
<form class="search" method="get"><input type="hidden" name="lang" value="<?= e($lang) ?>"><label for="q">Найти по названию или коду</label><div><input id="q" name="q" value="<?= e($q) ?>"><button class="button">Найти</button><?php if($q!==''): ?><a href="/admin/shop/<?= e($type) ?>?lang=<?= e($lang) ?>">Сбросить</a><?php endif ?></div></form>
<div class="table-wrap"><table><thead><tr><th>Название</th><th><?= in_array($type,['products','options','gifts'],true)?'Цена':'Код / адрес' ?></th><th>Статус</th><th><span class="sr">Действие</span></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['name']) ?><?php if($type==='products'): ?><small><?= e($r['sku']) ?></small><?php endif ?></td><td class="numeric"><?php if($type==='gifts'): ?><?= e(shop_money(shop_gift_total((int)$r['id']))) ?><?php elseif(isset($r['price_minor'])): ?><?= e(shop_money((int)$r['price_minor'])) ?><?php else: ?><?= e($r['code']??$r['slug']??'') ?><?php endif ?></td><td><span class="status"><?= e(isset($r['status'])?$statusNames[$r['status']]:(isset($r['active'])?($r['active']?'Включено':'Отключено'):'—')) ?></span></td><td><a href="/admin/shop/<?= e($type) ?>?id=<?= (int)$r['id'] ?>&amp;lang=<?= e($lang) ?>">Редактировать<span class="sr"> <?= e($r['name']) ?></span></a></td></tr><?php endforeach ?>
<?php if(!$rows): ?><tr><td colspan="4" class="empty"><?= $q!==''?'Ничего не найдено. Измените запрос.':'Здесь пока нет записей. Нажмите «Добавить», чтобы начать.' ?></td></tr><?php endif ?></tbody></table></div>
<?php if($total>50): ?><div class="pages"><?php if($page>1): ?><a href="?lang=<?= e($lang) ?>&amp;q=<?= e(rawurlencode($q)) ?>&amp;page=<?= $page-1 ?>">Назад</a><?php endif ?><span>Страница <?= $page ?> из <?= (int)ceil($total/50) ?></span><?php if($page*50<$total): ?><a href="?lang=<?= e($lang) ?>&amp;q=<?= e(rawurlencode($q)) ?>&amp;page=<?= $page+1 ?>">Далее</a><?php endif ?></div><?php endif ?>
<?php else: ?>
<form id="shop-editor" method="post" class="edit-form" data-edit><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="id" value="<?= $id?:'' ?>">
<section class="form-panel"><h2>Основное</h2><div class="fields">
<?php $input('name','Название · '.strtoupper($lang),$val('name'),'text',true); ?>
<?php foreach($c[4] as $key): ?>
<?php if(in_array($key,['photo_id','available','collectible','active'],true)): continue; ?>
<?php elseif($key==='status'): $select($key,'Статус',$statusNames,$val($key,'draft')); ?>
<?php elseif($key==='monastery_id'&&$type==='products'):
$producer=isset($row['monastery_id'])?shop_one('shop_monasteries',(int)$row['monastery_id']):null;
$country=$producer?shop_one('shop_countries',(int)$producer['country_id']):null;
$names=array_column(shop_choices('monasteries',$lang),'name','id');$countries=array_column(shop_choices('countries',$lang),'name','id');
$input('producer_name','Производитель',$val('producer_name',$producer?($names[$producer['id']]??''):''),'text',true);
$input('country_name','Страна',$val('country_name',$country?($countries[$country['id']]??''):''),'text',true);
$input('country_code','Код страны (GE, FR…)',$val('country_code',$country['code']??''),'text',true); ?>
<?php elseif($key==='country_id'||$key==='monastery_id'): $opts=array_column(shop_choices($key==='country_id'?'countries':'monasteries',$lang),'name','id');$select($key,$labels[$key],$opts,$val($key),true); ?>
<?php elseif($key==='packaging_id'): $opts=[];foreach(shop_choices('options',$lang) as $o){if($o['kind']==='packaging'){$opts[$o['id']]=$o['name'].' · '.shop_money((int)$o['price_minor']).(!$o['active']?' · отключено':'');}}$select($key,'Упаковка',$opts,$val($key),true); ?>
<?php elseif($key==='beverage_type'): $select($key,'Вид напитка',['wine'=>'Вино','fortified'=>'Креплёное вино','spirit'=>'Крепкий напиток'],$val($key,'wine')); ?>
<?php elseif($key==='color'): $select($key,'Цвет',['red'=>'Красное','white'=>'Белое','rose'=>'Розовое','amber'=>'Янтарное'],$val($key),true); ?>
<?php elseif($key==='sweetness'): $select($key,'Содержание сахара',['dry'=>'Сухое','semi_dry'=>'Полусухое','semi_sweet'=>'Полусладкое','sweet'=>'Сладкое'],$val($key),true); ?>
<?php elseif($key==='kind'): $select($key,'Тип',['packaging'=>'Упаковка','extra'=>'Дополнительное оформление'],$val($key,'packaging')); ?>
<?php elseif($key==='price_minor'): $input('price','Цена, €',isset($row['price'])?$val('price'):shop_decimal_input((int)$val('price_minor','0')),'text',true); ?>
<?php elseif($key==='alcohol_basis_points'): $input('alcohol','Крепость, %',isset($row['alcohol'])?$val('alcohol'):($val($key)!==''?shop_decimal_input((int)$val($key)):'')); ?>
<?php else: $input($key,$labels[$key]??$key,$val($key,$key==='position'?'0':''),in_array($key,['position','vintage','volume_ml'],true)?'number':'text',in_array($key,['sku','slug','code'],true)); ?>
<?php endif ?><?php endforeach ?>
<?php if($type==='products'): $input('grape','Сорт винограда · '.strtoupper($lang),$val('grape')); endif ?>
</div>
<?php if(in_array('description',$c[5],true)): ?><label class="sf wide"><span>Описание · <?= e(strtoupper($lang)) ?></span><textarea name="description" rows="5" maxlength="6000"><?= e($val('description')) ?></textarea></label><?php endif ?>
<div class="checks"><?php foreach(['available'=>'Доступно к заказу','collectible'=>'Коллекционное вино','active'=>'Включено'] as $key=>$label): if(!in_array($key,$c[4],true))continue; ?><label><input type="checkbox" name="<?= e($key) ?>" value="1" <?= $val($key,($key==='collectible'?'0':'1'))==='1'?'checked':'' ?>><?= e($label) ?></label><?php endforeach ?></div>
</section>
<?php if(in_array('photo_id',$c[4],true)): ?><section class="form-panel"><h2>Фотография</h2><div class="photo-row"><div><?php $opts=[];foreach($photos as $p){$opts[$p['id']]='#'.$p['id'].' · '.$p['original_path'];}$select('photo_id','Выбрать из библиотеки',$opts,$val('photo_id'),true); ?><label class="sf"><span>Загрузить JPEG, PNG или WebP, до 20 МБ</span><input type="file" accept="image/jpeg,image/png,image/webp" data-photo-upload></label><input type="hidden" name="photo_source" value="<?=e($val('photo_source'))?>"><div class="checks"><?php if(photo_service_ready()):?><label>Режим ИИ<select data-photo-mode><option value="enhance">Улучшить качество</option><option value="studio">Студийное фото</option></select></label><button class="button" type="button" data-photo-process>ИИ-обработка</button><?php else:?><a href="/admin#set:advanced">Подключить ИИ-обработку</a><?php endif ?><button class="button" type="button" data-photo-original>Оставить оригинал</button></div><p class="hint">Результат обработки можно сравнить с оригиналом. Выбор применяется после сохранения карточки. Обработка отправляет фото в подключённый ИИ-сервис.</p><p data-photo-message role="status"></p></div><img data-photo-preview src="<?= $val('photo_id')!==''?'/admin/api/original?id='.(int)$val('photo_id'):'' ?>" alt="Выбранная фотография" <?= $val('photo_id')===''?'hidden':'' ?>></div></section><?php endif ?>
<?php if($type==='gifts'): ?><section class="form-panel"><h2>Состав подарка</h2><p class="muted">Укажите количество бутылок. Пустое поле или 0 — вино не входит в набор.</p><div class="gift-items"><?php foreach(shop_choices('products',$lang) as $p): ?><label class="gift-line"><span><?= e($p['name']) ?><small><?= e($p['sku']) ?> · <?= e(shop_money((int)$p['price_minor'])) ?> · <?= e($statusNames[$p['status']]) ?></small></span><input aria-label="Количество: <?= e($p['name']) ?>" type="number" min="0" max="99" name="items[<?= (int)$p['id'] ?>]" value="<?= e(isset($items[$p['id']])&&is_scalar($items[$p['id']])?(string)$items[$p['id']]:'') ?>"></label><?php endforeach ?></div><?php if(!shop_choices('products',$lang)): ?><p>Сначала добавьте вина в каталог.</p><?php endif ?>
<h3>Дополнительное оформление</h3><div class="checks"><?php foreach(shop_choices('options',$lang) as $o): if($o['kind']!=='extra')continue; ?><label><input type="checkbox" name="extras[<?= (int)$o['id'] ?>]" value="1" <?= isset($extras[$o['id']])?'checked':'' ?>><?= e($o['name']) ?> · <?= e(shop_money((int)$o['price_minor'])) ?><?= !$o['active']?' · отключено':'' ?></label><?php endforeach ?></div>
<?php if($id&&!$error): ?><p class="total">Текущая стоимость: <strong><?= e(shop_money(shop_gift_total($id))) ?></strong></p><?php else: ?><p class="muted">Итоговая стоимость появится после сохранения состава.</p><?php endif ?></section><?php endif ?>
<input type="hidden" name="form_complete" value="1"><div class="save-bar"><button class="button primary" type="submit">Сохранить</button><a href="/admin/shop/<?= e($type) ?>?lang=<?= e($lang) ?>">Отмена</a><span>Общие поля действуют для всех языков; текст — только для <?= e(strtoupper($lang)) ?>.</span></div>
</form><?php endif ?>
<?php require __DIR__.'/shop-shell-end.php'; ?>
