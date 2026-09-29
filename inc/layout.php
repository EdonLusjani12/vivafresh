<?php
require_once __DIR__ . '/bootstrap.php';

const NAV = [
    'home' => ['/', 'Дома'],
    'katalog' => ['/katalog.php', 'Каталог'],
    'cenovnik' => ['/cenovnik.php', 'Ценовници'],
    'kontakt' => ['/kontakt.php', 'Контакт'],
    'vrabotuvanje' => ['/vrabotuvanje.php', 'Вработување'],
    'tgtg' => ['/toogoodtogo1.php', 'Too Good To Go'],
];

function site_header(string $title, string $active = ''): void
{
    $pageTitle = $title === '' ? c('site.name') : $title . ' - ' . c('site.name');
    ?>
<!DOCTYPE html>
<html lang="mk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="<?= e(asset(c('site.logo'))) ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="/">
        <img src="<?= e(asset(c('site.logo'))) ?>" alt="<?= t('site.brand') ?>">
        <span><?= t('site.brand') ?><span class="brand-mk"> <?= t('site.brand_suffix') ?></span></span>
      </a>
      <button class="nav-toggle" id="navToggle" aria-label="Мени"><i class="fas fa-bars"></i></button>
      <nav class="nav" id="nav">
<?php foreach (NAV as $id => [$href, $label]): ?>
        <a<?= $id === $active ? ' class="active"' : '' ?> href="<?= e($href) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
        <div class="nav-social">
<?php if (c('site.instagram') !== ''): ?>
          <a href="<?= t('site.instagram') ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
<?php endif; ?>
<?php if (c('site.facebook') !== ''): ?>
          <a href="<?= t('site.facebook') ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
<?php endif; ?>
        </div>
      </nav>
    </div>
  </header>
<?php
}

function site_footer(string $extraScripts = ''): void
{
    ?>
  <footer class="site-footer">
    <div class="wrap footer-grid">
      <div>
        <img class="footer-logo" src="<?= e(asset(c('site.logo'))) ?>" alt="">
        <h3><?= t('site.name') ?></h3>
        <p><?= tn('site.footer_text') ?></p>
      </div>
      <div>
        <h3>Линкови</h3>
        <ul>
<?php foreach (NAV as $id => [$href, $label]): ?>
<?php if ($id !== 'home'): ?>
          <li><a href="<?= e($href) ?>"><?= e($label) ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h3>Контакт</h3>
        <ul>
          <li><a href="tel:+<?= e(phone_intl()) ?>"><?= t('site.phone') ?></a></li>
          <li><a href="mailto:<?= t('site.email') ?>"><?= t('site.email') ?></a></li>
          <li><?= t('site.address') ?></li>
          <li><?= t('site.hours') ?></li>
        </ul>
      </div>
    </div>
    <div class="wrap copy">
      © <?= date('Y') ?> <?= t('site.name') ?> ·
      <a href="https://www.linkedin.com/in/edon-lusjani-798b24272/">Edon Lusjani</a> ·
      <a href="https://www.linkedin.com/in/bojana-taseva-0383b22b0/">Bojana Taseva</a>
    </div>
  </footer>
  <script src="<?= e(asset('js/site.js')) ?>"></script>
<?= $extraScripts ?>
</body>
</html>
<?php
}

/** Viber / WhatsApp / e-mail / phone round buttons. */
function contact_methods(bool $withEmailAndPhone = true): void
{
    ?>
        <div class="methods">
          <a class="viber" href="viber://chat?number=%2B<?= e(phone_intl()) ?>" aria-label="Viber"><i class="fab fa-viber"></i></a>
          <a class="whatsapp" href="https://wa.me/<?= e(phone_intl()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
<?php if ($withEmailAndPhone): ?>
          <a class="email" href="mailto:<?= t('site.email') ?>" aria-label="Е-пошта"><i class="fas fa-envelope"></i></a>
          <a class="phone" href="tel:+<?= e(phone_intl()) ?>" aria-label="Телефон"><i class="fas fa-phone"></i></a>
<?php endif; ?>
        </div>
<?php
}
