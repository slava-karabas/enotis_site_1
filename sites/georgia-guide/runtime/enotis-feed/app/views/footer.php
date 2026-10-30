<?php
if (setting('footer_show', '1') !== '1') {
    return;
}
$email = setting('footer_email');
$phone = setting('footer_phone');
$op = operator();
$fmt = $op ? $op['country'] : 'RU';
$docs = visible_docs();
$social = social_links();
$socialByNetwork = array_column($social, 'url', 'network');
?>
<footer id="footer" class="ftr"<?= eid('footer') ?>>
  <div class="ftr__in">
    <div class="ftr__main">
      <p class="ftr__brand"><span class="ftr__wordmark"><svg xmlns="http://www.w3.org/2000/svg" viewBox="15 25 315 100" width="189" height="60" role="img" aria-label="Enotis"><g fill="#7B2035" transform="translate(20 30) scale(1.1)"><path d="M23 0h2v3h3v2h-3v3h-2V5h-3V3h3zM17 16c0-5 3-8 7-10 4 2 7 5 7 10zM18 17h12v6H18zM8 23l8-6 8 6 8-6 8 6v12H8zM2 35h44v3H2z"/><path d="M20 25h8v10h-8z" fill="var(--c-bg,#f3ede5)"/><path d="M8 41H40V49Q40 53 35 56L29 61V70H19V61L13 56Q8 53 8 49Z"/><path d="M19 72h10v3H19Z"/><path d="M18 47h12v4H18Z" fill="var(--c-bg,#f3ede5)"/></g><text transform="translate(87 71) scale(.884058) translate(-87 -71)" x="87" y="91" fill="#7B2035" font-family="Charter" font-size="60" font-weight="900" letter-spacing="-.8">Enotis</text></svg></span></p>
      <p class="ftr__title"><?= e(site_title()) ?></p>
      <p class="ftr__description">Путеводитель по святыням, истории и монастырским традициям Грузии.</p>
      <nav class="ftr__nav" aria-label="Навигация в подвале"><a href="<?= e(url('/')) ?>">Все монастыри</a><a href="<?= e(url('/')) ?>#about">О проекте</a><?php foreach ($docs as $k => $d): ?><a href="<?= e(url('/doc/' . $k)) ?>"><?= e($d['title']) ?></a><?php endforeach ?></nav>
    </div>
    <div class="ftr__connection">
      <section id="footer-social" class="ftr__community" aria-label="Социальные сети проекта">
        <h2>Смотрите и подписывайтесь</h2>
        <ul class="social ftr__social">
<?php foreach (['youtube','instagram','tiktok','telegram'] as $network): $name=SOCIAL_NAMES[$network]; $destination=$socialByNetwork[$network] ?? ''; ?>
          <li>
<?php if ($destination): ?>
            <a href="<?= e($destination) ?>" target="_blank" rel="noopener noreferrer"><?= social_icon($network, true) ?><span><?= e($name) ?></span></a>
<?php else: ?>
            <span class="ftr__social-pending" title="Ссылка пока не добавлена"><?= social_icon($network, true) ?><span><?= e($name) ?></span></span>
<?php endif ?>
          </li>
<?php endforeach ?>
        </ul>
      </section>
      <section id="footer-contact" class="ftr__messages" aria-label="Связаться с проектом">
        <h2>Для связи с проектом ЭНОТИС</h2>
        <ul class="social ftr__social ftr__messengers">
<?php foreach (['whatsapp','viber','telegram'] as $network): $name=SOCIAL_NAMES[$network]; $destination=$network==='telegram' ? setting('footer_telegram', '') : ($socialByNetwork[$network] ?? ''); ?>
          <li>
<?php if ($destination): ?>
            <a href="<?= e($destination) ?>" target="_blank" rel="noopener noreferrer"><?= ($network === 'telegram' ? '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m21 3-4 18-6-5-3 3v-6L3 11 21 3Z"/><path d="m8 13 9-6-6 9"/></svg>' : social_icon($network)) ?><span><?= e($name) ?></span></a>
<?php else: ?>
            <span class="ftr__social-pending" title="Контакт пока не добавлен"><?= ($network === 'telegram' ? '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m21 3-4 18-6-5-3 3v-6L3 11 21 3Z"/><path d="m8 13 9-6-6 9"/></svg>' : social_icon($network)) ?><span><?= e($name) ?></span></span>
<?php endif ?>
          </li>
<?php endforeach ?>
        </ul>
        <div class="ftr__phone-row">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M6.5 3h3l1.4 5-2 1.5a15 15 0 0 0 5.6 5.6l1.5-2 5 1.4v3c0 2-1.8 3.5-3.8 3-7-1.8-12-6.8-13.8-13.8C2.9 4.8 4.5 3 6.5 3Z"/></svg>
<?php if ($phone): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a><?php else: ?><span>+995 000 00 00 00</span><small>Пример номера</small><?php endif ?>
        </div>
<?php if ($email): ?><p class="ftr__email"><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p><?php endif ?>
      </section>
    </div>
<?php if ($op): ?>
    <div class="ftr__legal">
<?php   if ($op): ?>
      <p><?= e(implode(' · ', array_filter([
            $op['name'],
            $op['tax_id'] ? t('op.tax_id.' . $fmt) . ' ' . $op['tax_id'] : null,
            $op['reg_no'] ? t('op.reg_no.' . $fmt) . ' ' . $op['reg_no'] : null,
            $op['address'],
        ]))) ?></p>
<?php   endif ?>

    </div>
<?php endif ?>
  </div>
  <div class="wrap ftr__bottom"><p>ЭНОТИС · <?= date('Y') ?>. Путеводитель по монастырям Грузии.</p><a href="#main">Наверх ↑</a></div>
</footer>
