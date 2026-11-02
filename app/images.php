<?php
declare(strict_types=1);

/**
 * Нарезка фото (спецификация 2.1, раздел 10.6). Исходники — в data/uploads/originals/,
 * готовые размеры — в public/uploads/photos/. Размер режется при первом запросе
 * и дальше отдаётся веб-сервером как обычный файл.
 */

const ORIGINALS_DIR = DATA_DIR . '/uploads/originals';
const PUBLIC_UPLOADS = ROOT_DIR . '/public/uploads';

const PHOTO_SIZES = [
    '3:2' => ['l' => [1600, 1067], 'm' => [800, 533], 's' => [400, 267]],
    '4:5' => ['l' => [1200, 1500], 'm' => [600, 750], 's' => [300, 375]],
];

/** Подпись кадра: меняется вместе с кадром, поэтому старые файлы не попадают из кэша браузера. */
function photo_hash(array $p, bool $head): string
{
    $src = (int) $p['use_processed'] === 1 && $p['processed_path'] ? $p['processed_path'] : $p['original_path'];
    $key = $head
        ? [$src, $p['rotate'], $p['head_crop_x'], $p['head_crop_y'], $p['head_crop_w'], $p['head_crop_h']]
        : [$src, $p['aspect'], $p['rotate'], $p['crop_x'], $p['crop_y'], $p['crop_w'], $p['crop_h']];
    return substr(md5(json_encode($key)), 0, 8);
}

/** Пути всех размеров фото: ['l' => 'photos/7-ab12cd34-l.jpg', …, 'hl' => …]. */
function photo_paths(array $p): array
{
    $out = [];
    foreach (['l', 'm', 's'] as $v) {
        $out[$v] = sprintf('photos/%d-%s-%s.jpg', $p['id'], photo_hash($p, false), $v);
        $out['h' . $v] = sprintf('photos/%d-%s-h%s.jpg', $p['id'], photo_hash($p, true), $v);
    }
    return $out;
}

/** Записать в photos.path_* и head_path_* актуальные имена файлов. */
function photo_store_paths(int $id): void
{
    $st = db()->prepare('SELECT * FROM photos WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();
    if (!$p) {
        return;
    }
    $paths = photo_paths($p);
    db()->prepare('UPDATE photos SET path_l = ?, path_m = ?, path_s = ?, head_path_l = ?, head_path_m = ?, head_path_s = ? WHERE id = ?')
        ->execute([$paths['l'], $paths['m'], $paths['s'], $paths['hl'], $paths['hm'], $paths['hs'], $id]);
}

/**
 * Запрос /uploads/photos/{id}-{hash}-{размер}.jpg, которого ещё нет на диске:
 * режем из исходника и сохраняем. Чужой hash (кадр уже сменили) — 404.
 */
function serve_missing_photo(string $path): bool
{
    if (!preg_match('~^/uploads/photos/(\d+)-([0-9a-f]{8})-(h?)([lms])\.jpg$~', $path, $m)) {
        return false;
    }
    $st = db()->prepare('SELECT * FROM photos WHERE id = ?');
    $st->execute([(int) $m[1]]);
    $p = $st->fetch();
    $head = $m[3] === 'h';
    if (!$p || photo_hash($p, $head) !== $m[2]) {
        return false;
    }
    $file = PUBLIC_UPLOADS . substr($path, strlen('/uploads'));
    if (!is_file($file) && !photo_cut($p, $head, $m[4], $file)) {
        return false;
    }
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($file);
    return true;
}

/** Отрезать один размер фото ($size: l, m, s) в файл $file. */
function photo_cut(array $p, bool $head, string $size, string $file): bool
{
    $aspect = $head ? '3:2' : $p['aspect'];
    [$w, $h] = PHOTO_SIZES[$aspect][$size];
    $crop = $head
        ? [$p['head_crop_x'], $p['head_crop_y'], $p['head_crop_w'], $p['head_crop_h']]
        : [$p['crop_x'], $p['crop_y'], $p['crop_w'], $p['crop_h']];
    $src = (int) $p['use_processed'] === 1 && $p['processed_path'] ? $p['processed_path'] : $p['original_path'];
    return render_photo(ORIGINALS_DIR . '/' . $src, $file, $w, $h, (float) $p['rotate'], $crop);
}

/** Загрузить картинку с учётом EXIF-ориентации телефона. */
function load_image(string $src): ?GdImage
{
    $info = @getimagesize($src);
    if (!$info) {
        return null;
    }
    $img = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_WEBP => @imagecreatefromwebp($src),
        default        => false,
    };
    if (!$img) {
        return null;
    }
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($src)['Orientation'] ?? 1);
        $deg = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($deg) {
            $img = imagerotate($img, $deg, 0);
        }
    }
    return $img;
}

