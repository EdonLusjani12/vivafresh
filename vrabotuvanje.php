<?php
require __DIR__ . '/inc/layout.php';

const GENERAL_APPLICATION = 'Општа апликација';

$positions = open_positions();

$script = <<<'HTML'
  <script>
    document.querySelectorAll("[data-apply]").forEach(function (link) {
      link.addEventListener("click", function () {
        var select = document.getElementById("positionSelect");
        if (select) select.value = link.getAttribute("data-apply");
      });
    });
  </script>
HTML;

site_header('Вработување', 'vrabotuvanje');
?>
  <main class="wrap">
    <section class="page-hero">
      <span class="kicker"><?= t('vrabotuvanje.kicker') ?></span>
      <h1><?= t('vrabotuvanje.title') ?> <em><?= t('vrabotuvanje.title_em') ?></em></h1>
      <p><?= tn('vrabotuvanje.text') ?></p>
      <div class="actions" style="justify-content:center">
        <a class="btn btn-primary" href="#application">Аплицирај</a>
      </div>
    </section>

    <section class="section" id="benefits">
      <div class="grid-3">
<?php for ($i = 1; $i <= 3; $i++): ?>
        <article class="card"><div class="icon"><i class="fas <?= t("vrabotuvanje.benefit{$i}_icon") ?>"></i></div><h3><?= t("vrabotuvanje.benefit{$i}_title") ?></h3><p><?= tn("vrabotuvanje.benefit{$i}_text") ?></p></article>
<?php endfor; ?>
      </div>
    </section>

    <section class="section" id="positions" style="padding-top:0">
      <div class="section-head">
        <h2><?= t('vrabotuvanje.positions_title') ?></h2>
      </div>
<?php if ($positions): ?>
      <div class="positions">
<?php foreach ($positions as $p): ?>
        <article class="card position-card">
          <h3><?= e($p['title']) ?></h3>
<?php if ($p['location'] !== '' || $p['employment_type'] !== ''): ?>
          <div class="position-meta">
<?php if ($p['location'] !== ''): ?>
            <span><i class="fas fa-map-marker-alt"></i> <?= e($p['location']) ?></span>
<?php endif; ?>
<?php if ($p['employment_type'] !== ''): ?>
            <span><i class="fas fa-clock"></i> <?= e($p['employment_type']) ?></span>
<?php endif; ?>
          </div>
<?php endif; ?>
<?php if ($p['description'] !== ''): ?>
          <p><?= nl2br(e($p['description']), false) ?></p>
<?php endif; ?>
          <a class="btn btn-primary" href="#application" data-apply="<?= e($p['title']) ?>">Аплицирај</a>
        </article>
<?php endforeach; ?>
      </div>
<?php else: ?>
      <p class="card" style="text-align:center"><?= tn('vrabotuvanje.positions_empty') ?></p>
<?php endif; ?>
    </section>

    <section class="section" id="application" style="padding-top:0">
      <div class="card form-card">
        <h2><?= t('vrabotuvanje.form_title') ?></h2>

        <form action="send_email.php" method="POST" enctype="multipart/form-data">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Име</label><input class="form-control" type="text" name="first_name" maxlength="120" required></div>
            <div class="form-group"><label class="form-label">Презиме</label><input class="form-control" type="text" name="last_name" maxlength="120" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Град</label><input class="form-control" type="text" name="city" maxlength="120" required></div>
            <div class="form-group">
              <label class="form-label" for="positionSelect">Позиција</label>
              <select class="form-control" id="positionSelect" name="position" required>
<?php foreach ($positions as $p): ?>
                <option value="<?= e($p['title']) ?>"><?= e($p['title']) ?></option>
<?php endforeach; ?>
                <option value="<?= e(GENERAL_APPLICATION) ?>"><?= e(GENERAL_APPLICATION) ?></option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Телефон</label><input class="form-control" type="tel" name="phone" maxlength="40" required></div>
            <div class="form-group"><label class="form-label">Е-маил</label><input class="form-control" type="email" name="email" maxlength="190" required></div>
          </div>
          <div class="form-group">
            <label class="form-label">CV (PDF, DOC, DOCX · макс. 5 MB)</label>
            <input class="form-control-file" type="file" name="cv" accept=".pdf,.doc,.docx" required>
          </div>
          <div class="hp" aria-hidden="true">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Испрати</button>
            <button class="btn-secondary-modern" type="reset">Ресетирај</button>
          </div>
        </form>
      </div>
    </section>
  </main>
<?php
site_footer($script);
