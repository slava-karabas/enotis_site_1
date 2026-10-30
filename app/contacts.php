<?php
declare(strict_types=1);

/** Parse only public contact destinations; no HTML, script, credentials or arbitrary schemes. */
function panel_contact(string $value): array
{
    $value = trim($value);
    if ($value === '' || mb_strlen($value) > 2048 || preg_match('/[\x00-\x1F\x7F]/', $value)) {
        throw new InvalidArgumentException('Некорректный контакт');
    }
    $mail = preg_replace('/^mailto:/i', '', $value);
    if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        return ['href'=>'mailto:'.$mail, 'text'=>$mail, 'type'=>'email'];
    }
    $phone = preg_replace('/^tel:/i', '', $value);
    if (preg_match('/^\+?[0-9() .-]+$/D', $phone)) {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) >= 7 && strlen($digits) <= 15) {
            return ['href'=>'tel:'.(str_starts_with($phone, '+')?'+':'').$digits, 'text'=>$phone, 'type'=>'phone'];
        }
    }
    $parts = parse_url($value);
    if (!$parts || !in_array(strtolower($parts['scheme']??''), ['http','https'], true)
        || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
        || preg_match('/\s|\\\\|%(?:0[0-9a-f]|1[0-9a-f]|7f)/i', $value)
        || !filter_var($value, FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException('Введите телефон, почту или HTTP(S)-ссылку');
    }
    $host = strtolower(rtrim($parts['host'], '.'));
    $type = 'site';
    foreach (['instagram.com'=>'instagram','facebook.com'=>'facebook','fb.com'=>'facebook',
        't.me'=>'telegram','telegram.me'=>'telegram','wa.me'=>'whatsapp','whatsapp.com'=>'whatsapp',
        'youtube.com'=>'youtube','youtu.be'=>'youtube','vk.com'=>'vk'] as $domain=>$name) {
        if ($host === $domain || str_ends_with($host, '.'.$domain)) { $type=$name; break; }
    }
    return ['href'=>$value, 'text'=>$value, 'type'=>$type];
}

function panel_contacts_config(array $v): array
{
    if (!is_array($v['items']??null) || !array_is_list($v['items']) || count($v['items'])>50) {
        throw new InvalidArgumentException('Допустимо до 50 контактов');
    }
    $items=[];
    foreach ($v['items'] as $item) {
        if (!is_array($item) || !is_string($item['value']??null) || !is_string($item['label']??'')) {
            throw new InvalidArgumentException('Некорректная строка контакта');
        }
        $value=trim($item['value']); $label=trim($item['label']??'');
        if (mb_strlen($label)>160) throw new InvalidArgumentException('Подпись слишком длинная');
        if ($value==='') { if ($label!=='') throw new InvalidArgumentException('У подписи нет контакта'); continue; }
        panel_contact($value);
        $items[]=['value'=>$value,'label'=>$label];
    }
    return ['kind'=>'contacts','items'=>$items];
}

/** Safe optional subheading in the existing plain description: a line starting with ##. */
function panel_description(?string $body): void
{
    foreach (paragraphs($body) as $p) {
        if (str_starts_with($p, '## ')) echo '<h2 class="tpage__subheading">'.e(mb_substr($p,3)).'</h2>';
        else echo '<p>'.e($p).'</p>';
    }
}
