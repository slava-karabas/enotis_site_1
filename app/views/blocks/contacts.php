<?php
$items = $config['items'] ?? [];
if (!$items) return;
?>
<section class="wrap blk contacts"<?= eid($b['id']) ?><?php if (!empty($b['_page_anchor'])): ?> id="block-<?= (int)$b['id'] ?>"<?php endif ?>>
  <div class="contacts__surface">
    <h2><?= e($b['title'] ?: t('contacts.title')) ?></h2>
    <ul class="contacts__list">
<?php foreach ($items as $item):
    try { $contact=panel_contact($item['value']); } catch (InvalidArgumentException $e) { continue; }
    $label=$item['label'] ?: t('contacts.'.$contact['type']);
    $display=preg_replace('~^https?://(?:www\.)?~i','',$contact['text']);
?>
      <li><span class="contacts__label"><?= e($label) ?></span><a href="<?= e($contact['href']) ?>"<?php if (str_starts_with($contact['href'],'http')): ?> target="_blank" rel="noopener noreferrer"<?php endif ?>><?= e($display) ?></a></li>
<?php endforeach ?>
    </ul>
  </div>
</section>
