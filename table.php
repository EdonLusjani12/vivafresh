<?php
// table.php - Optimized version with pagination and server-side filtering

error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Europe/Skopje');

require __DIR__ . '/inc/layout.php';

// ==============================================
// 1. ЛОАДИРАЈТЕ ГО КОНФИГУРАЦИСКИОТ ФАЈЛ
// ==============================================

// Директен пат до config фајлот (ист директориум)
$config_file = __DIR__ . '/db_config.php';

if (!file_exists($config_file) || !is_readable($config_file)) {
    error_log('table.php: db_config.php not found');
    showError("Ценовникот моментално не е достапен. Обидете се подоцна.");
}

// Вчитај го config фајлот
$mysql_config = include $config_file;

// Провери дали конфигурацијата е валидна
if (!is_array($mysql_config) || 
    !isset($mysql_config['host'], $mysql_config['user'], $mysql_config['pass'], $mysql_config['db'])) {
    showError("Невалидна конфигурација. Проверете дали db_config.php враќа низа со клучеви: host, user, pass, db");
}

// ==============================================
// 2. ДАТАБАЗА КОНЕКЦИЈА ФУНКЦИЈА
// ==============================================

function getDatabaseConnection() {
    global $mysql_config;
    
    $conn = mysqli_connect(
        $mysql_config['host'],
        $mysql_config['user'],
        $mysql_config['pass'],
        $mysql_config['db'],
        $mysql_config['port'] ?? 3306
    );
    
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    mysqli_set_charset($conn, $mysql_config['charset'] ?? 'utf8mb4');
    return $conn;
}

// ==============================================
// 3. ГЛАВЕН КОД (вашиот оригинален код)
// ==============================================

try {
    if (isset($_GET['market'])) {
        $market = $_GET['market'];
        
        if (preg_match('/^market(\d+)$/i', $market, $matches)) {
            $marketNumber = (int)$matches[1];
            
            if ($marketNumber >= 1 && $marketNumber <= 80) {
                // Check if it's an AJAX request for pagination/filtering
                if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                    handleAjaxRequest($marketNumber);
                } else {
                    displayPriceListFromMySQL($marketNumber);
                }
            } else {
                showError("Невалиден број на маркет. Мора да биде помеѓу 1 и 80.");
            }
        } else {
            showError("Невалиден формат. Користи: ?market=market1");
        }
    } else {
        showError("Ве молиме наведете маркет: ?market=market1");
    }
} catch (Exception $e) {
    error_log('table.php: ' . $e->getMessage());
    showError("Ценовникот моментално не е достапен. Обидете се подоцна.");
}

// ==============================================
// 4. AJAX HANDLER (вашиот оригинален код)
// ==============================================

