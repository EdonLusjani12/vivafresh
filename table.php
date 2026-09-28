<?php
// table.php - Optimized version with pagination and server-side filtering

error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Europe/Skopje');

// ==============================================
// 1. ЛОАДИРАЈТЕ ГО КОНФИГУРАЦИСКИОТ ФАЈЛ
// ==============================================

// Директен пат до config фајлот (ист директориум)
$config_file = __DIR__ . '/db_config.php';

if (!file_exists($config_file) || !is_readable($config_file)) {
    showError("Конфигурациската датотека db_config.php не е пронајдена во: " . htmlspecialchars($config_file));
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
    showError("Системска грешка: " . htmlspecialchars($e->getMessage()));
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
// 6. HTML DISPLAY FUNCTION (вашиот оригинален код)
// ==============================================

function displayHTML($marketNumber, $totalProducts, $categories, $updateRow) {
    ?>
    <!DOCTYPE html>
    <html lang="mk">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ценовник - Маркет <?php echo htmlspecialchars($marketNumber); ?> | VivaFresh MK</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            :root { 
                --primary-green: #0d7b3e;
                --primary-dark-green: #095c2d;
                --accent-orange: #ff7a00;
                --accent-yellow: #ffd700;
                --success-green: #28a745;
                --warning-orange: #ffc107;
                --danger-red: #dc3545;
                --light-gray: #f8f9fa;
                --light-green: #e8f5e9;
                --dark-gray: #2d3748;
            }
            * {
                box-sizing: border-box;
            }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                background: #f5f7fa; 
                padding: 20px; 
                margin: 0;
                user-select: none; /* Prevent text selection */
                -webkit-user-select: none; /* Safari */
                -moz-user-select: none; /* Firefox */
                -ms-user-select: none; /* IE/Edge */
            }
            .container { 
                max-width: 1400px; 
                margin: 0 auto; 
                background: white; 
                border-radius: 15px; 
                box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
                overflow: hidden; 
            }
            .header { 
                background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark-green) 100%); 
                color: white; 
                padding: 20px 25px; 
                text-align: center; 
            }
            .header h1 { 
                font-size: 1.8rem; 
                margin: 0 0 10px 0; 
            }
            .header h1 .accent {
                color: var(--accent-yellow);
            }
            .market-info { 
                display: flex; 
                justify-content: center; 
                gap: 15px; 
                flex-wrap: wrap; 
            }
            .info-item { 
                background: rgba(255, 255, 255, 0.2); 
                padding: 6px 15px; 
                border-radius: 20px; 
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 0.9rem;
            }
            .warning { 
                background: #fff8e1; 
                border-left: 5px solid var(--accent-orange); 
                color: #856404; 
                padding: 12px 20px; 
                margin: 0; 
                display: flex; 
                align-items: center; 
                gap: 10px;
                font-size: 0.9rem;
            }
            .controls { 
                padding: 15px 20px; 
                background: var(--light-gray); 
                border-bottom: 2px solid #dee2e6; 
                display: flex; 
                flex-wrap: wrap; 
                gap: 12px; 
                align-items: center;
            }
            .btn { 
                padding: 8px 16px; 
                border-radius: 6px; 
                text-decoration: none; 
                font-weight: 600; 
                display: inline-flex; 
                align-items: center; 
                gap: 6px; 
                transition: all 0.3s ease; 
                border: none; 
                cursor: pointer; 
                font-size: 0.9rem;
            }
            .back-btn { 
                background: var(--primary-green); 
                color: white; 
            }
            .back-btn:hover { 
                background: var(--primary-dark-green); 
                transform: translateY(-2px); 
                box-shadow: 0 4px 8px rgba(13, 123, 62, 0.2);
            }
            .filter-btn {
                background: #6c757d;
                color: white;
            }
            .filter-btn:hover:not(.active) {
                background: #5a6268;
            }
            .filter-btn.active {
                background: var(--accent-orange);
                color: #000;
            }
            .filter-section {
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
                flex-grow: 1;
            }
            .search-box { 
                position: relative; 
                min-width: 200px;
                flex-grow: 1;
                max-width: 400px;
            }
            .search-input { 
                width: 100%; 
                padding: 8px 15px 8px 35px; 
                border: 2px solid #ced4da; 
                border-radius: 6px; 
                font-size: 0.9rem; 
                transition: border-color 0.3s;
                user-select: auto; /* Allow selection in search input */
                -webkit-user-select: auto;
                -moz-user-select: auto;
                -ms-user-select: auto;
            }
            .search-input:focus {
                outline: none;
                border-color: var(--primary-green);
                box-shadow: 0 0 0 3px rgba(13, 123, 62, 0.1);
            }
            .search-icon { 
                position: absolute; 
                left: 10px; 
                top: 50%; 
                transform: translateY(-50%); 
                color: #6c757d; 
            }
            .category-select {
                padding: 8px 15px;
                border: 2px solid #ced4da;
                border-radius: 6px;
                font-size: 0.9rem;
                min-width: 200px;
                flex-grow: 1;
                background: white;
                user-select: auto; /* Allow selection in select */
                -webkit-user-select: auto;
                -moz-user-select: auto;
                -ms-user-select: auto;
            }
            .category-select:focus {
                outline: none;
                border-color: var(--primary-green);
            }
            .content { 
                padding: 0;
            }
            .table-responsive { 
                overflow-x: auto; 
                border: 1px solid #dee2e6; 
                border-radius: 8px; 
                margin: 15px;
                max-height: 600px;
                overflow-y: auto;
            }
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                flex-direction: column;
                gap: 15px;
                display: none;
            }
            .spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid var(--primary-green);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .price-table { 
                width: 100%; 
                border-collapse: collapse; 
                min-width: 1200px; 
                margin: 0;
            }
            .price-table th { 
                background: var(--primary-green); 
                padding: 10px 8px; 
                text-align: left; 
                font-weight: 700; 
                color: white; 
                font-size: 0.85rem; 
                white-space: nowrap;
                position: sticky;
                top: 0;
                z-index: 10;
            }
            .price-table td { 
                padding: 8px; 
                border-bottom: 1px solid #dee2e6; 
                font-size: 0.85rem; 
                vertical-align: middle;
            }
            .price-table tr:hover { 
                background: #e8f5e9 !important; 
            }
            .price-table tr.active { 
                background: #fff8e1; 
                border-left: 3px solid var(--accent-orange);
            }
            .price { 
                color: var(--primary-dark-green); 
                font-weight: 700; 
                text-align: right; 
            }
            .regular-price { 
                color: #6c757d; 
                text-decoration: line-through; 
                text-align: right; 
            }
            .discount-price { 
                color: var(--danger-red); 
                font-weight: 700; 
                text-align: right; 
            }
            .discount-badge { 
                background: var(--danger-red); 
                color: white; 
                padding: 2px 6px; 
                border-radius: 8px; 
                font-size: 0.75rem; 
                font-weight: 600; 
                display: inline-block;
            }
            .available { 
                color: var(--success-green); 
                font-weight: 600; 
                display: flex;
                align-items: center;
                gap: 4px;
                font-size: 0.85rem;
            }
            .not-available { 
                color: var(--danger-red); 
                font-weight: 600; 
                display: flex;
                align-items: center;
                gap: 4px;
                font-size: 0.85rem;
            }
            .akcija { 
                background: #fff8e1; 
                color: #856404;
                font-weight: 600; 
                padding: 2px 6px; 
                border-radius: 4px; 
                font-size: 0.8rem; 
                display: inline-block;
                border: 1px solid #ffecb3;
            }
            .no-data { 
                text-align: center; 
                padding: 40px 20px; 
                color: #6c757d; 
            }
            .footer { 
                text-align: center; 
                padding: 15px; 
                background: var(--light-gray); 
                color: #6c757d; 
                border-top: 2px solid #dee2e6; 
            }
            .category-header { 
                background: #e8f5e9 !important; 
                font-weight: 700; 
                color: var(--primary-dark-green); 
                position: sticky;
                top: 45px;
                z-index: 5;
            }
            .category-header td { 
                padding: 8px 15px; 
                font-size: 0.9rem;
            }
            .counter { 
                font-weight: 600; 
                color: #495057; 
                font-size: 0.85rem;
            }
            .pagination {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 10px;
                padding: 15px;
                background: var(--light-gray);
                border-top: 1px solid #dee2e6;
            }
            .pagination-btn {
                padding: 6px 12px;
                border: 1px solid #ced4da;
                background: white;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.85rem;
                min-width: 36px;
                text-align: center;
                transition: all 0.3s ease;
            }
            .pagination-btn:hover:not(:disabled) {
                background: var(--primary-green);
                color: white;
                border-color: var(--primary-green);
            }
            .pagination-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            .pagination-btn.active {
                background: var(--primary-green);
                color: white;
                border-color: var(--primary-green);
            }
            .page-info {
                font-size: 0.85rem;
                color: #6c757d;
                margin: 0 10px;
            }
            .per-page-select {
                padding: 4px 8px;
                border-radius: 4px;
                border: 1px solid #ced4da;
                font-size: 0.85rem;
                user-select: auto; /* Allow selection in select */
                -webkit-user-select: auto;
                -moz-user-select: auto;
                -ms-user-select: auto;
            }
            .stats-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 20px;
                background: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
                font-size: 0.85rem;
                color: #6c757d;
            }
            .copy-warning {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--danger-red);
                color: white;
                padding: 10px 15px;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 1000;
                font-weight: 600;
                display: none;
                align-items: center;
                gap: 10px;
                animation: fadeIn 0.3s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @media (max-width: 992px) {
                body { padding: 10px; }
                .header h1 { font-size: 1.5rem; }
                .market-info { gap: 10px; }
                .info-item { padding: 5px 12px; font-size: 0.8rem; }
                .controls { flex-direction: column; align-items: stretch; }
                .filter-section { flex-direction: column; align-items: stretch; }
                .search-box, .category-select { max-width: 100%; min-width: 100%; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-file-invoice-dollar"></i> SUPER <span class="accent">VivaFresh</span> MK - Ценовник</h1>
                <div class="market-info">
                    <div class="info-item"><i class="fas fa-store"></i> Маркет <?php echo htmlspecialchars($marketNumber); ?></div>
                    <div class="info-item"><i class="fas fa-box"></i> <span id="productCount"><?php echo number_format($totalProducts); ?></span> производи</div>
                    <div class="info-item"><i class="fas fa-calendar"></i> <?php echo date('d.m.Y'); ?></div>
                </div>
            </div>
            
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>ВАЖНО:</strong> Овој ценовник е само за преглед. Копирање и зачувување не се дозволени.
                </div>
            </div>
            
            <div class="controls">
                <a href="index.html" class="btn back-btn">
                    <i class="fas fa-arrow-left"></i> Назад
                </a>
                
                <div class="filter-section">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Пребарај производ..." id="searchInput" autocomplete="off">
                    </div>
                    
                    <select class="category-select" id="categorySelect">
                        <option value="">Сите категории</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button class="btn filter-btn" id="discountFilter">
                        <i class="fas fa-percentage"></i> Само попусти
                    </button>
                    
                    <select class="per-page-select" id="perPageSelect">
                        <option value="50">50 по страна</option>
                        <option value="100" selected>100 по страна</option>
                        <option value="200">200 по страна</option>
                        <option value="500">500 по страна</option>
                    </select>
                </div>
            </div>
            
            <div class="stats-bar">
                <div>
                    Последно ажурирање: <span id="lastUpdate">
                    <?php 
                        if ($updateRow && $updateRow['last_update']) {
                            $last_update = new DateTime($updateRow['last_update']);
                            $now = new DateTime();
                            $interval = $now->diff($last_update);
                            
                            echo $last_update->format('d.m.Y H:i');
                            
                            if ($interval->d == 0 && $interval->h < 1) {
                                echo " <span style='color: #28a745;'>(пред " . $interval->format('%i минути)</span>');
                            } elseif ($interval->d == 0) {
                                echo " <span style='color: #28a745;'>(пред " . $interval->format('%h часа)</span>');
                            } else {
                                echo " <span style='color: var(--accent-orange);'>(пред " . $interval->format('%a дена)</span>');
                            }
                        } else {
                            echo 'Недостапно';
                        }
                    ?>
                    </span>
                </div>
                <div>
                    Прикажани: <span id="shownProducts">0</span> од <span id="totalProducts"><?php echo number_format($totalProducts); ?></span>
                </div>
            </div>
            
            <div class="content">
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="spinner"></div>
                    <div>Вчитувам податоци...</div>
                </div>
                
                <div class="table-responsive">
                    <table class="price-table" id="priceTable">
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
                        <tbody id="tableBody">
                            <!-- Data will be loaded here via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination" id="pagination">
                    <!-- Pagination will be loaded here -->
                </div>
            </div>
            
            <div class="footer">
                <p><strong>VivaFresh MK</strong> &copy; <?php echo date('Y'); ?> - Сите права се задржани</p>
                <p style="margin-top: 5px; font-size: 0.8rem;">Цените се во денари и може да варираат.</p>
            </div>
        </div>
        
        <!-- Copy warning message -->
        <div class="copy-warning" id="copyWarning">
            <i class="fas fa-ban"></i>
            <span>Копирањето не е дозволено!</span>
        </div>
        
        <script>
            let currentPage = 1;
            let currentPerPage = 100;
            let currentSearch = '';
            let currentCategory = '';
            let currentDiscountOnly = false;
            let currentMarket = <?php echo $marketNumber; ?>;
            let totalProducts = <?php echo $totalProducts; ?>;
            let copyWarningTimeout;
            
            // === SECURITY: DISABLE COPY/PASTE ===
            
            // Disable right-click context menu
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                showCopyWarning();
                return false;
            });
            
            // Disable keyboard shortcuts for copy/cut/paste
            document.addEventListener('keydown', function(e) {
                // Ctrl+C, Ctrl+X, Ctrl+V
                if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'x' || e.key === 'v')) {
                    e.preventDefault();
                    showCopyWarning();
                    return false;
                }
                
                // Print Screen
                if (e.key === 'PrintScreen') {
                    e.preventDefault();
                    showCopyWarning();
                    return false;
                }
                
                // Ctrl+A (select all) - allow only in search inputs
                if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                    const activeElement = document.activeElement;
                    if (!activeElement.classList.contains('search-input') && 
                        !activeElement.classList.contains('category-select') &&
                        !activeElement.classList.contains('per-page-select')) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                // Ctrl+U (view source) - disable
                if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
                    e.preventDefault();
                    showCopyWarning();
                    return false;
                }
                
                // F12 (dev tools) - disable
                if (e.key === 'F12') {
                    e.preventDefault();
                    showCopyWarning();
                    return false;
                }
            });
            
            // Disable text selection on table
            document.addEventListener('selectstart', function(e) {
                if (e.target.classList.contains('search-input') || 
                    e.target.classList.contains('category-select') ||
                    e.target.classList.contains('per-page-select')) {
                    return true; // Allow selection in form inputs
                }
                e.preventDefault();
                return false;
            });
            
            // Disable drag and drop
            document.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });
            
            // Disable image context menu
            document.querySelectorAll('img, table, tr, td').forEach(element => {
                element.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    showCopyWarning();
                    return false;
                });
            });
            
            // Show copy warning message
            function showCopyWarning() {
                const warning = document.getElementById('copyWarning');
                warning.style.display = 'flex';
                
                // Clear any existing timeout
                if (copyWarningTimeout) {
                    clearTimeout(copyWarningTimeout);
                }
                
                // Hide warning after 3 seconds
                copyWarningTimeout = setTimeout(() => {
                    warning.style.display = 'none';
                }, 3000);
            }
            
            // === END SECURITY ===
            
            // Load initial data
            document.addEventListener('DOMContentLoaded', function() {
                loadData();
                
                // Event listeners
document.getElementById('searchInput').addEventListener('input', function() {
    // Store 'this' reference
    const searchInput = this;
    
    debounce(function() {
        currentSearch = searchInput.value;
        currentPage = 1;
        console.log('Searching for:', currentSearch); // Debug
        loadData();
    }, 500)();
});
                
                document.getElementById('categorySelect').addEventListener('change', function() {
                    currentCategory = this.value;
                    currentPage = 1;
                    loadData();
                });
                
                document.getElementById('discountFilter').addEventListener('click', function() {
                    currentDiscountOnly = !currentDiscountOnly;
                    this.classList.toggle('active', currentDiscountOnly);
                    currentPage = 1;
                    loadData();
                });
                
                document.getElementById('perPageSelect').addEventListener('change', function() {
                    currentPerPage = parseInt(this.value);
                    currentPage = 1;
                    loadData();
                });
                
                // Keyboard shortcut for search (Ctrl+F) - still allowed
                document.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                        e.preventDefault();
                        document.getElementById('searchInput').focus().select();
                    }
                });
                
                document.getElementById('searchInput').focus();
            });
            
            // Debounce function
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
            
            // Show/hide loading
            function showLoading() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            }
            
            function hideLoading() {
                document.getElementById('loadingOverlay').style.display = 'none';
            }
            
            // Load data via AJAX
            function loadData() {
                showLoading();
                
                const params = new URLSearchParams({
                    market: 'market' + currentMarket,
                    ajax: '1',
                    page: currentPage,
                    per_page: currentPerPage,
                    search: currentSearch,
                    category: currentCategory,
                    discount: currentDiscountOnly ? '1' : '0'
                });
                
                // Use fetch API
                fetch(`?${params.toString()}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            updateTable(data.products);
                            updatePagination(data.pagination);
                            updateStats(data);
                        } else {
                            showError('Грешка при вчитување на податоци: ' + (data.error || 'Непозната грешка'));
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        showError('Грешка при вчитување на податоци.');
                    })
                    .finally(() => {
                        hideLoading();
                    });
            }
            
            // Update table
            function updateTable(products) {
                const tbody = document.getElementById('tableBody');
                tbody.innerHTML = '';
                
                if (!products || products.length === 0) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td colspan="11" class="no-data">
                            <i class="fas fa-exclamation-circle"></i>
                            <h3>Нема пронајдени производи</h3>
                            <p>Пробајте со различни филтри</p>
                        </td>
                    `;
                    tbody.appendChild(row);
                    return;
                }
                
                let counter = (currentPage - 1) * currentPerPage + 1;
                let currentCat = '';
                
                products.forEach(product => {
                    const hasDiscount = product.popust_procent > 0;
                    const isAkcija = product.akcija_opis !== 'Nema Akcija';
                    
                    // Category header
                    if (product.opis && product.opis !== currentCat) {
                        currentCat = product.opis;
                        const categoryRow = document.createElement('tr');
                        categoryRow.className = 'category-header';
                        categoryRow.innerHTML = `
                            <td colspan="11">
                                <i class="fas fa-folder"></i> ${escapeHtml(currentCat)}
                            </td>
                        `;
                        tbody.appendChild(categoryRow);
                    }
                    
                    const row = document.createElement('tr');
                    if (isAkcija) {
                        row.className = 'active';
                    }
                    
                    row.innerHTML = `
                        <td class="counter">${counter++}</td>
                        <td>${escapeHtml(product.product_name)}</td>
                        <td>${escapeHtml(product.opis || '-')}</td>
<td>
    ${parseInt(product.dostapnost, 10) === 1
        ? '<span class="available"><i class="fas fa-check-circle"></i> DA</span>'
        : '<span class="not-available"><i class="fas fa-times-circle"></i> NE</span>'}
</td>


                        <td class="price">${formatNumber(product.edinecna_cena)} ден.</td>
                        <td class="price">${formatNumber(product.prodazna_cena)} ден.</td>
                        <td class="regular-price">${formatNumber(product.redovna_cena)} ден.</td>
                        <td>
                            ${hasDiscount && product.cena_so_popust ? 
                                `<span class="discount-price">${escapeHtml(product.cena_so_popust)}</span>` : 
                                escapeHtml(product.cena_so_popust || '-')}
                        </td>
                        <td>
                            ${product.popust_procent > 0 ? 
                                `<span class="discount-badge">-${product.popust_procent}%</span>` : 
                                '-'}
                        </td>
                        <td>
                            ${isAkcija ? 
                                `<span class="akcija">${escapeHtml(product.akcija_opis)}</span>` : 
                                escapeHtml(product.akcija_opis)}
                        </td>
                        <td>${escapeHtml(product.vreme_traenje || '-')}</td>
                    `;
                    
                    tbody.appendChild(row);
                });
            }
            
            // Update pagination
            function updatePagination(pagination) {
                const paginationDiv = document.getElementById('pagination');
                const totalPages = pagination.total_pages;
                
                if (totalPages <= 1) {
                    paginationDiv.innerHTML = '';
                    return;
                }
                
                let html = '';
                
                // Previous button
                html += `<button class="pagination-btn" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="fas fa-chevron-left"></i>
                         </button>`;
                
                // Page numbers
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                
                if (endPage - startPage + 1 < maxVisiblePages) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }
                
                if (startPage > 1) {
                    html += `<button class="pagination-btn" onclick="changePage(1)">1</button>`;
                    if (startPage > 2) {
                        html += `<span class="page-info">...</span>`;
                    }
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
                }
                
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        html += `<span class="page-info">...</span>`;
                    }
                    html += `<button class="pagination-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
                }
                
                // Next button
                html += `<button class="pagination-btn" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                            <i class="fas fa-chevron-right"></i>
                         </button>`;
                
                // Page info
                html += `<span class="page-info">
                            Страна ${currentPage} од ${totalPages}
                         </span>`;
                
                paginationDiv.innerHTML = html;
            }
            
            // Update stats
            function updateStats(data) {
                const shown = Math.min(currentPage * currentPerPage, data.pagination.total);
                document.getElementById('shownProducts').textContent = formatNumber(shown);
                document.getElementById('totalProducts').textContent = formatNumber(data.pagination.total);
                document.getElementById('productCount').textContent = formatNumber(data.pagination.total);
            }
            
            // Change page
            function changePage(page) {
                if (page >= 1 && page <= Math.ceil(totalProducts / currentPerPage)) {
                    currentPage = page;
                    loadData();
                    document.querySelector('.table-responsive').scrollTop = 0;
                }
            }
            
            // Utility functions
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            function formatNumber(num) {
                if (!num) return '0.00';
                return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }
            
            function showError(message) {
                showNotification(message, 'error');
            }
            
            function showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? '#28a745' : '#dc3545'};
                    color: white;
                    padding: 12px 20px;
                    border-radius: 8px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                    z-index: 1000;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                `;
                
                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                notification.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        </script>
    </body>
    </html>
    <?php
}

