<?php
declare(strict_types=1);
const PRESENTATION_DEFAULTS = ['catalog_title'=>'Каталог вин','catalog_description'=>'','catalog_filter'=>'Производитель','description'=>'','social_heading'=>'Смотрите и подписывайтесь','contact_heading'=>'Для связи с проектом','copyright'=>'','menu_home'=>'Главная','menu_about'=>'О проекте','menu_catalog'=>'Каталог вин','menu_social'=>'Соцсети','menu_contact'=>'Связь с проектом','social_networks'=>'youtube,instagram,tiktok,telegram','messenger_networks'=>'whatsapp,viber,telegram','menu_sections'=>'0','page_layout'=>'editorial'];
function presentation_settings(): array { $v=json_decode((string)setting('presentation','{}'),true); return array_replace(PRESENTATION_DEFAULTS,is_array($v)?array_intersect_key($v,PRESENTATION_DEFAULTS):[]); }
function presentation(string $key): string { return (string)(presentation_settings()[$key]??''); }
function presentation_networks(string $key): array { return array_values(array_intersect(array_map('trim',explode(',',presentation($key))),array_keys(SOCIAL_NAMES))); }

/** Real destinations shared by header and footer; never import a guide's content. */
function presentation_navigation(): array
{
    $items = [];
    if (presentation('menu_home') !== '') $items[] = ['title'=>presentation('menu_home'), 'url'=>url('/')];
    if (presentation('menu_sections') === '1') {
        foreach (tiles_in(home_feed_blocks()) as $tile) {
            if (!is_wine($tile) && tile_opens($tile) === 'page') {
                $items[] = ['title'=>$tile['title'], 'url'=>tile_url($tile)];
            }
        }
    }
    if (presentation('menu_about') !== '') {
        foreach (home_feed_blocks() as $block) {
            if ($block['type'] === 'text' && is_visible($block) && !empty($block['title'])) {
                $items[] = ['title'=>presentation('menu_about'), 'url'=>url('/').'#about'];
                break;
            }
        }
    }
    if (setting('footer_show', '1') === '1') {
        foreach (['menu_social'=>'footer-social','menu_contact'=>'footer-contact'] as $key=>$anchor) {
            if (presentation($key) !== '') $items[] = ['title'=>presentation($key), 'url'=>url('/').'#'.$anchor];
        }
    }
    return $items;
}
