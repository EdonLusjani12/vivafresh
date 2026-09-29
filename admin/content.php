<?php
require __DIR__ . '/../inc/admin.php';
require __DIR__ . '/../inc/media.php';

require_admin();
admin_db();

$schema = content_schema();
$sectionId = (string) ($_GET['section'] ?? 'site');
if (!isset($schema[$sectionId])) {
    $sectionId = 'site';
}
$section = $schema[$sectionId];
$self = '/admin/content.php?section=' . urlencode($sectionId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('Датотеките се преголеми за серверот. Прикачете помали слики.', 'error');
        redirect($self);
    }
    require_post_csrf();

    $current = content_all();
    $values = (array) ($_POST['f'] ?? []);
    $resets = (array) ($_POST['reset'] ?? []);
    $errors = [];
    $changed = 0;

    foreach ($section['fields'] as $key => $field) {
        if ($field['type'] === 'image') {
            $old = $current[$key];
            $error = $_FILES['img']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

            if (!empty($resets[$key])) {
                content_delete($key);
                delete_media_if_unused($old);
                $changed++;
            } elseif ($error !== UPLOAD_ERR_NO_FILE) {
                try {
                    $path = save_uploaded_image([
                        'error' => $error,
                        'tmp_name' => $_FILES['img']['tmp_name'][$key],
                        'size' => $_FILES['img']['size'][$key],
                    ]);
                    content_save($key, $path);
                    delete_media_if_unused($old);
                    $changed++;
                } catch (RuntimeException $ex) {
                    $errors[] = $field['label'] . ': ' . $ex->getMessage();
                }
            }
            continue;
        }

        if (!array_key_exists($key, $values)) {
            continue;
        }
        $value = trim(str_replace("\r\n", "\n", (string) $values[$key]));
        $value = mb_substr($value, 0, $field['type'] === 'textarea' ? 3000 : 1000);

        if ($field['type'] === 'url' && $value !== '' && !preg_match('~^https?://~i', $value)) {
            $errors[] = $field['label'] . ': линкот мора да почнува со https://';
            continue;
        }
        if ($field['type'] === 'icon' && !preg_match('~^fa-[a-z0-9-]+$~', $value)) {
            $errors[] = $field['label'] . ': невалидно име на икона (пр. fa-leaf).';
            continue;
        }
        if ($key === 'home.video_url' && $value !== '' && youtube_id($value) === '') {
            $errors[] = $field['label'] . ': ова не е YouTube линк.';
            continue;
        }
        if ($value === $current[$key]) {
            continue;
        }

        if ($value === $field['default']) {
            content_delete($key);
        } else {
            content_save($key, $value);
        }
        $changed++;
    }

    foreach ($errors as $error) {
        flash($error, 'error');
    }
    if ($changed > 0) {
        flash('Промените се зачувани.');
    } elseif (!$errors) {
        flash('Нема промени.', 'info');
    }
    redirect($self);
}

$values = content_all();

admin_header('Содржина: ' . $section['label'], 'content');
?>
    <h1>Содржина и слики</h1>
    <nav class="tabs">
<?php foreach ($schema as $id => $s): ?>
      <a class="<?= $id === $sectionId ? 'active' : '' ?>" href="/admin/content.php?section=<?= e($id) ?>"><?= e($s['label']) ?></a>
<?php endforeach; ?>
    </nav>

    <form class="panel" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <p class="muted"><?= e($section['description']) ?></p>

<?php foreach ($section['fields'] as $key => $field): ?>
<?php $value = $values[$key]; $id = 'f_' . str_replace('.', '_', $key); ?>
      <div class="field field-<?= e($field['type']) ?>">
        <label for="<?= e($id) ?>"><?= e($field['label']) ?></label>
<?php if ($field['type'] === 'image'): ?>
        <div class="image-field">
          <img src="<?= e(asset($value)) ?>" alt="">
          <div>
            <input id="<?= e($id) ?>" type="file" name="img[<?= e($key) ?>]" accept="image/jpeg,image/png,image/webp,image/gif">
            <small>JPG, PNG, WEBP или GIF, макс. 5 MB.</small>
<?php if ($value !== $field['default']): ?>
            <label class="check"><input type="checkbox" name="reset[<?= e($key) ?>]" value="1"> Врати ја оригиналната слика</label>
<?php endif; ?>
          </div>
        </div>
<?php elseif ($field['type'] === 'textarea'): ?>
        <textarea id="<?= e($id) ?>" name="f[<?= e($key) ?>]" rows="3" maxlength="3000"><?= e($value) ?></textarea>
<?php elseif ($field['type'] === 'icon'): ?>
        <div class="icon-field">
          <span class="icon-preview"><i class="fas <?= e($value) ?>"></i></span>
          <input id="<?= e($id) ?>" type="text" name="f[<?= e($key) ?>]" value="<?= e($value) ?>" maxlength="60" pattern="fa-[a-z0-9-]+" required>
        </div>
<?php else: ?>
        <input id="<?= e($id) ?>" type="<?= $field['type'] === 'url' ? 'url' : 'text' ?>" name="f[<?= e($key) ?>]" value="<?= e($value) ?>" maxlength="1000">
<?php endif; ?>
<?php if ($field['help'] !== ''): ?>
        <small><?= e($field['help']) ?></small>
<?php endif; ?>
      </div>
<?php endforeach; ?>

      <div class="form-foot">
        <button class="button primary" type="submit"><i class="fas fa-floppy-disk"></i> Зачувај</button>
      </div>
    </form>
    <script>
      document.querySelectorAll(".icon-field input").forEach(function (input) {
        input.addEventListener("input", function () {
          input.previousElementSibling.querySelector("i").className = "fas " + input.value.trim();
        });
      });
      document.querySelectorAll(".image-field input[type=file]").forEach(function (input) {
        input.addEventListener("change", function () {
          if (input.files && input.files[0]) {
            input.closest(".image-field").querySelector("img").src = URL.createObjectURL(input.files[0]);
          }
        });
      });
    </script>
<?php
admin_footer();
