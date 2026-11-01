<?php
$section=$type??($tool??'products');$editing=$editing??false;$lang=$lang??default_lang();$id=$id??0;
$preview='/catalog#catalog';$canPreview=true;
if($editing&&$id&&in_array($section,['products','monasteries','gifts'],true)){
 $record=shop_one(shop_types()[$section][1],$id);
 $canPreview=($record['status']??'')==='published';
 if($record)$preview=['products'=>'/wine/','monasteries'=>'/monastery/','gifts'=>'/gift/'][$section].$record['slug'];
}elseif($section==='gifts')$preview='/gifts';
$title=shop_types()[$section][0]??['orders'=>'Заявки','transfer'=>'Перенос сайта'][$section]??'Магазин';
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?=e($title)?> · Магазин Энотис</title>
<link rel="stylesheet" href="/assets/fonts/fonts.css?v=<?=e(ENGINE_VERSION)?>"><link rel="stylesheet" href="/assets/css/admin.css?v=<?=e(ENGINE_VERSION)?>-ui20261026"><link rel="stylesheet" href="/assets/css/shop-admin.css?v=<?=e(ENGINE_VERSION)?>-ui20261026"><script src="/assets/js/shop-admin.js?v=<?=e(ENGINE_VERSION)?>" defer></script></head>
<body class="app shop"><header class="top"><span class="top__product">№<?=e(PANEL_NUMBER)?></span><a class="top__site" href="/admin/shop"><?=e(site_title())?></a><div class="top__status" data-shop-status><?=isset($_GET['saved'])?'Сохранено':'Магазин'?></div><div class="top__acts"><a class="btn btn--ghost" href="/" target="_blank" rel="noopener">Открыть сайт ↗</a><?php if($editing):?><button class="btn btn--action" type="submit" form="shop-editor" data-top-save>Сохранить</button><?php endif ?><details class="pmenu"><summary class="btn btn--ghost">Мой профиль ▾</summary><div class="pmenu__list"><a class="btn" href="/admin">Профиль и настройки</a><form action="/admin/logout" method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><button>Выйти</button></form></div></details></div></header>
<div class="cols shop-cols"><nav class="tree" aria-label="Разделы панели"><?php panel_nav('shop/'.($section==='products'?'products':$section)); ?><?php if($section==='products'):?><div class="grp"><div class="grp__h">Товары</div><?php foreach(shop_choices('products',$lang) as $entry):?><a class="row" href="/admin/shop/products?id=<?=(int)$entry['id']?>&amp;lang=<?=e($lang)?>"><?=e($entry['name'])?></a><?php endforeach ?><a class="add" href="/admin/shop/products?new=1">+ Добавить товар</a></div><?php endif ?></nav><main class="form shop-form">
