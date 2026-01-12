<?php
/**
 * FOODBANK CRM - SMART GATEWAY
 * Handles Public Access (Welcome Page) and Auto-Redirects after Login
 */

// 1. ALLOW PUBLIC ACCESS
if (!defined("NOLOGIN")) define("NOLOGIN", 1);
if (!defined("NOCSRFCHECK")) define("NOCSRFCHECK", 1);
if (!defined("NOIPCHECK")) define("NOIPCHECK", 1);

// 2. Load Dolibarr Environment
require_once dirname(__DIR__, 2) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/foodbankcrm/class/permissions.class.php';

// 3. LOGGED IN USER LOGIC (The "Traffic Controller")
// This runs automatically if the user is logged in (or returns from login)
if (!empty($user->id)) {
    
    // A. ADMIN REDIRECT (Fixing the issue)
    if ($user->admin || FoodbankPermissions::isAdmin($user)) {
        header('Location: core/pages/dashboard_admin.php');
        exit;
    } 
    
    // B. VENDOR REDIRECT
    elseif (FoodbankPermissions::isVendor($user, $db)) {
        header('Location: core/pages/dashboard_vendor.php');
        exit;
    } 
    
    // C. BENEFICIARY REDIRECT
    elseif (FoodbankPermissions::isBeneficiary($user, $db)) {
        header('Location: core/pages/dashboard_beneficiary.php');
        exit;
    }
    
    // Fallback if no role matches
    header('Location: '.DOL_URL_ROOT.'/index.php');
    exit;
}

// 4. PUBLIC VISITOR LOGIC (Welcome Screen)
// If code reaches here, the user is NOT logged in.

// --- THE FIX: Create the "Return Ticket" URL ---
// This forces Dolibarr to send the user BACK here after logging in
$login_url = DOL_URL_ROOT . '/index.php?backtopage=' . urlencode(DOL_URL_ROOT . '/custom/foodbankcrm/index.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodbank CRM | Welcome</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            height: 100vh;
            display: flex; align-items: center; justify-content: center;
            color: white;
        }
        .container {
            text-align: center; background: white; color: #333;
            padding: 50px; border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 450px; width: 90%;
        }
        h1 { margin-top: 0; color: #1e293b; font-size: 28px; margin-bottom: 10px; }
        p { color: #64748b; margin-bottom: 30px; line-height: 1.5; }
        
        .btn {
            display: block; width: 100%; padding: 15px; margin-bottom: 15px;
            border-radius: 8px; text-decoration: none; font-weight: bold;
            font-size: 16px; transition: transform 0.2s, box-shadow 0.2s;
            box-sizing: border-box; border: none; cursor: pointer;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        .btn-login { background: #2c3e50; color: white; }
        .btn-register { background: #667eea; color: white; }
        .btn-vendor { background: #fff; color: #2c3e50; border: 2px solid #e2e8f0; }
        .btn-vendor:hover { background: #f8fafc; border-color: #cbd5e1; }

        .divider { 
            margin: 25px 0; color: #94a3b8; font-size: 12px; 
            text-transform: uppercase; letter-spacing: 1px;
            display: flex; align-items: center; 
        }
        .divider::before, .divider::after { content: ""; flex: 1; border-bottom: 1px solid #e2e8f0; }
        .divider::before { margin-right: 15px; }
        .divider::after { margin-left: 15px; }
        
        .icon-logo { font-size: 50px; margin-bottom: 15px; display: block; }
    </style>
</head>
<body>

    <div class="container">
        <span class="icon-logo">🍲</span>
        <h1>Foodbank CRM</h1>
        <p>Connecting communities through efficient food distribution and supply management.</p>

        <a href="<?php echo $login_url; ?>" class="btn btn-login">🔐 Login to Account</a>
        
        <div class="divider">Get Started</div>

        <a href="core/pages/register.php" class="btn btn-register">👤 Register as User</a>
        
        <a href="core/pages/register_vendor.php" class="btn btn-vendor">🏢 Become a Vendor Partner</a>
    </div>

</body>
</html>