// ==============================================
// 7. ERROR DISPLAY FUNCTION (вашиот оригинален код)
// ==============================================

function showError($message) {
    ?>
    <!DOCTYPE html>
    <html lang="mk">
    <head>
        <title>Грешка | VivaFresh MK</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            :root {
                --primary-green: #0d7b3e;
                --primary-dark-green: #095c2d;
                --accent-orange: #ff7a00;
                --danger-red: #dc3545;
            }
            
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                padding: 40px; 
                text-align: center; 
                background: #f5f5f5; 
                margin: 0;
            }
            .error-container { 
                max-width: 600px; 
                margin: 50px auto; 
                background: white; 
                padding: 40px; 
                border-radius: 10px; 
                box-shadow: 0 0 20px rgba(0,0,0,0.1); 
            }
            .error-icon { 
                font-size: 4rem; 
                color: var(--danger-red); 
                margin-bottom: 20px; 
            }
            .error { 
                background: #fee; 
                border-left: 4px solid var(--danger-red); 
                padding: 20px; 
                margin: 20px 0; 
                text-align: left; 
                border-radius: 4px;
            }
            .back-link { 
                display: inline-block; 
                margin-top: 20px; 
                padding: 12px 25px; 
                background: var(--primary-green); 
                color: white; 
                text-decoration: none; 
                border-radius: 8px; 
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .back-link:hover {
                background: var(--primary-dark-green);
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(13, 123, 62, 0.2);
            }
            h1 { color: #333; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>Грешка</h1>
            <div class="error">
                <strong>Настана грешка:</strong><br>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <a href="marketi.html" class="back-link">
                <i class="fas fa-arrow-left"></i> Назад кон маркети
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}