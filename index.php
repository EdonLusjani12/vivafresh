<?php
require __DIR__ . '/inc/layout.php';

$videoId = youtube_id(c('home.video_url'));

site_header('', 'home');
?>
  <main>
    <section class="wrap hero">
      <div>
        <span class="kicker"><?= t('home.kicker') ?></span>
        <h1><?= t('home.title') ?><br><em><?= t('home.title_em') ?></em></h1>
        <p class="lede"><?= tn('home.lede') ?></p>
<?php if (c('home.open_note') !== ''): ?>
        <p class="lede" style="margin-top:18px;font-weight:700;color:var(--green)"><?= t('home.open_note') ?></p>
<?php endif; ?>
        <div class="actions">
          <a class="btn btn-primary" href="/katalog.php"><i class="fas fa-book-open"></i> Каталог</a>
          <a class="btn btn-ghost" href="/cenovnik.php"><i class="fas fa-tags"></i> Ценовници</a>
        </div>
      </div>
      <div class="hero-art">
        <img src="<?= e(asset(c('home.hero_image'))) ?>" alt="<?= t('site.name') ?>">
      </div>
    </section>

    <section class="wrap">
      <div class="stats">
<?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="stat"><b><?= t("home.stat{$i}_value") ?></b><span><?= t("home.stat{$i}_label") ?></span></div>
<?php endfor; ?>
      </div>
    </section>

    <section class="section">
      <div class="wrap">
        <div class="section-head">
          <h2><?= t('home.why_title') ?></h2>
          <p><?= t('home.why_text') ?></p>
        </div>
        <div class="grid-3">
<?php for ($i = 1; $i <= 3; $i++): ?>
          <article class="card">
            <div class="icon"><i class="fas <?= t("home.card{$i}_icon") ?>"></i></div>
            <h3><?= t("home.card{$i}_title") ?></h3>
            <p><?= tn("home.card{$i}_text") ?></p>
          </article>
<?php endfor; ?>
        </div>
      </div>
    </section>

<?php if ($videoId !== ''): ?>
    <section class="section" style="padding-top:0">
      <div class="wrap">
        <div class="section-head">
          <h2><?= t('home.video_title') ?></h2>
          <p><?= t('home.video_text') ?></p>
        </div>
        <div class="video-frame">
          <iframe src="https://www.youtube.com/embed/<?= e($videoId) ?>" title="<?= t('home.video_title') ?>" allowfullscreen></iframe>
        </div>
      </div>
    </section>
<?php endif; ?>

    <section class="section" id="contact">
      <div class="wrap">
        <div class="card contact-panel">
          <span class="kicker">Контакт</span>
          <h2><?= t('home.contact_title') ?></h2>
          <p class="lede" style="margin:12px auto"><?= t('home.contact_text') ?></p>
          <div class="phone"><?= t('site.phone') ?></div>
<?php contact_methods(); ?>
        </div>
      </div>
    </section>
  </main>
<?php
site_footer();
