<?php
require __DIR__ . '/../inc/admin.php';

require_admin();
$pdo = admin_db();

function find_position(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM vf_positions WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $data = [
            'title' => mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 150),
            'location' => mb_substr(trim((string) ($_POST['location'] ?? '')), 0, 150),
            'employment_type' => mb_substr(trim((string) ($_POST['employment_type'] ?? '')), 0, 100),
            'description' => mb_substr(trim(str_replace("\r\n", "\n", (string) ($_POST['description'] ?? ''))), 0, 5000),
            'is_open' => empty($_POST['is_open']) ? 0 : 1,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        if ($data['title'] === '') {
            flash('Внесете наслов на позицијата.', 'error');
            redirect('/admin/positions.php?edit=' . ($id ?: 'new'));
        }

        if ($id && find_position($pdo, $id)) {
            $pdo->prepare(
                'UPDATE vf_positions SET title = ?, location = ?, employment_type = ?, description = ?, is_open = ?, sort_order = ?, updated_at = ? WHERE id = ?'
            )->execute([...array_values($data), now(), $id]);
            flash('Позицијата е зачувана.');
        } else {
            $pdo->prepare(
                'INSERT INTO vf_positions (title, location, employment_type, description, is_open, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([...array_values($data), now(), now()]);
            flash('Позицијата е додадена.');
        }
    } elseif ($action === 'toggle' && ($position = find_position($pdo, $id))) {
        $pdo->prepare('UPDATE vf_positions SET is_open = ?, updated_at = ? WHERE id = ?')
            ->execute([$position['is_open'] ? 0 : 1, now(), $id]);
        flash($position['is_open'] ? 'Позицијата е затворена (скриена од сајтот).' : 'Позицијата е отворена (видлива на сајтот).');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM vf_positions WHERE id = ?')->execute([$id]);
        flash('Позицијата е избришана.');
    }
    redirect('/admin/positions.php');
}

$edit = $_GET['edit'] ?? null;
$editing = null;
if ($edit !== null) {
    $editing = $edit === 'new' ? null : find_position($pdo, (int) $edit);
    if ($edit !== 'new' && !$editing) {
        redirect('/admin/positions.php');
    }
}
$positions = $pdo->query('SELECT * FROM vf_positions ORDER BY is_open DESC, sort_order, id DESC')->fetchAll();

admin_header('Позиции', 'positions');
?>
    <div class="page-head">
      <h1>Позиции за вработување</h1>
      <a class="button primary" href="/admin/positions.php?edit=new"><i class="fas fa-plus"></i> Нова позиција</a>
    </div>

<?php if ($edit !== null): ?>
<?php $p = $editing ?? ['id' => 0, 'title' => '', 'location' => '', 'employment_type' => '', 'description' => '', 'is_open' => 1, 'sort_order' => 0]; ?>
    <form class="panel" method="post">
      <h2><?= $editing ? 'Измени позиција' : 'Нова позиција' ?></h2>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
      <div class="field">
        <label for="title">Наслов *</label>
        <input id="title" type="text" name="title" value="<?= e($p['title']) ?>" maxlength="150" required placeholder="пр. Касиер/ка">
      </div>
      <div class="grid2">
        <div class="field">
          <label for="location">Локација</label>
          <input id="location" type="text" name="location" value="<?= e($p['location']) ?>" maxlength="150" placeholder="пр. Viva Fresh Store 1, Скопје">
        </div>
        <div class="field">
          <label for="employment_type">Тип на работа</label>
          <input id="employment_type" type="text" name="employment_type" value="<?= e($p['employment_type']) ?>" maxlength="100" placeholder="пр. Полно работно време">
        </div>
      </div>
      <div class="field">
        <label for="description">Опис</label>
        <textarea id="description" name="description" rows="6" maxlength="5000" placeholder="Обврски, услови, што нудиме..."><?= e($p['description']) ?></textarea>
      </div>
      <div class="grid2">
        <div class="field">
          <label for="sort_order">Редослед</label>
          <input id="sort_order" type="number" name="sort_order" value="<?= (int) $p['sort_order'] ?>">
          <small>Помал број = прикажана погоре.</small>
        </div>
        <div class="field">
          <label class="check"><input type="checkbox" name="is_open" value="1"<?= $p['is_open'] ? ' checked' : '' ?>> Отворена (видлива на сајтот)</label>
        </div>
      </div>
      <div class="form-foot">
        <a class="button" href="/admin/positions.php">Откажи</a>
        <button class="button primary" type="submit"><i class="fas fa-floppy-disk"></i> Зачувај</button>
      </div>
    </form>
<?php endif; ?>

    <section class="panel">
<?php if ($positions): ?>
      <table class="table">
        <thead><tr><th>Позиција</th><th>Локација</th><th>Тип</th><th>Статус</th><th></th></tr></thead>
        <tbody>
<?php foreach ($positions as $p): ?>
          <tr>
            <td><strong><?= e($p['title']) ?></strong></td>
            <td><?= e($p['location']) ?></td>
            <td><?= e($p['employment_type']) ?></td>
            <td><span class="badge <?= $p['is_open'] ? 'badge-ok' : 'badge-off' ?>"><?= $p['is_open'] ? 'Отворена' : 'Затворена' ?></span></td>
            <td class="row-actions">
              <a class="button small" href="/admin/positions.php?edit=<?= (int) $p['id'] ?>"><i class="fas fa-pen"></i> Измени</a>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="button small" type="submit"><?= $p['is_open'] ? 'Затвори' : 'Отвори' ?></button>
              </form>
              <form method="post" onsubmit="return confirm('Да се избрише позицијата?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="button small danger" type="submit"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
<?php else: ?>
      <p class="muted">Нема позиции. Кликнете „Нова позиција“ за да додадете.</p>
<?php endif; ?>
    </section>
<?php
admin_footer();
