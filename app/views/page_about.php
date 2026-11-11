<article class="wrap doc">
  <h1><?=e(presentation('menu_about'))?></h1>
<?php foreach(paragraphs(setting_text('about_body') ?? '') as $paragraph): ?>
  <p><?=e($paragraph)?></p>
<?php endforeach ?>
</article>
