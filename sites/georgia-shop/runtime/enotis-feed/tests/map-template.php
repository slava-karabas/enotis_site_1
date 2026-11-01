<?php
declare(strict_types=1);
// Contract checks for the actual server template, without a database or client data.
function check(bool $ok, string $why): void { if (!$ok) throw new RuntimeException($why); }
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function t(string $key): string { return $GLOBALS['map_lang'][$key] ?? throw new RuntimeException('Missing translation: '.$key); }
function feed_setting(string $key, string $fallback): string { return $GLOBALS['map_service']; }
function map_points(array $b): array { return $GLOBALS['map_rows']; }
function tile_opens(array $t): ?string { return $t['id'] === 7 ? 'popup' : null; }
function tile_url(array $t): string { return '/place/'.$t['id']; }
function eid(mixed $id): string { return ''; }
$GLOBALS['map_rows'] = [['id'=>7,'lat'=>42.0325,'lng'=>45.3771,'title'=>'Алаверди <script> & "место"'],['id'=>8,'lat'=>41.937,'lng'=>45.381,'title'=>'Икалто']];
foreach (['ru','en','fr','ka'] as $lang) {
    $GLOBALS['map_lang'] = require dirname(__DIR__).'/app/lang/'.$lang.'.php';
    foreach (['google','yandex'] as $service) {
        $GLOBALS['map_service'] = $service;
        $b = ['id'=>41,'title'=>''];
        ob_start(); require dirname(__DIR__).'/app/views/blocks/map.php'; $out = ob_get_clean();
        check(str_contains($out, 'aria-controls="map-block-41"'), 'Expand control must target its own map');
        check(str_contains($out, 'aria-expanded="false" hidden'), 'Progressive enhancement must hide the control initially');
        check(!str_contains($out, '<script>'), 'Place titles must be escaped in HTML');
        preg_match('/data-points="([^"]*)"/', $out, $m);
        $points = json_decode(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR);
        check(count($points)===2 && $points[0]['title']===$GLOBALS['map_rows'][0]['title'], 'Point title must survive safe encoding');
        check($points[0]['url']==='/place/7' && $points[0]['popup']===7, 'Existing popup destination must survive');
        check($points[1]['url']===null && $points[1]['route']!=='', 'A place without details must still have directions');
        check(str_starts_with($points[0]['route'], $service==='google'?'https://www.google.com/maps/dir/?':'https://yandex.ru/maps/?'), 'Respect the feed navigation provider');
        check(str_contains($out, 'class="map__list"') && str_contains($out, '/place/7'), 'Keep no-JS navigation');
        foreach (['map.expand','map.collapse','map.more','map.title','map.route'] as $key) check(t($key)!=='', 'Translated labels are required');
    }
}
echo "PASS: actual map template, four languages, Google/Yandex routes, safe titles, details/no-details, unique expand target and no-JS fallback\n";
