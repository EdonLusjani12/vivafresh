<?php
require __DIR__ . '/inc/layout.php';

$maps = [];
foreach ([1, 2] as $i) {
    if (c("kontakt.map{$i}_url") !== '') {
        $maps[] = ['title' => c("kontakt.map{$i}_title"), 'url' => c("kontakt.map{$i}_url")];
    }
}

site_header('Контакт', 'kontakt');
?>
  <main class="wrap">
    <section class="page-hero">
      <span class="kicker"><?= t('kontakt.kicker') ?></span>
      <h1><?= t('kontakt.title') ?> <em><?= t('kontakt.title_em') ?></em></h1>
      <p><?= tn('kontakt.text') ?></p>
    </section>
<?php if ($maps): ?>
    <div class="map-grid">
<?php foreach ($maps as $map): ?>
      <article class="card map-card">
        <iframe src="<?= e($map['url']) ?>" loading="lazy" allowfullscreen></iframe>
        <h3><?= e($map['title']) ?></h3>
      </article>
<?php endforeach; ?>
    </div>
<?php endif; ?>
    <section class="section">
      <div class="card contact-panel">
        <h2><?= t('kontakt.panel_title') ?></h2>
        <div class="phone"><?= t('site.phone') ?></div>
        <p><?= t('site.hours') ?></p>
<?php contact_methods(); ?>
      </div>
    </section>
  </main>
<?php
site_footer();
