<?php
session_start();
require_once 'config/db.php';

// --- AJAX / POST HANDLERS FOR AUTHENTICATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // 1. Sign Up Handler
    if ($_POST['action'] === 'signup') {
        try {
            global $pdo;
            $fullname = trim($_POST['fullname'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($fullname) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email address is already in use.']);
                exit;
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'customer')");
            $stmt->execute([$fullname, $email, $hashedPassword]);

            $_SESSION['user_id']  = $pdo->lastInsertId();
            $_SESSION['fullname'] = $fullname;

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    // 2. Login Handler
    if ($_POST['action'] === 'login') {
        try {
            global $pdo;
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in both email and password.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            }
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    // 3. Logout Handler
    if ($_POST['action'] === 'logout') {
        session_unset();
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }
}

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Food Packs - Sairene TephTeph Tapsilogan</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            background-color: #2b1d0c;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            position: relative;
            min-height: 100vh;
        }

        /* Fixed Fullscreen Wooden Texture Overlay */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.25), rgba(0, 0, 0, 0.35)), url('img/wood.jpg') no-repeat center center;
            background-size: cover;
            z-index: -1;
        }

        /* TOP-LEFT FIXED LOGIN BUTTON */
        .top-left-login-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1050;
            background-color: #212529;
            color: #ffc107;
            border: 2px solid #ffc107;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: bold;
            font-size: 0.88rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        .top-left-login-btn:hover {
            background-color: #ffc107;
            color: #212529;
            border-color: #212529;
            transform: translateY(-2px);
        }

        /* Large Outer Border Box */
        .page-outer-frame {
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            max-width: 1200px;
            margin: 40px auto 0 auto;
        }

        /* TOP FEATURED BANNER SPOT */
        .top-banner-spot {
            position: relative;
            border: 2px solid #ffc107;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(6px);
            padding: 25px 30px;
            margin-bottom: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: inset 0 0 15px rgba(255, 193, 7, 0.1);
        }

        .banner-logo-container {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 3px solid #ffc107;
            background-color: #ffffff;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .banner-logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .banner-text-content {
            flex-grow: 1;
            padding: 0 25px;
        }

        .banner-text-content h1 {
            color: #ffc107;
            font-weight: 900;
            font-size: 2.2rem;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.9), 0 0 12px rgba(0, 0, 0, 0.7);
        }

        .banner-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .banner-badge {
            background-color: #ffc107;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            text-transform: uppercase;
        }

        /* Sub-Header Navigation Line */
        .sub-header-line-1 {
            border: 1px solid #ffc107;
            background: rgba(0, 0, 0, 0.4);
            color: #ffffff;
            height: 36px;
            border-radius: 4px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sub-header-line-2 {
            border: 2px solid #ffc107;
            background: rgba(0, 0, 0, 0.4);
            
            border-radius: 12px;
            padding: 10px 20px;
            margin-bottom: 20px;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }

        .nav-links-custom {
            display: flex;
            justify-content: center;
            gap: 25px;
            list-style: none;
            padding: 0;
            margin: 0;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links-custom a {
            text-decoration: none;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: all 0.25s ease;
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid transparent;
        }

        .nav-links-custom a:hover, 
        .nav-links-custom a.active {
            color: #121212 !important;
            background-color: #ffc107;
            border-color: #d4a000;
            box-shadow: 0 4px 10px rgba(255, 193, 7, 0.4);
        }

        /* 3-DIVISION BENTO CONTAINER GRAPHIC */
        .bento-container {
            border: 2px solid #ffc107;
            border-radius: 16px;
            background-color: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            position: relative;
        }

        .bento-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 15px;
            height: 420px;
        }

        /* Compartment Styling */
        .compartment {
            border: 2px dashed #ffc107;
            border-radius: 12px;
            background-color: rgba(18, 18, 18, 0.7);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .compartment-carbs { grid-row: span 2; border-color: #ffc107; background-color: rgba(255, 193, 7, 0.08); }
        .compartment-main { border-color: #0d6efd; background-color: rgba(13, 110, 253, 0.08); }
        .compartment-side { border-color: #198754; background-color: rgba(25, 135, 84, 0.08); }

        .compartment:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .compartment-carbs:hover { border-style: solid; border-color: #ffc107; }
        .compartment-main:hover { border-style: solid; border-color: #3d8bfd; }
        .compartment-side:hover { border-style: solid; border-color: #25c974; }

        .compartment-badge {
            position: absolute;
            top: 10px;
            left: 12px;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 50px;
            text-transform: uppercase;
        }

        .badge-carbs { background-color: #ffc107; color: #121212; }
        .badge-main { background-color: #0d6efd; color: #ffffff; }
        .badge-side { background-color: #198754; color: #ffffff; }

        .item-preview {
            font-weight: 800;
            font-size: 1.1rem;
            color: #ffffff;
            margin-top: 10px;
        }

        /* Hover Dropdown Overlay */
        .hover-dropdown-panel {
            position: absolute;
            inset: 0;
            background-color: rgba(18, 18, 18, 0.96);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease-in-out;
            z-index: 10;
        }

        .compartment:hover .hover-dropdown-panel,
        .compartment:focus-within .hover-dropdown-panel {
            opacity: 1;
            visibility: visible;
        }

        .form-select-custom {
            background-color: #212529;
            color: #ffffff;
            border: 2px solid #ffc107;
            font-weight: 600;
            border-radius: 8px;
        }

        .form-select-custom:focus {
            background-color: #212529;
            color: #ffffff;
            border-color: #d4a000;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.4);
        }

        /* Summary Sidebar */
        .summary-card {
            border: 2px solid #ffc107;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }

        /* Bottom Full Width Line */
        .bottom-full-line {
            border: 1px solid #ffc107;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            height: 36px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 20px;
        }

        @media (max-width: 992px) {
            .top-banner-spot {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .banner-text-content { padding: 0; }
            .banner-badges { justify-content: center; }
            .bento-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
                height: auto;
            }
            .compartment-carbs { grid-row: span 1; min-height: 200px; }
            .compartment { min-height: 180px; }
        }
    </style>
</head>
<body>

    <!-- TOP-LEFT CORNER LOGIN / LOGOUT BUTTON -->
    <?php if ($is_logged_in): ?>
        <button type="button" class="top-left-login-btn" onclick="confirmLogout()">
            <i class="fa-solid fa-right-from-bracket me-2"></i>Logout (<?= htmlspecialchars($_SESSION['fullname']) ?>)
        </button>
    <?php else: ?>
        <button type="button" class="top-left-login-btn" onclick="openAuthModal('login')">
            <i class="fa-solid fa-user me-2"></i>Login / Register
        </button>
    <?php endif; ?>

    <!-- Large Outer Wireframe Frame -->
    <div class="page-outer-frame">

        <!-- 1. TOP FEATURED BANNER SPOT -->
        <section class="top-banner-spot">
            <div class="banner-logo-container">
                <img src="img/logo.png" alt="Sairene TephTeph Logo">
            </div>

            <div class="banner-text-content">
                <h1>Sairene TephTeph Tapsilogan</h1>
                <p class="text-white small mb-2 fw-semibold">Your Favorite Neighborhood Tapsilogan & Tambayan • Fresh Silog Meals Everyday</p>
                
                <div class="banner-badges">
                    <span class="banner-badge" style="color: #121212 !important;"><i class="fa-solid fa-clock me-1"></i> Open 24/7</span>
                    <span class="banner-badge" style="color: #121212 !important;"><i class="fa-solid fa-location-dot me-1"></i> MRB Condominium C5, Taguig</span>
                    <span class="banner-badge" style="color: #121212 !important;"><i class="fa-solid fa-wallet me-1"></i> GCash Ready</span>
                </div>
            </div>

            <div>
                <a href="order.php" class="btn btn-warning fw-bold px-4 py-2 text-dark shadow-sm rounded-pill text-nowrap">
                    <i class="fa-solid fa-cart-shopping me-1"></i> Order Now
                </a>
            </div>
        </section>

        <!-- 2. Two Thin Sub-Header Lines -->
        <div class="sub-header-line-1">
            <span class="text-white small fw-bold"><i class="fa-solid fa-star text-warning me-1"></i> A Taste of Comfort • Hot & Fresh Silog Meals <i class="fa-solid fa-star text-warning ms-1"></i></span>
        </div>

        <div class="sub-header-line-2">
            <ul class="nav-links-custom">
                <li><a href="Home_Page.php">HOME</a></li>
                <li><a href="menu.php">MENU</a></li>
                <li><a href="order.php">ORDER NOW</a></li>
                <li><a href="food_packs.php" class="active">FOOD PACKS</a></li>
                <li><a href="tambay_caps.php" target="_blank">TAMBAY CAPS</a></li>
            </ul>
        </div>

        <!-- 3. Title Banner -->
        <div class="text-center mb-4">
            <h2 class="fw-bold text-uppercase text-warning text-shadow mb-1" style="letter-spacing: 1px;">Build Your Custom 3-Division Food Pack</h2>
            <p class="text-white small mb-0">Hover over any compartment of the container to choose your rice, main dish, and side dish!</p>
        </div>

        <!-- 4. Interactive Bento Builder + Summary Grid -->
        <div class="row g-4">
            <!-- LEFT: Interactive 3-Division Food Pack Container -->
            <div class="col-lg-7">
                <div class="bento-container">
                    <div class="bento-grid">

                        <!-- 1. BIG SIDE (CARBS: RICE / PASTA) -->
                        <div class="compartment compartment-carbs" tabindex="0">
                            <span class="compartment-badge badge-carbs">1. Carbs Division</span>
                            <i class="fa-solid fa-bowl-rice fs-1 text-warning mb-2"></i>
                            <div class="item-preview" id="previewCarbs">Garlic Rice</div>
                            <small class="text-light opacity-75 mt-1"><i class="fa-solid fa-hand-pointer me-1"></i>Hover to edit</small>

                            <!-- Hover Dropdown Panel -->
                            <div class="hover-dropdown-panel">
                                <label class="fw-bold text-warning mb-1 small">Category:</label>
                                <select id="carbsCategory" class="form-select form-select-custom mb-2" onchange="updateCarbsOptions()">
                                    <option value="Rice" selected>Rice</option>
                                    <option value="Pasta">Pasta</option>
                                </select>

                                <label class="fw-bold text-warning mb-1 small">Select Carbs Item:</label>
                                <select id="carbsItem" class="form-select form-select-custom" onchange="updateSummary()">
                                    <!-- Populated dynamically via JS -->
                                </select>
                            </div>
                        </div>

                        <!-- 2. SMALL SIDE 1 (MAIN DISH) -->
                        <div class="compartment compartment-main" tabindex="0">
                            <span class="compartment-badge badge-main">2. Main Dish</span>
                            <i class="fa-solid fa-drumstick-bite fs-2 text-primary mb-2"></i>
                            <div class="item-preview" id="previewMain">Chicken Adobo</div>
                            <small class="text-light opacity-75 mt-1"><i class="fa-solid fa-hand-pointer me-1"></i>Hover to edit</small>

                            <!-- Hover Dropdown Panel -->
                            <div class="hover-dropdown-panel">
                                <label class="fw-bold text-warning mb-1 small">Select Main Dish:</label>
                                <select id="mainDishItem" class="form-select form-select-custom" onchange="updateSummary()">
                                    <option value="Chicken Adobo" selected>Chicken Adobo</option>
                                    <option value="Pork Adobo">Pork Adobo</option>
                                    <option value="Chicken Curry">Chicken Curry</option>
                                    <option value="Crispy Bagnet Portion">Crispy Bagnet Portion</option>
                                    <option value="Beef Tapa Portion">Beef Tapa Portion</option>
                                    <option value="Pork Sisig Portion">Pork Sisig Portion</option>
                                </select>
                            </div>
                        </div>

                        <!-- 3. SMALL SIDE 2 (SIDE DISH - OPTIONAL) -->
                        <div class="compartment compartment-side" tabindex="0">
                            <span class="compartment-badge badge-side">3. Side Dish (Optional)</span>
                            <i class="fa-solid fa-egg fs-2 text-success mb-2"></i>
                            <div class="item-preview" id="previewSide">Sunny Side Egg</div>
                            <small class="text-light opacity-75 mt-1"><i class="fa-solid fa-hand-pointer me-1"></i>Hover to edit</small>

                            <!-- Hover Dropdown Panel -->
                            <div class="hover-dropdown-panel">
                                <label class="fw-bold text-warning mb-1 small">Select Side Dish:</label>
                                <select id="sideDishItem" class="form-select form-select-custom" onchange="updateSummary()">
                                    <option value="None (No Side Dish)">-- None (Skip Side Dish) --</option>
                                    <option value="Sunny Side Egg" selected>Sunny Side Egg</option>
                                    <option value="Scrambled Egg">Scrambled Egg</option>
                                    <option value="Atchara (Pickled Papaya)">Atchara (Pickled Papaya)</option>
                                    <option value="Butter Corn & Peas">Butter Corn & Peas</option>
                                    <option value="Lumpiang Shanghai (2pcs)">Lumpiang Shanghai (2pcs)</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RIGHT: Custom Food Pack Order Summary -->
            <div class="col-lg-5">
                <div class="summary-card">
                    <div>
                        <h4 class="fw-bold text-warning mb-3 border-bottom border-warning pb-2">
                            <i class="fa-solid fa-box-open text-warning me-2"></i>Your Food Pack Summary
                        </h4>

                        <div class="bg-dark p-3 rounded border border-warning mb-3">
                            <div class="mb-2">
                                <span class="text-light small d-block opacity-75">Carbs Portion:</span>
                                <strong id="summaryCarbs" class="text-white">Rice - Garlic Rice</strong>
                            </div>
                            <div class="mb-2">
                                <span class="text-light small d-block opacity-75">Main Dish Portion:</span>
                                <strong id="summaryMain" class="text-warning">Chicken Adobo</strong>
                            </div>
                            <div>
                                <span class="text-light small d-block opacity-75">Side Dish Portion:</span>
                                <strong id="summarySide" class="text-success">Sunny Side Egg</strong>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center p-3 bg-dark rounded border border-warning">
                            <span class="fw-bold text-white">Meal Pack Total:</span>
                            <span class="fs-4 fw-bold text-warning font-monospace" id="summaryPrice">₱120.00</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="button" onclick="submitFoodPack()" class="btn btn-warning w-100 fw-bold py-2 text-dark shadow-sm">
                            <i class="fa-solid fa-cart-plus me-1"></i> Proceed to Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Bottom Full-Width Divider Line -->
        <div class="bottom-full-line">
            <span>&copy; <?php echo date('Y'); ?> Sairene TephTeph Tapsilogan • All Rights Reserved</span>
        </div>

    </div>

    <!-- LOGIN & SIGNUP POPUP MODAL -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-3 border-warning bg-dark text-white">
                <div class="modal-header border-warning">
                    <h5 class="modal-title fw-bold text-warning" id="authModalTitle">Customer Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Login Form -->
                    <form id="loginForm" onsubmit="handleAuthSubmit(event, 'login')">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">Email Address</label>
                            <input type="email" id="loginEmail" class="form-control bg-dark text-white border-secondary" placeholder="name@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">Password</label>
                            <input type="password" id="loginPassword" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold text-dark mb-3">Login</button>
                        <p class="small text-center mb-0">Don't have an account? <button type="button" onclick="toggleAuthForm('signup')" class="btn btn-link text-warning fw-bold p-0 text-decoration-none">Sign Up</button></p>
                    </form>

                    <!-- Signup Form -->
                    <form id="signupForm" class="d-none" onsubmit="handleAuthSubmit(event, 'signup')">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">Full Name</label>
                            <input type="text" id="signupName" class="form-control bg-dark text-white border-secondary" placeholder="Juan Dela Cruz" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">Email Address</label>
                            <input type="email" id="signupEmail" class="form-control bg-dark text-white border-secondary" placeholder="name@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">Password</label>
                            <input type="password" id="signupPassword" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold text-dark mb-3">Create Account</button>
                        <p class="small text-center mb-0">Already have an account? <button type="button" onclick="toggleAuthForm('login')" class="btn btn-link text-warning fw-bold p-0 text-decoration-none">Login</button></p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOTSTRAP 5 JS BUNDLE -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- FOOD PACK BUILDER & AUTHENTICATION SCRIPT HANDLERS -->
    <script>
        const carbsOptions = {
            "Rice": ["Fried Rice", "Garlic Rice", "Java Rice", "Plain Rice"],
            "Pasta": ["Spaghetti", "Carbonara", "Pesto Pasta"]
        };

        function updateCarbsOptions() {
            const category = document.getElementById("carbsCategory").value;
            const itemSelect = document.getElementById("carbsItem");
            itemSelect.innerHTML = "";

            carbsOptions[category].forEach(item => {
                const opt = document.createElement("option");
                opt.value = item;
                opt.textContent = item;
                if (item === "Garlic Rice" || item === "Spaghetti") opt.selected = true;
                itemSelect.appendChild(opt);
            });

            updateSummary();
        }

        function updateSummary() {
            const carbsCat = document.getElementById("carbsCategory").value;
            const carbsItem = document.getElementById("carbsItem").value || "Garlic Rice";
            const mainDish = document.getElementById("mainDishItem").value;
            const sideDish = document.getElementById("sideDishItem").value;

            document.getElementById("previewCarbs").innerText = carbsItem;
            document.getElementById("previewMain").innerText = mainDish;
            document.getElementById("previewSide").innerText = sideDish === "None (No Side Dish)" ? "No Side Dish" : sideDish;

            document.getElementById("summaryCarbs").innerText = `${carbsCat} - ${carbsItem}`;
            document.getElementById("summaryMain").innerText = mainDish;
            document.getElementById("summarySide").innerText = sideDish;

            let price = 120.00;
            if (sideDish === "None (No Side Dish)") {
                price = 105.00;
            }
            document.getElementById("summaryPrice").innerText = "₱" + price.toFixed(2);
        }

        function submitFoodPack() {
            const carbs = document.getElementById("summaryCarbs").innerText;
            const main = document.getElementById("summaryMain").innerText;
            const side = document.getElementById("summarySide").innerText;
            const price = document.getElementById("summaryPrice").innerText;

            const packDetails = `Custom Food Pack (${carbs} + ${main} + ${side})`;
            window.location.href = `order.php?custom_pack=${encodeURIComponent(packDetails)}&price=${encodeURIComponent(price)}`;
        }

        function openAuthModal(type) {
            toggleAuthForm(type);
            var modalEl = document.getElementById('authModal');
            if (typeof bootstrap !== 'undefined') {
                var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.show();
            }
        }

        function toggleAuthForm(type) {
            let loginForm = document.getElementById('loginForm');
            let signupForm = document.getElementById('signupForm');
            let modalTitle = document.getElementById('authModalTitle');

            if (type === 'signup') {
                loginForm.classList.add('d-none');
                signupForm.classList.remove('d-none');
                modalTitle.innerText = 'Create Account';
            } else {
                signupForm.classList.add('d-none');
                loginForm.classList.remove('d-none');
                modalTitle.innerText = 'Customer Login';
            }
        }

        function handleAuthSubmit(e, action) {
            e.preventDefault();
            let formData = new FormData();
            formData.append('action', action);

            if (action === 'login') {
                formData.append('email', document.getElementById('loginEmail').value);
                formData.append('password', document.getElementById('loginPassword').value);
            } else if (action === 'signup') {
                formData.append('fullname', document.getElementById('signupName').value);
                formData.append('email', document.getElementById('signupEmail').value);
                formData.append('password', document.getElementById('signupPassword').value);
            }

            fetch('food_packs.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'An error occurred during authentication.');
                }
            })
            .catch(err => console.error(err));
        }

        function confirmLogout() {
            if (confirm('Are you sure you want to log out?')) {
                let formData = new FormData();
                formData.append('action', 'logout');
                fetch('food_packs.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                });
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateCarbsOptions();
        });
    </script>
</body>
</html>
```[cite: 12, 13]