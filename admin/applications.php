<?php
require __DIR__ . '/../inc/admin.php';

require_admin();
$pdo = admin_db();

const PER_PAGE = 50;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('SELECT cv_file FROM vf_applications WHERE id = ?');
        $stmt->execute([(int) $_POST['id']]);
        $cvFile = $stmt->fetchColumn();
        if ($cvFile !== false) {
            if ($cvFile !== '') {
                @unlink(CV_DIR . '/' . basename($cvFile));
            }
            $pdo->prepare('DELETE FROM vf_applications WHERE id = ?')->execute([(int) $_POST['id']]);
            flash('Апликацијата и CV-то се избришани.');
        }
    }
    redirect('/admin/applications.php?' . http_build_query(array_filter([
        'q' => $_POST['q'] ?? '',
        'position' => $_POST['position'] ?? '',
        'page' => $_POST['page'] ?? '',
    ])));
}

$q = trim((string) ($_GET['q'] ?? ''));
$positionFilter = trim((string) ($_GET['position'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)';
    array_push($params, ...array_fill(0, 5, '%' . $q . '%'));
}
if ($positionFilter !== '') {
    $where[] = 'position = ?';
    $params[] = $positionFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM vf_applications $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE));
$page = min($page, $pages);

$stmt = $pdo->prepare("SELECT * FROM vf_applications $whereSql ORDER BY id DESC LIMIT " . PER_PAGE . ' OFFSET ' . (($page - 1) * PER_PAGE));
$stmt->execute($params);
$applications = $stmt->fetchAll();

$positionNames = $pdo->query('SELECT DISTINCT position FROM vf_applications ORDER BY position')->fetchAll(PDO::FETCH_COLUMN);

function page_url(int $page): string
{
    return '/admin/applications.php?' . http_build_query(array_filter([
        'q' => $_GET['q'] ?? '',
        'position' => $_GET['position'] ?? '',
        'page' => $page > 1 ? $page : '',
    ]));
}

admin_header('Апликации', 'applications');
?>
    <div class="page-head">
      <h1>Апликации <span class="muted">(<?= $total ?>)</span></h1>
    </div>

    <form class="panel filters" method="get">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Пребарај име, е-пошта, телефон, град...">
      <select name="position">
        <option value="">Сите позиции</option>
<?php foreach ($positionNames as $name): ?>
        <option value="<?= e($name) ?>"<?= $name === $positionFilter ? ' selected' : '' ?>><?= e($name) ?></option>
<?php endforeach; ?>
      </select>
      <button class="button primary" type="submit"><i class="fas fa-search"></i> Барај</button>
<?php if ($q !== '' || $positionFilter !== ''): ?>
      <a class="button" href="/admin/applications.php">Исчисти</a>
<?php endif; ?>
    </form>

    <section class="panel">
<?php if ($applications): ?>
      <div class="table-scroll">
      <table class="table">
        <thead><tr><th>Датум</th><th>Име и презиме</th><th>Позиција</th><th>Град</th><th>Контакт</th><th>CV</th><th></th></tr></thead>
        <tbody>
<?php foreach ($applications as $a): ?>
          <tr>
            <td class="nowrap"><?= e(date('d.m.Y H:i', strtotime($a['created_at']))) ?></td>
            <td><strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong></td>
            <td><?= e($a['position']) ?></td>
            <td><?= e($a['city']) ?></td>
            <td>
              <a href="tel:<?= e($a['phone']) ?>"><?= e($a['phone']) ?></a><br>
              <a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a>
            </td>
            <td class="nowrap">
<?php if ($a['cv_file'] !== ''): ?>
              <a class="button small" href="/admin/cv.php?id=<?= (int) $a['id'] ?>"><i class="fas fa-download"></i> CV</a>
<?php else: ?>
              <span class="muted">нема</span>
<?php endif; ?>
<?php if (!$a['mail_sent']): ?>
              <span class="badge badge-off" title="Е-поштата до вас не беше испратена за оваа апликација">без е-пошта</span>
<?php endif; ?>
            </td>
            <td>
              <form method="post" onsubmit="return confirm('Да се избрише апликацијата и CV-то?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <input type="hidden" name="position" value="<?= e($positionFilter) ?>">
                <input type="hidden" name="page" value="<?= $page ?>">
                <button class="button small danger" type="submit" title="Избриши"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
<?php if ($pages > 1): ?>
      <div class="pager">
<?php if ($page > 1): ?>
        <a class="button small" href="<?= e(page_url($page - 1)) ?>"><i class="fas fa-chevron-left"></i></a>
<?php endif; ?>
        <span>Страна <?= $page ?> од <?= $pages ?></span>
<?php if ($page < $pages): ?>
        <a class="button small" href="<?= e(page_url($page + 1)) ?>"><i class="fas fa-chevron-right"></i></a>
<?php endif; ?>
      </div>
<?php endif; ?>
<?php else: ?>
      <p class="muted">Нема апликации<?= $q !== '' || $positionFilter !== '' ? ' за овој филтер' : '' ?>.</p>
<?php endif; ?>
    </section>
<?php
admin_footer();
