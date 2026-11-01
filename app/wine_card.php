<?php
declare(strict_types=1);
/** Canonical presentation shared by the shop, editorial tiles and their popups. */
function wine_card_data(array $w, bool $native=false): array {
    $unknown='Тип и цвет';$source=$w;
    if($native){$q=db()->prepare('SELECT id FROM elements WHERE slug=? LIMIT 1');$q->execute([$w['sku']??$w['slug']??'']);$id=$q->fetchColumn();$source=$id?node((int)$id):null;}
    $placeholder=$source&&(panel_block_extension($source)['kind']??'')==='wine_placeholder';
    if($source){
        $parts=preg_split('/\s*·\s*/u',(string)($source['subtitle']??''),2);
        $country=$parts[0]??'';$producer=$parts[1]??'';
        $sweet=$source['wine_sweet']??'';$color=$source['wine_color']??'';$grape=$source['grape']??'';$year=$source['vintage']??'';
        $title=$placeholder?'Название вина':($source['title']??'');$slug=$source['slug']??'';
    }else{
        $countries=array_column(shop_choices('countries',current_lang()),'name','id');$country=$countries[$w['country_id']??0]??'';$producer=$w['monastery_name']??'';
        $sweet=$w['sweetness']??'';$color=$w['color']??'';$grape=$w['grape']??'';$year=$w['vintage']??'';$title=$w['name']??'';$slug=$w['slug']??'';
    }
    $colors=['red'=>'красное','white'=>'белое','rose'=>'розовое','amber'=>'янтарное'];$sugars=['dry'=>'сухое','semi_dry'=>'полусухое','semidry'=>'полусухое','semi_sweet'=>'полусладкое','semisweet'=>'полусладкое','sweet'=>'сладкое'];
    $sweet=$sugars[$sweet]??'';$color=$colors[$color]??'';
    $type=$sweet&&$color?$sweet.', '.$color:($sweet?$sweet.', цвет уточняется':($color?'сладость уточняется, '.$color:$unknown));
    if($placeholder){$country='Страна';$producer='Монастырь';$grape='Сорт';$year='Год';$type='Тип и цвет';}
    $text=fn($v)=>trim((string)$v)!==''?trim((string)$v):$unknown;
    $imagePhoto=$source['photo']??null;
    if($native&&!empty($w['photo_id'])){$q=db()->prepare('SELECT * FROM photos WHERE id=?');$q->execute([(int)$w['photo_id']]);$imagePhoto=$q->fetch()?:null;}
    $demo=$placeholder||wine_photo_demo($imagePhoto);
    return ['title'=>$title?:'Название вина','facts'=>[$country?:'Страна',$producer?:'Монастырь',$type,$grape?:'Сорт',$year?:'Год'],'demo'=>$demo,'placeholder'=>$placeholder];
}
