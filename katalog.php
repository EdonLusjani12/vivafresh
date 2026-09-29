<?php
require __DIR__ . '/inc/layout.php';

site_header('Каталог', 'katalog');
?>
  <main class="wrap">
    <section class="page-hero">
      <span class="kicker"><?= t('katalog.kicker') ?></span>
      <h1><?= t('katalog.title') ?> <em><?= t('katalog.title_em') ?></em></h1>
      <p><?= tn('katalog.text') ?></p>
    </section>
<?php if (c('katalog.url') !== ''): ?>
    <div class="pdf-wrap">
      <iframe src="<?= t('katalog.url') ?>" title="Каталог <?= t('site.brand') ?>" allowfullscreen></iframe>
    </div>
<?php endif; ?>
    <section class="section">
      <div class="card contact-panel">
        <h2><?= t('katalog.contact_title') ?></h2>
        <div class="phone"><?= t('site.phone') ?></div>
<?php contact_methods(false); ?>
      </div>
    </section>
  </main>
<?php
site_footer();
