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
      <p class="ftr__brand"><?php if($logo=site_brand_src()): ?><img class="ftr__logo" src="<?=e($logo)?>" alt="<?=e(site_title())?>" width="189" height="60"><?php else: ?><?=e(site_title())?><?php endif ?></p>
      <?php if($logo): ?><p class="ftr__title"><?= e(site_title()) ?></p><?php endif ?>
      <?php if(presentation('description')!==''): ?><p class="ftr__description"><?=e(presentation('description'))?></p><?php endif ?>
      <nav class="ftr__nav" aria-label="<?=e(site_phrase('Навигация в подвале'))?>"><?php foreach(presentation_navigation() as $item): ?><a href="<?=e($item['url'])?>"><?=e($item['title'])?></a><?php endforeach ?><?php foreach ($docs as $k => $d): ?><a href="<?= e(url('/doc/' . $k)) ?>"><?= e($d['title']) ?></a><?php endforeach ?></nav>
    </div>
    <div class="ftr__connection">
      <section id="footer-social" class="ftr__community" aria-label="<?=e(site_phrase('Социальные сети проекта'))?>">
        <h2><?=e(presentation('social_heading'))?></h2>
        <ul class="social ftr__social">
<?php foreach (presentation_networks('social_networks') as $network): $name=SOCIAL_NAMES[$network]; $destination=$socialByNetwork[$network] ?? ''; ?>
          <li>
<?php if ($destination): ?>
            <a href="<?= e($destination) ?>" target="_blank" rel="noopener noreferrer"><?= social_icon($network, true) ?><span><?= e($name) ?></span></a>
<?php else: ?>
            <span class="ftr__social-pending" title="<?=e(site_phrase('Ссылка пока не добавлена'))?>"><?= social_icon($network, true) ?><span><?= e($name) ?></span></span>
<?php endif ?>
          </li>
<?php endforeach ?>
        </ul>
      </section>
      <section id="footer-contact" class="ftr__messages" aria-label="<?=e(site_phrase('Связаться с проектом'))?>">
        <h2><?=e(presentation('contact_heading'))?></h2>
        <ul class="social ftr__social ftr__messengers">
<?php foreach (presentation_networks('messenger_networks') as $network): $name=SOCIAL_NAMES[$network]; $destination=$network==='telegram' ? setting('footer_telegram', '') : ($socialByNetwork[$network] ?? ''); ?>
          <li>
<?php if ($destination): ?>
            <a href="<?= e($destination) ?>" target="_blank" rel="noopener noreferrer"><?= ($network === 'telegram' ? '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m21 3-4 18-6-5-3 3v-6L3 11 21 3Z"/><path d="m8 13 9-6-6 9"/></svg>' : social_icon($network)) ?><span><?= e($name) ?></span></a>
<?php else: ?>
            <span class="ftr__social-pending" title="<?=e(site_phrase('Контакт пока не добавлен'))?>"><?= ($network === 'telegram' ? '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m21 3-4 18-6-5-3 3v-6L3 11 21 3Z"/><path d="m8 13 9-6-6 9"/></svg>' : social_icon($network)) ?><span><?= e($name) ?></span></span>
<?php endif ?>
          </li>
<?php endforeach ?>
        </ul>
<?php if ($phone): ?>
        <div class="ftr__phone-row">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M6.5 3h3l1.4 5-2 1.5a15 15 0 0 0 5.6 5.6l1.5-2 5 1.4v3c0 2-1.8 3.5-3.8 3-7-1.8-12-6.8-13.8-13.8C2.9 4.8 4.5 3 6.5 3Z"/></svg>
          <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a>
        </div>
<?php endif ?>
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
  <div class="wrap ftr__bottom"><p><?=e(str_replace('{year}',date('Y'),presentation('copyright')))?></p><a href="#main"><?=e(site_phrase('Наверх ↑'))?></a></div>
</footer>
