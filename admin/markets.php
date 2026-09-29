<?php
require __DIR__ . '/../inc/admin.php';

require_admin();
admin_db();

const MARKET_TYPES = ['super' => 'VivaFresh Hyper', 'regular' => 'Маркет'];
const EMPTY_ROWS = 3;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();

    $list = [];
    $numbers = [];
    $errors = [];
    foreach ((array) ($_POST['m'] ?? []) as $row) {
        $name = mb_substr(trim((string) ($row['name'] ?? '')), 0, 100);
        $city = mb_substr(trim((string) ($row['city'] ?? '')), 0, 100);
        $number = (int) ($row['number'] ?? 0);
        if (!empty($row['delete']) || ($name === '' && $city === '' && $number === 0)) {
            continue;
        }
        if ($number < 1 || $number > 80) {
            $errors[] = "„{$name}“: бројот на маркет мора да биде од 1 до 80 (како во ценовникот).";
            continue;
        }
        if (isset($numbers[$number])) {
            $errors[] = "Бројот {$number} е внесен повеќе пати.";
            continue;
        }
        $numbers[$number] = true;
        $list[] = [
            'number' => $number,
            'name' => $name !== '' ? $name : 'Вива Фреш',
            'type' => isset(MARKET_TYPES[$row['type'] ?? '']) ? $row['type'] : 'regular',
            'city' => $city,
        ];
    }

    if ($errors) {
        foreach ($errors as $error) {
            flash($error, 'error');
        }
    } else {
        usort($list, fn ($a, $b) => $a['number'] <=> $b['number']);
        content_save('cenovnik.markets', json_encode($list, JSON_UNESCAPED_UNICODE));
        flash('Маркетите се зачувани.');
    }
    redirect('/admin/markets.php');
}

$rows = markets();
for ($i = 0; $i < EMPTY_ROWS; $i++) {
    $rows[] = ['number' => '', 'name' => '', 'type' => 'regular', 'city' => ''];
}

admin_header('Маркети', 'markets');
?>
    <h1>Маркети на страницата „Ценовници“</h1>
    <form class="panel" method="post">
      <?= csrf_field() ?>
      <p class="muted">Бројот на маркет мора да одговара на бројот во базата на ценовници (1–80). За нов маркет пополнете празен ред.</p>
      <div class="table-scroll">
      <table class="table markets-table">
        <thead><tr><th>Број</th><th>Име</th><th>Тип</th><th>Град</th><th>Избриши</th></tr></thead>
        <tbody>
<?php foreach ($rows as $i => $m): ?>
          <tr>
            <td><input type="number" name="m[<?= $i ?>][number]" value="<?= e($m['number']) ?>" min="1" max="80"></td>
            <td><input type="text" name="m[<?= $i ?>][name]" value="<?= e($m['name']) ?>" maxlength="100"></td>
            <td>
              <select name="m[<?= $i ?>][type]">
<?php foreach (MARKET_TYPES as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= $m['type'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
              </select>
            </td>
            <td><input type="text" name="m[<?= $i ?>][city]" value="<?= e($m['city']) ?>" maxlength="100"></td>
            <td class="center"><?php if ($m['number'] !== ''): ?><input type="checkbox" name="m[<?= $i ?>][delete]" value="1"><?php endif; ?></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <div class="form-foot">
        <button class="button primary" type="submit"><i class="fas fa-floppy-disk"></i> Зачувај</button>
      </div>
    </form>
<?php
admin_footer();
