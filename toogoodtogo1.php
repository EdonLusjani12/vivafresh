<?php
// Path to the directory containing the JSON files
$directory = 'vivafresh.mk/vivafre2/';

// Check if the directory exists
if (!is_dir($directory)) {
    die("Directory does not exist.");
}

// Get list of JSON files in the directory
$files = glob($directory . "*.json");

// Check if there are files to process
if (count($files) == 0) {
    die("No JSON files found in the directory.");
}

// Build an array for products and to collect all cities
$products = [];
$cities = [];

foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true);
    if (!$data) continue;
    
    $products[] = $data;
    
    // Loop through the prices array to get cities
    if (isset($data['prices']) && is_array($data['prices'])) {
        foreach ($data['prices'] as $priceInfo) {
            if (!empty($priceInfo['city'])) {
                $cities[] = $priceInfo['city'];
            }
        }
    }
}

// Create a unique list of cities and sort alphabetically
$uniqueCities = array_unique($cities);
sort($uniqueCities);
?>
<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Too Good To Go - Viva Fresh Store MK</title>
    
    <!-- Bootstrap & Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="icon" href="Logo.png" type="image/png">
    
    <style>
        /* ===== VIVA FRESH COLOR THEME ===== */
        :root {
            --primary-green: #0d7b3e;
            --primary-dark-green: #095c2d;
            --primary-light-green: #e8f5e9;
            --accent-orange: #ff7a00;
            --accent-yellow: #ffd700;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --dark-gray: #2d3748;
            --gradient-green: linear-gradient(135deg, #0d7b3e 0%, #095c2d 100%);
            --gradient-orange: linear-gradient(135deg, #ff7a00 0%, #ff5500 100%);
            --shadow-sm: 0 4px 6px rgba(0,0,0,0.07);
            --shadow-md: 0 8px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 15px 30px rgba(0,0,0,0.15);
            --border-radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--white);
            color: var(--dark-gray);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ===== MODERN NAVIGATION ===== */
        .navbar-modern {
            background: var(--gradient-green);
            padding: 0.8rem 1.5rem;
            box-shadow: var(--shadow-md);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .navbar-modern.scrolled {
            padding: 0.5rem 1.5rem;
            background: rgba(13, 123, 62, 0.98);
            backdrop-filter: blur(10px);
        }
        
        .navbar-brand-modern {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }
        
        .logo-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }
        
        .logo-img:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-md);
        }
        
        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }
        
        .brand-main {
            color: var(--white);
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        
        .brand-sub {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            margin-top: 2px;
            font-weight: 400;
        }
        
        /* Navigation Links */
        .navbar-nav {
            display: flex;
            align-items: center;
        }
        
        .nav-item {
            margin: 0 3px;
        }
        
        .nav-link-modern {
            color: var(--white) !important;
            font-weight: 600;
            padding: 0.5rem 1.2rem !important;
            border-radius: 50px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .nav-link-modern:hover {
            color: var(--white) !important;
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .nav-link-modern.active {
            background: var(--accent-orange);
            box-shadow: var(--shadow-sm);
        }
        
        .nav-link-modern.active:hover {
            background: #ff5500;
        }
        
        .nav-link-modern i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }
        
        .nav-link-modern:hover i {
            transform: scale(1.2);
        }
        
        /* Social Icons */
        .social-icons-modern {
            display: flex;
            gap: 12px;
            margin-left: 20px;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.2rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .social-icon:hover {
            background: var(--accent-orange);
            transform: translateY(-3px) rotate(5deg);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }
        
        /* Mobile Menu Button */
        .navbar-toggler {
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            transition: all 0.3s ease;
        }
        
        .navbar-toggler:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .navbar-toggler:focus {
            outline: none;
            box-shadow: none;
        }
        
        /* Mobile Menu */
        @media (max-width: 991px) {
            .navbar-collapse {
                background: var(--gradient-green);
                padding: 20px;
                border-radius: var(--border-radius);
                margin-top: 15px;
                box-shadow: var(--shadow-lg);
            }
            
            .nav-link-modern {
                padding: 12px 20px !important;
                margin: 5px 0;
                border-radius: var(--border-radius);
            }
            
            .social-icons-modern {
                justify-content: center;
                margin: 20px 0 0 0;
            }
        }
        
        /* ===== TOO GOOD TO GO HERO SECTION ===== */
        .tgtg-hero {
            background: var(--gradient-green);
            color: var(--white);
            padding: 150px 0 80px;
            position: relative;
            overflow: hidden;
            margin-top: 0;
            text-align: center;
        }
        
        .tgtg-hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle cx="100" cy="100" r="80" fill="%23ffffff10"/></svg>');
            border-radius: 50%;
        }
        
        .tgtg-badge {
            display: inline-block;
            background: var(--accent-orange);
            color: var(--white);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
            box-shadow: var(--shadow-md);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .tgtg-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .tgtg-title span {
            color: var(--accent-yellow);
            text-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .tgtg-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* ===== PROMO IMAGE SECTION ===== */
        .promo-image-section {
            padding: 40px 0;
            background: var(--white);
            text-align: center;
        }
        
        .promo-image {
            max-width: 400px;
            width: 90%;
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            animation: fadeInScale 1.2s ease-out;
        }
        
        @keyframes fadeInScale {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* ===== CITY FILTER SECTION ===== */
        .filter-section {
            padding: 40px 0;
            background: var(--light-gray);
            text-align: center;
        }
        
        .filter-label {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-dark-green);
            margin-bottom: 15px;
            display: block;
        }
        
        .filter-select {
            max-width: 300px;
            margin: 0 auto;
            border: 2px solid var(--primary-green);
            border-radius: var(--border-radius);
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 500;
            color: var(--dark-gray);
            background: var(--white);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .filter-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 123, 62, 0.1);
            border-color: var(--accent-orange);
        }
        
        /* ===== PRODUCTS SECTION ===== */
        .products-section {
            padding: 60px 0;
            background: var(--white);
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-dark-green);
            margin-bottom: 3rem;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent-orange);
            border-radius: 2px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .product-card-modern {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .product-card-modern:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-green);
        }
        
        .product-image {
            width: 200px;
            height: 200px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .product-card-modern:hover .product-image {
            transform: scale(1.05);
        }
        
        .product-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-dark-green);
            margin-bottom: 10px;
        }
        
        .discount-badge {
            display: inline-block;
            background: var(--gradient-orange);
            color: var(--white);
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 15px;
        }
        
        .price-button {
            background: var(--gradient-green);
            color: var(--white);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            width: 100%;
            justify-content: center;
        }
        
        .price-button:hover {
            background: var(--gradient-orange);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            color: var(--white);
        }
        
        .price-dropdown {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--primary-green);
            z-index: 10;
            transform: translateY(100%);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .price-dropdown.show {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .price-item:last-child {
            border-bottom: none;
        }
        
        .price-market {
            font-weight: 600;
            color: var(--primary-dark-green);
        }
        
        .price-value {
            color: var(--accent-orange);
            font-weight: 700;
        }
        
        .price-city {
            color: var(--dark-gray);
            font-size: 0.9rem;
            margin-left: 10px;
        }
        
        /* ===== PROMO MESSAGE SECTION ===== */
        .promo-message {
            padding: 40px 0;
            background: linear-gradient(135deg, rgba(13, 123, 62, 0.05) 0%, rgba(9, 92, 45, 0.02) 100%);
            text-align: center;
        }
        
        .promo-text {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-dark-green);
            font-style: italic;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .promo-note {
            font-size: 1.1rem;
            color: var(--accent-orange);
            font-weight: 700;
            margin-top: 20px;
            display: block;
        }
        
        /* ===== CONTACT INFO SECTION ===== */
        .contact-info-section {
            padding: 80px 0;
            background: var(--white);
        }
        
        .contact-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 3rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--primary-green);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .contact-icon {
            font-size: 3.5rem;
            color: var(--primary-green);
            margin-bottom: 20px;
        }
        
        .contact-title {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .contact-subtitle {
            font-size: 1.1rem;
            color: var(--dark-gray);
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .phone-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 1.5rem 0;
            color: var(--accent-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .phone-number i {
            color: var(--primary-green);
            font-size: 2rem;
        }
        
        .contact-methods {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .contact-method {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--white);
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: var(--shadow-md);
        }
        
        .contact-method:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: var(--shadow-lg);
        }
        
        .contact-method.viber {
            background: #7360F2;
        }
        
        .contact-method.whatsapp {
            background: #25D366;
        }
        
        .contact-method.email {
            background: var(--accent-orange);
        }
        
        .contact-method.phone {
            background: var(--primary-green);
        }
        
        /* ===== FOOTER ===== */
        .footer-modern {
            background: var(--primary-dark-green);
            color: var(--white);
            padding: 3rem 0 1.5rem;
        }
        
        .footer-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.5rem;
            border: 3px solid var(--white);
        }
        
        .footer-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--accent-yellow);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--accent-yellow);
            padding-left: 5px;
        }
        
        .footer-contact li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .footer-contact i {
            color: var(--accent-yellow);
            width: 20px;
        }
        
        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .dev-links {
            margin-top: 10px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .dev-links a {
            color: var(--accent-yellow);
            text-decoration: none;
            font-weight: 500;
            margin: 0 5px;
        }
        
        .dev-links a:hover {
            text-decoration: underline;
        }
        
        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 992px) {
            .tgtg-title {
                font-size: 2.8rem;
            }
            
            .section-title {
                font-size: 2.2rem;
            }
            
            .brand-main {
                font-size: 1.5rem;
            }
            
            .brand-sub {
                font-size: 0.8rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .phone-number {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .brand-sub {
                display: none;
            }
            
            .tgtg-title {
                font-size: 2.2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .product-card-modern {
                padding: 20px;
            }
            
            .product-image {
                width: 180px;
                height: 180px;
            }
            
            .contact-card {
                padding: 2rem;
            }
            
            .phone-number {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }
            
            .contact-methods {
                gap: 15px;
            }
            
            .contact-method {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
            
            .navbar-modern {
                padding: 0.5rem 1rem;
            }
            
            .logo-img {
                width: 50px;
                height: 50px;
            }
            
            .brand-main {
                font-size: 1.3rem;
            }
        }
        
        @media (max-width: 576px) {
            .tgtg-title {
                font-size: 1.8rem;
            }
            
            .tgtg-hero {
                padding: 120px 0 60px;
            }
            
            .promo-image {
                max-width: 90%;
            }
            
            .filter-select {
                max-width: 100%;
            }
        }
        
        /* Body padding for fixed navbar */
        body {
            padding-top: 80px;
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
        }
    </style>
</head>

<body>
    <!-- Modern Navigation -->
    <nav class="navbar navbar-expand-lg navbar-modern" id="navbar">
        <div class="container">
            <a class="navbar-brand-modern" href="index.html">
                <img src="Logo.png" alt="Viva Fresh Store MK" class="logo-img">
                <div class="brand-text">
                    <div class="brand-main">VIVA FRESH STORE MK</div>
                    
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars text-white"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link-modern" href="index.html">
                            <i class="fas fa-home"></i> Дома
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-modern" href="index.html#contact">
                            <i class="fas fa-phone"></i> Контакт
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-modern" href="vrabotuvanje.html">
                            <i class="fas fa-briefcase"></i> Вработување
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-modern" href="katalog.html">
                            <i class="fas fa-list"></i> Каталог
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-modern" href="cenovnik.html">
                            <i class="fas fa-tag"></i> Ценовници
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-modern active" href="toogoodtogo1.php">
                            <i class="fas fa-leaf"></i> Too Good To Go
                        </a>
                    </li>
                </ul>
                
                <div class="social-icons-modern">
                    <a href="https://www.instagram.com/vivafreshmk/" class="social-icon" target="_blank" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.facebook.com/freshstoremk" class="social-icon" target="_blank" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Too Good To Go Hero Section -->
    <section class="tgtg-hero">
        <div class="container">
            <div class="tgtg-badge">🔥 ТОП ПОНУДА!</div>
            <h1 class="tgtg-title">Too Good To Go</h1>
            <p class="tgtg-subtitle">
                Одлични промоции секој ден! Искористете ги нашите акции и дополнителни попусти на избрани артикли.
            </p>
        </div>
    </section>



    <!-- Section Title -->
    <section class="products-section">
        <div class="container">
            <h2 class="section-title">ЗА ДЕНЕС ИЗДВОЈУВАМЕ:</h2>
            
            <!-- City Filter -->
            <div class="filter-section">
                <label for="cityFilter" class="filter-label">Филтрирај по град:</label>
                <select id="cityFilter" class="filter-select">
                    <option value="all">Сите градови</option>
                    <?php foreach ($uniqueCities as $city): ?>
                        <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Products Grid -->
            <div class="products-grid" id="productsContainer">
                <?php
                foreach ($products as $product) {
                    $name = htmlspecialchars($product['name']);
                    $discount = htmlspecialchars($product['discount']);
                    $image = 'images/' . htmlspecialchars($product['image']);
                    $prices = $product['prices'];

                    $productCities = [];
                    if (is_array($prices)) {
                        foreach ($prices as $priceInfo) {
                            if (!empty($priceInfo['city'])) {
                                $productCities[] = $priceInfo['city'];
                            }
                        }
                    }
                    $dataCities = htmlspecialchars(implode(',', $productCities));
                ?>
                    <div class="product-card-modern" data-cities="<?= $dataCities ?>">
                        <img src="<?php echo $image; ?>" 
                             alt="<?php echo $name; ?>" 
                             class="product-image"
                             onerror="this.onerror=null;this.src='noimage.jpg';" />
                        <div class="product-name"><?php echo $name; ?></div>
                        <div class="discount-badge"><?php echo $discount; ?></div>
                        <button class="price-button" onclick="togglePriceDropdown(this)">
                            <i class="fas fa-eye"></i> ВИДИ ЦЕНИ
                        </button>
                        
                        <div class="price-dropdown">
                            <div class="price-dropdown-header">
                                <h4 style="color: var(--primary-dark-green); margin-bottom: 20px; text-align: center;">Цени за <?php echo $name; ?></h4>
                            </div>
                            <div class="price-dropdown-content">
                                <?php
                                if (is_array($prices)) {
                                    foreach ($prices as $priceInfo) {
                                        $market = htmlspecialchars($priceInfo['market']);
                                        $price = htmlspecialchars($priceInfo['price']);
                                        $city = htmlspecialchars($priceInfo['city']);
                                        echo "<div class=\"price-item\">";
                                        echo "<span class=\"price-market\">$market</span>";
                                        echo "<span class=\"price-value\">$price <span class=\"price-city\">($city)</span></span>";
                                        echo "</div>";
                                    }
                                }
                                ?>
                            </div>
                            <button class="price-button" onclick="togglePriceDropdown(this)" style="margin-top: 15px; background: var(--gradient-orange);">
                                <i class="fas fa-times"></i> Затвори
                            </button>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Promo Message Section -->
    <section class="promo-message">
        <div class="container">
            <p class="promo-text">
                Промоцијата трае до потрошување на залихите!
            </p>
            <span class="promo-note">
                Најдобри цени гарантирани!
            </span>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="contact-info-section" id="contact">
        <div class="container">
            <div class="contact-card">
                <div class="contact-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h2 class="contact-title">Имате прашање за понудите?</h2>
                <p class="contact-subtitle">
                    Слободно не контактирајте за повеќе информации за Too Good To Go промоциите
                </p>
                
                <div class="phone-number">
                    <i class="fas fa-phone"></i> 071 350 288
                </div>
                
                <p style="margin-bottom: 1rem; color: var(--dark-gray);">
                    Достапни преку Viber и WhatsApp за побрз одговор
                </p>
                
                <div class="contact-methods">
                    <a href="viber://add?number=071350288" class="contact-method viber" aria-label="Viber">
                        <i class="fab fa-viber"></i>
                    </a>
                    <a href="https://wa.me/071350288" class="contact-method whatsapp" aria-label="WhatsApp" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:info@vivafresh.mk" class="contact-method email" aria-label="Email">
                        <i class="fas fa-envelope"></i>
                    </a>
                    <a href="tel:+071350288" class="contact-method phone" aria-label="Phone">
                        <i class="fas fa-phone"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <img src="Logo.png" alt="Viva Fresh Store MK" class="footer-logo">
                    <h3 class="footer-title">VIVA FRESH STORE MK</h3>
                    <p style="color: rgba(255, 255, 255, 0.8);">Вашата сигурна дестинација за свежи и квалитетни производи по најдобри цени.</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h3 class="footer-title">Брзи линкви</h3>
                    <ul class="footer-links">
                        <li><a href="index.html">Дома</a></li>
                        <li><a href="katalog.html">Каталог</a></li>
                        <li><a href="cenovnik.html">Ценовници</a></li>
                        <li><a href="toogoodtogo1.php">Too Good To Go</a></li>
                        <li><a href="vrabotuvanje.html">Вработување</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h3 class="footer-title">Контакт информации</h3>
                    <ul class="footer-contact">
                        <li><i class="fas fa-phone"></i> 071 350 288</li>
                        <li><i class="fas fa-envelope"></i> info@vivafresh.mk</li>
                        <li><i class="fas fa-map-marker-alt"></i> Скопје, Македонија</li>
                        <li><i class="fas fa-clock"></i> Пон-Пет: 8:00 - 20:00</li>
                        <li><i class="fas fa-clock"></i> Сабота: 9:00 - 18:00</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
  &copy; 2025 Viva Fresh Store MK. Сите права се задржани.
  <div class="dev-links">
    <span>Developers:</span>
    <div class="dev-names">
      <a href="https://www.linkedin.com/in/edon-lusjani-798b24272/" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-linkedin"></i> Edon Lusjani
      </a>
      <span class="separator">•</span>
      <a href="https://www.linkedin.com/in/bojana-taseva-0383b22b0/" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-linkedin"></i> Bojana Taseva
      </a>
    </div>
  </div>
</div>
    </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Close mobile menu if open
                    const navbarCollapse = document.getElementById('navbarNav');
                    if (navbarCollapse.classList.contains('show')) {
                        navbarCollapse.classList.remove('show');
                    }
                }
            });
        });
        
        // Toggle price dropdown
        function togglePriceDropdown(button) {
            const dropdown = button.parentElement.querySelector('.price-dropdown');
            const allDropdowns = document.querySelectorAll('.price-dropdown');
            
            // Close all other dropdowns
            allDropdowns.forEach(otherDropdown => {
                if (otherDropdown !== dropdown) {
                    otherDropdown.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('show');
            
            // Scroll to dropdown if it's opening
            if (dropdown.classList.contains('show')) {
                dropdown.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('price-button') && 
                !e.target.closest('.price-dropdown') &&
                !e.target.classList.contains('price-item') &&
                !e.target.classList.contains('price-market') &&
                !e.target.classList.contains('price-value') &&
                !e.target.classList.contains('price-city')) {
                document.querySelectorAll('.price-dropdown').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
        // Filter products by selected city
        document.getElementById('cityFilter').addEventListener('change', function () {
            const selectedCity = this.value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card-modern');

            productCards.forEach(card => {
                const cities = card.getAttribute('data-cities').toLowerCase();
                if (selectedCity === 'all' || cities.includes(selectedCity)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Add hover effect to product cards
        document.querySelectorAll('.product-card-modern').forEach(card => {
            card.addEventListener('mouseenter', () => {
                if (!card.querySelector('.price-dropdown').classList.contains('show')) {
                    card.style.transform = 'translateY(-10px)';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                if (!card.querySelector('.price-dropdown').classList.contains('show')) {
                    card.style.transform = 'translateY(0)';
                }
            });
        });
        
        // Add animation to discount badges
        document.querySelectorAll('.discount-badge').forEach(badge => {
            badge.addEventListener('mouseenter', () => {
                badge.style.transform = 'scale(1.1) rotate(3deg)';
            });
            
            badge.addEventListener('mouseleave', () => {
                badge.style.transform = 'scale(1) rotate(0deg)';
            });
        });
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Too Good To Go page loaded successfully!');
            
            // Add today's date animation
            const today = new Date();
            const formattedDate = today.toLocaleDateString('mk-MK', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            
            // Update any date elements if needed
            document.querySelectorAll('.date-today').forEach(el => {
                el.textContent = formattedDate;
            });
        });
    </script>
</body>
</html>