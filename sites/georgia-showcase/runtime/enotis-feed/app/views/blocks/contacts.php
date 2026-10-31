<?php
$items = $config['items'] ?? [];

?>
<section id="block-<?= (int)$b['id'] ?>" class="wrap blk contacts"<?= eid($b['id']) ?>>
  <div class="contacts__surface">
    <h2><?= e($b['title'] ?: t('contacts.title')) ?></h2>
<?php if (!$items): ?>
    <p class="contacts__empty"><?= e(t('contacts.empty')) ?></p>
<?php else: ?>
    <ul class="contacts__list">
<?php foreach ($items as $item):
    try { $contact=panel_contact($item['value']); } catch (InvalidArgumentException $e) { continue; }
    $label=$item['label'] ?: t('contacts.'.$contact['type']);
?>
      <li><span class="contacts__label"><?= e($label) ?></span><a href="<?= e($contact['href']) ?>"<?php if (str_starts_with($contact['href'],'http')): ?> target="_blank" rel="noopener noreferrer"<?php endif ?>><?= e($contact['text']) ?></a></li>
<?php endforeach ?>
    </ul>
<?php endif ?>
  </div>
</section>
