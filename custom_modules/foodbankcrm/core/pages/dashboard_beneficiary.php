<?php
/**
 * Beneficiary/Subscriber Dashboard - FULL SCREEN FLUID LAYOUT
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;
$langs->load("admin");

// Security Check
if (!FoodbankPermissions::isBeneficiary($user, $db)) {
    accessforbidden('Access Denied: You are not a registered beneficiary.');
}

// Fetch Subscriber Profile
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
if (!$res || $db->num_rows($res) == 0) {
    accessforbidden('Profile not found. Please contact support.');
}
$subscriber = $db->fetch_object($res);

llxHeader('', 'My Dashboard');

// --- AGGRESSIVE CSS RESET & FLUID LAYOUT ---
print '<style>
    /* 1. HIDE ALL DOLIBARR CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    /* 2. RESET PARENT CONTAINERS */
    html, body {
        background-color: #f8f9fa !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
        overflow-x: hidden !important;
    }

    #id-container {
        display: block !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* 3. FORCE CONTENT TO FULL VIEWPORT WIDTH */
    #id-right, .id-right {
        margin: 0 !important;
        padding: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        flex: none !important;
        display: block !important;
    }

    .fiche { width: 100% !important; max-width: 100% !important; margin: 0 !important; }

    /* 4. CUSTOM FLUID CONTAINER */
    .ben-container { 
        width: 98%;        /* Occupy 98% of the screen */
        max-width: none;   /* Remove width limit */
        margin: 0 auto; 
        padding: 30px 0; 
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    
    /* DASHBOARD CARDS */
    .status-card { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        color: white; 
        padding: 35px; 
        border-radius: 12px; 
        margin-bottom: 30px; 
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); 
    }
    
    .stat-box { 
        background: white; 
        padding: 30px; 
        border-radius: 12px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        text-align: center; 
        border-bottom: 5px solid #eee; 
        transition: transform 0.2s; 
        height: 100%; /* Even height */
        box-sizing: border-box;
    }
    .stat-box:hover { transform: translateY(-3px); }
    
    .menu-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
        gap: 25px; 
        margin-top: 40px; 
    }
    
    .menu-card { 
        background: white; 
        padding: 40px 30px; 
        border-radius: 12px; 
        text-align: center; 
        text-decoration: none; 
        color: #333; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        transition: transform 0.2s; 
        border: 1px solid #f0f0f0; 
        display: block;
    }
    .menu-card:hover { 
        transform: translateY(-5px); 
        border-color: #667eea; 
        box-shadow: 0 8px 20px rgba(0,0,0,0.1); 
    }

    /* LOGOUT BUTTON STYLE */
    .btn-logout {
        background: white; 
        color: #dc3545; 
        border: 1px solid #dc3545; 
        padding: 12px 25px; 
        border-radius: 30px; 
        text-decoration: none; 
        font-weight: bold; 
        font-size: 15px; 
        display: inline-flex; 
        align-items: center; 
        gap: 5px; 
        transition: all 0.2s;
    }
    .btn-logout:hover { 
        background: #dc3545; 
        color: white; 
    }
</style>';

print '<div class="ben-container">';

// 1. Welcome Header (Updated with Logout)
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 0 10px;">';
print '<div><h1 style="margin: 0; color: #2c3e50; font-size: 32px;">👋 Welcome, '.dol_escape_htmltag($subscriber->firstname).'!</h1><p style="color: #7f8c8d; margin: 5px 0 0 0; font-size: 16px;">Subscriber ID: '.dol_escape_htmltag($subscriber->ref).'</p></div>';

// Right Side Actions Wrapper
print '<div style="display: flex; gap: 15px; align-items: center;">';
print '<a href="product_catalog.php" class="button" style="background:#667eea; color:white; padding:12px 30px; border-radius:30px; box-shadow:0 4px 12px rgba(102,126,234,0.3); font-weight:bold; text-decoration:none; font-size: 15px;">🛒 Order Food</a>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="btn-logout"><span>🚪</span> Logout</a>';
print '</div>'; // End Right Side

print '</div>';

// 2. Subscription Card
print '<div class="status-card">';
print '<div style="display: flex; justify-content: space-between; align-items: center;">';
print '<div><div style="opacity:0.8; font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-bottom: 5px;">Current Plan</div><div style="font-size:32px; font-weight:bold;">'.($subscriber->subscription_type ?: 'Guest').'</div></div>';
print '<div><div style="opacity:0.8; font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-bottom: 5px;">Status</div><div style="font-size:20px; font-weight:bold; background:rgba(255,255,255,0.2); padding:5px 20px; border-radius:30px;">'.($subscriber->subscription_status ?: 'Active').'</div></div>';
print '<a href="renew_subscription.php" style="color:white; text-decoration:none; background:rgba(0,0,0,0.2); padding:12px 25px; border-radius:30px; font-weight:bold;">Manage Plan →</a>';
print '</div></div>';

// 3. Stats Calculation
$stats = $db->fetch_object($db->query("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status='Delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status IN ('Prepared','In Transit') THEN 1 ELSE 0 END) as active,
    SUM(total_amount) as spent
    FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_beneficiary=".$subscriber->rowid));

// 4. Stats Grid
print '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px;">';
print '<div class="stat-box" style="border-bottom-color: #667eea;"><div style="font-size:13px; color:#888; margin-bottom:10px; font-weight:bold;">TOTAL ORDERS</div><div style="font-size:36px; font-weight:800; color:#333;">'.($stats->total ?: 0).'</div></div>';
print '<div class="stat-box" style="border-bottom-color: #f1c40f;"><div style="font-size:13px; color:#888; margin-bottom:10px; font-weight:bold;">ACTIVE</div><div style="font-size:36px; font-weight:800; color:#333;">'.($stats->active ?: 0).'</div></div>';
print '<div class="stat-box" style="border-bottom-color: #2ecc71;"><div style="font-size:13px; color:#888; margin-bottom:10px; font-weight:bold;">DELIVERED</div><div style="font-size:36px; font-weight:800; color:#333;">'.($stats->delivered ?: 0).'</div></div>';
print '<div class="stat-box" style="border-bottom-color: #9b59b6;"><div style="font-size:13px; color:#888; margin-bottom:10px; font-weight:bold;">TOTAL SPENT</div><div style="font-size:36px; font-weight:800; color:#333;">₦'.number_format($stats->spent ?: 0).'</div></div>';
print '</div>';

// 5. Quick Links Menu
print '<div class="menu-grid">';
print '<a href="product_catalog.php" class="menu-card"><div style="font-size:48px; margin-bottom:15px;">🛍️</div><div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">Browse Packages</div><div style="color:#888; font-size: 14px;">View available food boxes</div></a>';
print '<a href="view_cart.php" class="menu-card"><div style="font-size:48px; margin-bottom:15px;">🛒</div><div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">My Cart</div><div style="color:#888; font-size: 14px;">Manage your selection</div></a>';
print '<a href="my_orders.php" class="menu-card"><div style="font-size:48px; margin-bottom:15px;">📦</div><div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">My Orders</div><div style="color:#888; font-size: 14px;">Track history</div></a>';
print '<a href="my_profile.php" class="menu-card"><div style="font-size:48px; margin-bottom:15px;">👤</div><div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">My Profile</div><div style="color:#888; font-size: 14px;">Update info</div></a>';
print '</div>';

print '</div>';
llxFooter();
?>