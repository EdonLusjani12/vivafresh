<?php
require __DIR__ . '/../inc/admin.php';

require_admin();
$pdo = admin_db();

$openPositions = (int) $pdo->query('SELECT COUNT(*) FROM vf_positions WHERE is_open = 1')->fetchColumn();
$totalApplications = (int) $pdo->query('SELECT COUNT(*) FROM vf_applications')->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM vf_applications WHERE created_at >= ?');
$stmt->execute([date('Y-m-d H:i:s', strtotime('-7 days'))]);
$weekApplications = (int) $stmt->fetchColumn();
$latest = $pdo->query('SELECT * FROM vf_applications ORDER BY id DESC LIMIT 5')->fetchAll();

admin_header('Преглед', 'dashboard');
?>
    <h1>Преглед</h1>
    <div class="tiles">
      <a class="tile" href="/admin/positions.php"><b><?= $openPositions ?></b><span>отворени позиции</span></a>
      <a class="tile" href="/admin/applications.php"><b><?= $weekApplications ?></b><span>апликации во последните 7 дена</span></a>
      <a class="tile" href="/admin/applications.php"><b><?= $totalApplications ?></b><span>апликации вкупно</span></a>
    </div>

    <section class="panel">
      <div class="panel-head">
        <h2>Последни апликации</h2>
        <a class="button" href="/admin/applications.php">Сите апликации</a>
      </div>
<?php if ($latest): ?>
      <table class="table">
        <thead><tr><th>Датум</th><th>Име</th><th>Позиција</th><th>Град</th></tr></thead>
        <tbody>
<?php foreach ($latest as $a): ?>
          <tr>
            <td><?= e(date('d.m.Y H:i', strtotime($a['created_at']))) ?></td>
            <td><?= e($a['first_name'] . ' ' . $a['last_name']) ?></td>
            <td><?= e($a['position']) ?></td>
            <td><?= e($a['city']) ?></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
<?php else: ?>
      <p class="muted">Сè уште нема апликации.</p>
<?php endif; ?>
    </section>

    <section class="panel">
      <h2>Брзи линкови</h2>
      <div class="quick">
<?php foreach (content_schema() as $id => $section): ?>
        <a class="button" href="/admin/content.php?section=<?= e($id) ?>"><i class="fas fa-pen"></i> <?= e($section['label']) ?></a>
<?php endforeach; ?>
        <a class="button" href="/admin/positions.php?edit=new"><i class="fas fa-plus"></i> Нова позиција</a>
      </div>
    </section>
<?php
admin_footer();