function handleAjaxRequest($marketNumber) {
    $conn = getDatabaseConnection();
    
    // Get parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(1000, max(10, (int)$_GET['per_page'])) : 100;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $onlyDiscount = isset($_GET['discount']) ? $_GET['discount'] == '1' : false;
    
    // Calculate offset
    $offset = ($page - 1) * $perPage;
    
    // Build query with filters
    $whereClauses = ["market_id = ?"];
    $params = [$marketNumber];
    $types = "i";
    
    if (!empty($search)) {
        $whereClauses[] = "(product_name LIKE ? OR opis LIKE ?)";
        $searchTerm = "%" . $search . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    if (!empty($category)) {
        $whereClauses[] = "opis = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if ($onlyDiscount) {
        $whereClauses[] = "popust_procent > 0";
    }
    
    $whereSQL = implode(" AND ", $whereClauses);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM price_lists_viva WHERE $whereSQL";
    
    $countStmt = mysqli_prepare($conn, $countQuery);
    if (!$countStmt) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Prepare failed', 'details' => mysqli_error($conn)]);
        exit;
    }
    
    if (count($params) > 0) {
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($countStmt)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Execute failed', 'details' => mysqli_stmt_error($countStmt)]);
        exit;
    }
    
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalRow = mysqli_fetch_assoc($countResult);
    $totalProducts = $totalRow['total'];
    mysqli_stmt_close($countStmt);
    
    // Get data with pagination
    $dataQuery = "SELECT * FROM price_lists_viva WHERE $whereSQL 
                  ORDER BY opis, akcija_opis DESC, product_name 
                  LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";
    
    $dataStmt = mysqli_prepare($conn, $dataQuery);
    if (!$dataStmt) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Data prepare failed', 'details' => mysqli_error($conn)]);
        exit;
    }
    
    if (count($params) > 0) {
        mysqli_stmt_bind_param($dataStmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($dataStmt)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Data execute failed', 'details' => mysqli_stmt_error($dataStmt)]);
        exit;
    }
    
    $result = mysqli_stmt_get_result($dataStmt);
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    // Get unique categories for filter dropdown
    $categoriesQuery = "SELECT DISTINCT opis FROM price_lists_viva WHERE market_id = ? AND opis IS NOT NULL AND opis != '' ORDER BY opis";
    $catStmt = mysqli_prepare($conn, $categoriesQuery);
    mysqli_stmt_bind_param($catStmt, "i", $marketNumber);
    mysqli_stmt_execute($catStmt);
    $catResult = mysqli_stmt_get_result($catStmt);
    
    $categories = [];
    while ($catRow = mysqli_fetch_assoc($catResult)) {
        $categories[] = $catRow['opis'];
    }
    mysqli_stmt_close($catStmt);
    
    // Get last update
    $updateQuery = "SELECT MAX(updated_at) as last_update FROM price_lists_viva WHERE market_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "i", $marketNumber);
    mysqli_stmt_execute($updateStmt);
    $updateResult = mysqli_stmt_get_result($updateStmt);
    $updateRow = mysqli_fetch_assoc($updateResult);
    mysqli_stmt_close($updateStmt);
    
    mysqli_close($conn);
    
    // Prepare response
    $response = [
        'success' => true,
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $totalProducts,
            'total_pages' => ceil($totalProducts / $perPage)
        ],
        'filters' => [
            'categories' => $categories,
            'search' => $search,
            'category' => $category,
            'only_discount' => $onlyDiscount
        ],
        'last_update' => $updateRow['last_update'] ?? null
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// ==============================================
// 5. INITIAL DISPLAY FUNCTION (вашиот оригинален код)
// ==============================================

function displayPriceListFromMySQL($marketNumber) {
    $conn = getDatabaseConnection();
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM price_lists_viva WHERE market_id = ?";
    $countStmt = mysqli_prepare($conn, $countQuery);
    mysqli_stmt_bind_param($countStmt, "i", $marketNumber);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalRow = mysqli_fetch_assoc($countResult);
    $totalProducts = $totalRow['total'];
    mysqli_stmt_close($countStmt);
    
    // Get unique categories
    $catQuery = "SELECT DISTINCT opis FROM price_lists_viva WHERE market_id = ? AND opis IS NOT NULL AND opis != '' ORDER BY opis";
    $catStmt = mysqli_prepare($conn, $catQuery);
    mysqli_stmt_bind_param($catStmt, "i", $marketNumber);
    mysqli_stmt_execute($catStmt);
    $catResult = mysqli_stmt_get_result($catStmt);
    
    $categories = [];
    while ($catRow = mysqli_fetch_assoc($catResult)) {
        $categories[] = $catRow['opis'];
    }
    mysqli_stmt_close($catStmt);
    
    // Get last update
    $updateQuery = "SELECT MAX(updated_at) as last_update FROM price_lists_viva WHERE market_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "i", $marketNumber);
    mysqli_stmt_execute($updateStmt);
    $updateResult = mysqli_stmt_get_result($updateStmt);
    $updateRow = mysqli_fetch_assoc($updateResult);
    mysqli_stmt_close($updateStmt);
    
    mysqli_close($conn);
    
    displayHTML($marketNumber, $totalProducts, $categories, $updateRow);
}


// ==============================================
// 6. HTML DISPLAY FUNCTION
// ==============================================

function displayHTML($marketNumber, $totalProducts, $categories, $updateRow) {
    $lastUpdate = 'Недостапно';
    $lastUpdateAgo = '';
    $lastUpdateFresh = true;
    if ($updateRow && $updateRow['last_update']) {
        $last = new DateTime($updateRow['last_update']);
        $interval = (new DateTime())->diff($last);
        $lastUpdate = $last->format('d.m.Y H:i');
        if ($interval->days == 0 && $interval->h < 1) {
            $lastUpdateAgo = 'пред ' . $interval->i . ' минути';
        } elseif ($interval->days == 0) {
            $lastUpdateAgo = 'пред ' . $interval->h . ' часа';
        } else {
            $lastUpdateAgo = 'пред ' . $interval->days . ' дена';
            $lastUpdateFresh = false;
        }
    }

    ob_start();
    ?>
  <script>
    let currentPage = 1;
    let currentPerPage = 100;
    let currentSearch = '';
    let currentCategory = '';
    let currentDiscountOnly = false;
    let currentTotalPages = 1;
    const currentMarket = <?php echo (int) $marketNumber; ?>;
    let copyWarningTimeout;

    // === Copy protection ===
    function isFormControl(el) {
      return el && el.classList && (el.classList.contains('search-input') || el.classList.contains('pl-select'));
    }

    document.addEventListener('contextmenu', function (e) {
      e.preventDefault();
      showCopyWarning();
    });

    document.addEventListener('keydown', function (e) {
      const key = e.key.toLowerCase();
      const mod = e.ctrlKey || e.metaKey;
      if (mod && key === 'f') {
        e.preventDefault();
        const input = document.getElementById('searchInput');
        input.focus();
        input.select();
        return;
      }
      if ((mod && (key === 'c' || key === 'x' || key === 'u')) || e.key === 'PrintScreen' || e.key === 'F12') {
        if (mod && key === 'c' && isFormControl(document.activeElement)) return;
        e.preventDefault();
        showCopyWarning();
        return;
      }
      if (mod && key === 'a' && !isFormControl(document.activeElement)) {
        e.preventDefault();
      }
    });

    document.addEventListener('selectstart', function (e) {
      if (!isFormControl(e.target)) e.preventDefault();
    });

    document.addEventListener('dragstart', function (e) {
      e.preventDefault();
    });

    function showCopyWarning() {
      const warning = document.getElementById('copyWarning');
      warning.classList.add('show');
      clearTimeout(copyWarningTimeout);
      copyWarningTimeout = setTimeout(function () { warning.classList.remove('show'); }, 3000);
    }

    // === Data loading ===
    document.addEventListener('DOMContentLoaded', function () {
      loadData();

      let searchTimer;
      document.getElementById('searchInput').addEventListener('input', function () {
        const value = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
          currentSearch = value;
          currentPage = 1;
          loadData();
        }, 400);
      });

      document.getElementById('categorySelect').addEventListener('change', function () {
        currentCategory = this.value;
        currentPage = 1;
        loadData();
      });

      document.getElementById('discountFilter').addEventListener('click', function () {
        currentDiscountOnly = !currentDiscountOnly;
        this.classList.toggle('active', currentDiscountOnly);
        this.setAttribute('aria-pressed', currentDiscountOnly ? 'true' : 'false');
        currentPage = 1;
        loadData();
      });

      document.getElementById('perPageSelect').addEventListener('change', function () {
        currentPerPage = parseInt(this.value, 10);
        currentPage = 1;
        loadData();
      });
    });

    function setLoading(on) {
      document.getElementById('loadingOverlay').classList.toggle('show', on);
    }

    function loadData() {
      setLoading(true);
      const params = new URLSearchParams({
        market: 'market' + currentMarket,
        ajax: '1',
        page: currentPage,
        per_page: currentPerPage,
        search: currentSearch,
        category: currentCategory,
        discount: currentDiscountOnly ? '1' : '0'
      });

      fetch('?' + params.toString())
        .then(function (response) {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.json();
        })
        .then(function (data) {
          if (!data.success) {
            showNotification('Грешка при вчитување на податоци: ' + (data.error || 'Непозната грешка'));
            return;
          }
          currentTotalPages = Math.max(1, data.pagination.total_pages);
          updateTable(data.products);
          updatePagination(data.pagination);
          updateStats(data);
        })
        .catch(function (error) {
          console.error('Fetch error:', error);
          showNotification('Грешка при вчитување на податоци.');
        })
        .finally(function () {
          setLoading(false);
        });
    }

    function updateTable(products) {
      const tbody = document.getElementById('tableBody');
      tbody.innerHTML = '';

      if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="no-data"><i class="fas fa-basket-shopping"></i><h3>Нема пронајдени производи</h3><p>Пробајте со различни филтри.</p></td></tr>';
        return;
      }

      let counter = (currentPage - 1) * currentPerPage + 1;
      let currentCat = '';

      products.forEach(function (product) {
        const hasDiscount = product.popust_procent > 0;
        const isAkcija = product.akcija_opis !== 'Nema Akcija';

        if (product.opis && product.opis !== currentCat) {
          currentCat = product.opis;
          const categoryRow = document.createElement('tr');
          categoryRow.className = 'category-header';
          categoryRow.innerHTML = '<td colspan="11"><i class="fas fa-folder-open"></i> ' + escapeHtml(currentCat) + '</td>';
          tbody.appendChild(categoryRow);
        }

        const row = document.createElement('tr');
        if (isAkcija) row.className = 'active';

        row.innerHTML = `
          <td class="counter">${counter++}</td>
          <td class="product">${escapeHtml(product.product_name)}</td>
          <td>${escapeHtml(product.opis || '-')}</td>
          <td>${parseInt(product.dostapnost, 10) === 1
            ? '<span class="available"><i class="fas fa-check-circle"></i> DA</span>'
            : '<span class="not-available"><i class="fas fa-times-circle"></i> NE</span>'}</td>
          <td class="price">${formatNumber(product.edinecna_cena)} ден.</td>
          <td class="price">${formatNumber(product.prodazna_cena)} ден.</td>
          <td class="regular-price">${formatNumber(product.redovna_cena)} ден.</td>
          <td>${hasDiscount && product.cena_so_popust
            ? `<span class="discount-price">${escapeHtml(product.cena_so_popust)}</span>`
            : escapeHtml(product.cena_so_popust || '-')}</td>
          <td>${hasDiscount ? `<span class="discount-badge">-${escapeHtml(product.popust_procent)}%</span>` : '-'}</td>
          <td>${isAkcija ? `<span class="akcija">${escapeHtml(product.akcija_opis)}</span>` : escapeHtml(product.akcija_opis)}</td>
          <td>${escapeHtml(product.vreme_traenje || '-')}</td>
        `;
        tbody.appendChild(row);
      });
    }

    function updatePagination(pagination) {
      const container = document.getElementById('pagination');
      const totalPages = pagination.total_pages;

      if (totalPages <= 1) {
        container.innerHTML = '';
        return;
      }

      let html = `<button class="pagination-btn" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} aria-label="Претходна"><i class="fas fa-chevron-left"></i></button>`;

      const maxVisible = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
      let endPage = Math.min(totalPages, startPage + maxVisible - 1);
      if (endPage - startPage + 1 < maxVisible) startPage = Math.max(1, endPage - maxVisible + 1);

      if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="changePage(1)">1</button>`;
        if (startPage > 2) html += `<span class="page-info">…</span>`;
      }
      for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
      }
      if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<span class="page-info">…</span>`;
        html += `<button class="pagination-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
      }

      html += `<button class="pagination-btn" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} aria-label="Следна"><i class="fas fa-chevron-right"></i></button>`;
      html += `<span class="page-info">Страна ${currentPage} од ${totalPages}</span>`;
      container.innerHTML = html;
    }

    function updateStats(data) {
      const shown = Math.min(currentPage * currentPerPage, data.pagination.total);
      document.getElementById('shownProducts').textContent = formatInt(shown);
      document.getElementById('totalProducts').textContent = formatInt(data.pagination.total);
    }

    function changePage(page) {
      if (page >= 1 && page <= currentTotalPages) {
        currentPage = page;
        loadData();
        document.getElementById('tableScroll').scrollTop = 0;
      }
    }

    function escapeHtml(text) {
      if (text === null || text === undefined) return '';
      const div = document.createElement('div');
      div.textContent = String(text);
      return div.innerHTML;
    }

    function formatNumber(num) {
      if (!num) return '0.00';
      return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatInt(num) {
      return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function showNotification(message) {
      const toast = document.createElement('div');
      toast.className = 'pl-toast';
      toast.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + escapeHtml(message);
      document.body.appendChild(toast);
      setTimeout(function () { toast.remove(); }, 5000);
    }
  </script>
<?php
    $script = ob_get_clean();

    site_header('Ценовник – Маркет ' . (int) $marketNumber, 'cenovnik');
    ?>
  <main class="wrap price-list">
    <section class="page-hero">
      <span class="kicker"><i class="fas fa-store"></i> Маркет <?php echo (int) $marketNumber; ?></span>
      <h1>Ценовник <em><?php echo t('site.brand'); ?></em></h1>
      <p>Цени, попусти и акции за овој маркет. Цените се во денари.</p>
      <div class="pl-chips">
        <span class="pl-chip"><i class="fas fa-box"></i> <?php echo number_format($totalProducts, 0, ',', '.'); ?> производи</span>
        <span class="pl-chip"><i class="fas fa-calendar"></i> <?php echo date('d.m.Y'); ?></span>
        <span class="pl-chip"><i class="fas fa-rotate"></i> Ажурирано: <?php echo e($lastUpdate); ?>
<?php if ($lastUpdateAgo !== ''): ?>
          <small class="<?php echo $lastUpdateFresh ? 'fresh' : 'stale'; ?>">(<?php echo e($lastUpdateAgo); ?>)</small>
<?php endif; ?>
        </span>
      </div>
    </section>

    <div class="notice"><i class="fas fa-triangle-exclamation"></i> <strong>Важно:</strong> Овој ценовник е само за преглед. Копирање и зачувување не се дозволени.</div>

    <div class="card pl-controls">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" class="search-input" id="searchInput" placeholder="Пребарај производ..." autocomplete="off">
      </div>
      <select class="pl-select" id="categorySelect" aria-label="Категорија">
        <option value="">Сите категории</option>
<?php foreach ($categories as $category): ?>
        <option value="<?php echo e($category); ?>"><?php echo e($category); ?></option>
<?php endforeach; ?>
      </select>
      <button class="pl-toggle" id="discountFilter" type="button" aria-pressed="false"><i class="fas fa-percent"></i> Само попусти</button>
      <select class="pl-select pl-select-small" id="perPageSelect" aria-label="Производи по страна">
        <option value="50">50 по страна</option>
        <option value="100" selected>100 по страна</option>
        <option value="200">200 по страна</option>
        <option value="500">500 по страна</option>
      </select>
    </div>

    <div class="pl-meta">
      <a href="/cenovnik.php"><i class="fas fa-arrow-left"></i> Сите маркети</a>
      <span>Прикажани: <b id="shownProducts">0</b> од <b id="totalProducts"><?php echo number_format($totalProducts, 0, ',', '.'); ?></b></span>
    </div>

    <div class="card pl-card">
      <div class="pl-loading" id="loadingOverlay">
        <div class="pl-spinner"></div>
        <div>Вчитувам податоци...</div>
      </div>
      <div class="pl-scroll" id="tableScroll">
        <table class="pl-table" id="priceTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Производ</th>
              <th>Категорија</th>
              <th>Достапност</th>
              <th>Единечна цена</th>
              <th>Продажна цена</th>
              <th>Редовна цена</th>
              <th>Цена со попуст</th>
              <th>Попуст</th>
              <th>Тип на акција</th>
              <th>Време на траење</th>
            </tr>
          </thead>
          <tbody id="tableBody"></tbody>
        </table>
      </div>
      <div class="pl-pagination" id="pagination"></div>
    </div>
  </main>

  <div class="copy-warning" id="copyWarning" role="alert">
    <i class="fas fa-ban"></i> Копирањето не е дозволено!
  </div>
<?php
    site_footer($script);
}

// ==============================================
// 7. ERROR DISPLAY FUNCTION
// ==============================================

function showError($message) {
    http_response_code(400);
    site_header('Грешка', 'cenovnik');
    ?>
  <main class="wrap">
    <section class="page-hero">
      <span class="kicker"><i class="fas fa-circle-exclamation"></i> Грешка</span>
      <h1>Ценовникот не е <em>достапен</em></h1>
      <p><?php echo e($message); ?></p>
      <div class="actions" style="justify-content:center">
        <a class="btn btn-primary" href="/cenovnik.php"><i class="fas fa-arrow-left"></i> Назад кон маркети</a>
      </div>
    </section>
  </main>
<?php
    site_footer();
    exit();
}
