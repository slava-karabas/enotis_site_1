<?php
declare(strict_types=1);
$root=__DIR__;
function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function t(string $key):string{return $GLOBALS['lang'][$key]??$key;}
function feed_setting(string $key,string $fallback):string{return $GLOBALS['provider']??$fallback;}
function has_coords(array $t):bool{return isset($t['lat'],$t['lng'])&&is_numeric($t['lat'])&&is_numeric($t['lng'])&&abs((float)$t['lat'])<=90&&abs((float)$t['lng'])<=180;}
function view(string $name,array $vars):string{extract($vars);ob_start();require $GLOBALS['viewRoot'].'/'.$name.'.php';return ob_get_clean();}
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$source=dirname(__DIR__).'/app/lang';
foreach(['shared'] as $core){
 $GLOBALS['viewRoot']=dirname(__DIR__).'/app/views';
 foreach(['ru','en','fr','ka'] as $language){
  $GLOBALS['lang']=require $source.'/'.$language.'.php';
  foreach(['google','yandex'] as $provider){
   $GLOBALS['provider']=$provider;
   foreach([['lat'=>41.871154,'lng'=>44.765492],['lat'=>null,'lng'=>null],['lat'=>0,'lng'=>0]] as $coords){
    $tile=['id'=>40,'title'=>'Место <script>alert(1)</script> & "X"','place'=>"Адрес <b>дома</b>\nВторая строка"]+$coords;
    $out=view('directions-card',['t'=>$tile,'directionsContext'=>'page','directionsHeading'=>2]);
    check(!str_contains($out,'<script>')&&!str_contains($out,'<b>дома</b>'),'Escape content');
    check(str_contains($out,'<h2 class="map__h">'),'Page heading');
    check(str_contains($out,'directions-card__footer'),'Common footer');
    check(!str_contains($out,'map__hint')&&!str_contains($out,'maps/search'),'No duplicate hints or map-open button');
    if(has_coords($tile)){
     preg_match('/data-points="([^"]*)"/',$out,$m);$points=json_decode(html_entity_decode($m[1],ENT_QUOTES,'UTF-8'),true,512,JSON_THROW_ON_ERROR);
     check($points[0]['title']===$tile['title']&&$points[0]['lat']==$tile['lat'],'Exact destination preserved');
     check(str_contains($points[0]['route'],$provider==='google'?'google.com/maps/dir/':'yandex.ru/maps/?rtext='),'Navigation provider');
     check(substr_count($out,'class="btn directions-card__route"')===1,'Exactly one primary route button');
     check(strpos($out,'data-place-map')<strpos($out,'directions-card__footer'),'Map before footer');
     $popup=view('directions-card',['t'=>$tile,'directionsContext'=>'popup']);
     check(str_contains($out,'directions-map-page-40')&&str_contains($popup,'directions-map-popup-40'),'Distinct page/popup map IDs');
    }else check(!str_contains($out,'data-map')&&!str_contains($out,'href='),'No fake map or route without coordinates');
   }
  }
 }
 echo "PASS core {$core}: 4 languages, 2 navigation services, coordinates/zero/missing, escaping, map-first layout, single route, distinct IDs\n";
}