/**
 * Повернуть, вырезать рамку (в пикселях повёрнутого исходника) и уменьшить до $w×$h.
 * Нет рамки — берём самую большую по центру. JPEG ~82, без EXIF.
 */
function render_photo(string $src, string $dst, int $w, int $h, float $rotate, array $crop): bool
{
    $img = load_image($src);
    if (!$img) {
        return false;
    }
    if ($rotate != 0.0) {
        $bg = imagecolorallocate($img, 255, 255, 255);
        $img = imagerotate($img, -$rotate, $bg);
    }
    $iw = imagesx($img);
    $ih = imagesy($img);
    [$cx, $cy, $cw, $ch] = $crop;
    if ($cw === null || $ch === null || $cw <= 0 || $ch <= 0) {
        $ratio = $w / $h;
        $cw = $iw / $ih > $ratio ? $ih * $ratio : $iw;
        $ch = $cw / $ratio;
        $cx = ($iw - $cw) / 2;
        $cy = ($ih - $ch) / 2;
    }
    $out = imagecreatetruecolor($w, $h);
    $png=str_ends_with(strtolower($dst),'.png');
    imagefill($out,0,0,imagecolorallocate($out,$png?247:255,$png?245:255,$png?240:255));
    imagecopyresampled($out, $img, 0, 0, (int) round((float) $cx), (int) round((float) $cy), $w, $h, (int) round((float) $cw), (int) round((float) $ch));
    if (!is_dir(dirname($dst))) {
        mkdir(dirname($dst), 0775, true);
    }
    $tmp = $dst . '.tmp';
    imageinterlace($out, true);
    $ok = ($png?imagepng($out,$tmp,6):imagejpeg($out,$tmp,82)) && rename($tmp,$dst);
    return $ok;
}

/** <img> со srcset и размерами (раздел 9.2). $head — кадр 3:2 верха страницы плитки. */
function photo_img(?array $p, string $alt, bool $lazy = true, bool $head = false, string $sizes = '100vw'): string
{
    if (!$p) {
        return '';
    }
    $aspect = $head ? '3:2' : $p['aspect'];
    $sz = PHOTO_SIZES[$aspect];
    $paths = photo_paths($p);
    $k = $head ? 'h' : '';
    $srcset = [];
    foreach (['s', 'm', 'l'] as $v) {
        $srcset[] = '/uploads/' . $paths[$k . $v] . ' ' . $sz[$v][0] . 'w';
    }
    return sprintf('<img src="/uploads/%s" srcset="%s" sizes="%s" width="%d" height="%d" alt="%s"%s>',
        e($paths[$k . 'm']), e(implode(', ', $srcset)), e($sizes), $sz['m'][0], $sz['m'][1], e($alt),
        $lazy ? ' loading="lazy" decoding="async"' : '');
}

/** Lossless approved wine stage. Generated from the editor photo and invalidated by every crop/source change. */
function wine_photo_url(array $p): ?string {
    $p['aspect']='4:5';$name='wine-stage/'.$p['id'].'-'.photo_hash($p,false).'.png';$file=PUBLIC_UPLOADS.'/'.$name;
    if(!is_file($file)&&!photo_cut($p,false,'l',$file))return null;
    return '/uploads/'.$name;
}

function wine_photo_demo(?array $p): bool {
    if(!$p)return true;$src=(int)$p['use_processed']===1&&$p['processed_path']?$p['processed_path']:$p['original_path'];
    $file=ORIGINALS_DIR.'/'.$src;$demo=ROOT_DIR.'/public/assets/wines/henotis.png';
    return is_file($file)&&is_file($demo)&&hash_file('sha256',$file)===hash_file('sha256',$demo);
}
