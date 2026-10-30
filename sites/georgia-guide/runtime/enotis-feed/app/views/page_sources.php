<?php $credits = json_decode(file_get_contents(APP_DIR . '/source_credits.json'), true) ?: []; ?>
<article class="wrap doc"><h1>Источники и фотографии</h1><p>Путеводитель подготовлен по материалам туристических организаций, монастырей и открытых фотоархивов. Фотографии сохраняют авторство своих создателей; кадрирование и светокоррекция не меняют архитектуру.</p>
<?php foreach ($credits as $credit): ?><section id="<?= e($credit['slug'] ?? '') ?>" class="source-credit"><h2><?= e($credit['title']) ?></h2><p><?= e($credit['author']) ?></p><p><?php foreach ($credit['links'] as $link): if (!str_starts_with($link, 'https://')) continue; ?><a href="<?= e($link) ?>" target="_blank" rel="noopener">Источник ↗</a> <?php endforeach ?></p></section><?php endforeach ?>
</article>
