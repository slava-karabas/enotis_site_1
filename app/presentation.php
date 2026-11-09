<?php
declare(strict_types=1);
const PRESENTATION_DEFAULTS = ['catalog_title'=>'Каталог вин','catalog_description'=>'','catalog_filter'=>'Производитель','description'=>'','social_heading'=>'Смотрите и подписывайтесь','contact_heading'=>'Для связи с проектом','copyright'=>'','menu_home'=>'Все монастыри','menu_about'=>'О проекте','menu_catalog'=>'Каталог вин','menu_social'=>'Соцсети','menu_contact'=>'Связь с проектом','social_networks'=>'youtube,instagram,tiktok,telegram','messenger_networks'=>'whatsapp,viber,telegram'];
function presentation_settings(): array { $v=json_decode((string)setting('presentation','{}'),true); return array_replace(PRESENTATION_DEFAULTS,is_array($v)?array_intersect_key($v,PRESENTATION_DEFAULTS):[]); }
function presentation(string $key): string { return (string)(presentation_settings()[$key]??''); }
function presentation_networks(string $key): array { return array_values(array_intersect(array_map('trim',explode(',',presentation($key))),array_keys(SOCIAL_NAMES))); }
