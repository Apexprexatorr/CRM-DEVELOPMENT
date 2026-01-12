<?php
/**
 * Vendor Dashboard - ENTREPRENEUR FOCUSED (Inventory/Supply Terminology)
 */
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

// Reset Redirect Flag
if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

// Security Check
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);
if (!$user_is_vendor) {
    accessforbidden('You do not have access to the vendor dashboard.');
}

// Fetch Vendor Data
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    llxHeader('', 'Vendor Dashboard');
    print '<div class="error">Vendor profile not found. Please contact administrator.</div>';
    llxFooter();
    exit;
}

$vendor = $db->fetch_object($res);
$vendor_id = $vendor->rowid;

llxHeader('', 'Vendor Dashboard');

// --- MODERN CSS & RESET ---
print '<style>
    /* 1. HIDE DOLIBARR CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header {
        display: none !important;
    }

    /* 2. RESET LAYOUT */
    html, body {
        background-color: #f8f9fa !important;
        margin: 0 !important;
        width: 100% !important;
        overflow-x: hidden !important;
    }

    #id-right, .id-right {
        margin: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        padding: 0 !important;
    }
    
    .fiche { max-width: 100% !important; margin: 0 !important; }

    /* 3. MAIN CONTAINER */
    .vendor-container { 
        width: 95%; 
        max-width: 1200px; 
        margin: 0 auto; 
        padding: 40px 20px; 
        font-family: "Segoe UI", sans-serif; 
    }

    /* 4. CARDS & WIDGETS */
    .dashboard-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
        transition: transform 0.2s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        border-color: #667eea;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    /* 5. STATS GRID */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    /* 6. BUTTONS */
    .btn-primary {
        background: #667eea; color: white; padding: 12px 25px; border-radius: 30px; 
        text-decoration: none; font-weight: bold; box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        display: inline-block;
    }
    
    .btn-logout {
        background: white; color: #dc3545; border: 1px solid #dc3545; 
        padding: 10px 20px; border-radius: 30px; text-decoration: none; 
        font-weight: bold; display: inline-flex; align-items: center; gap: 5px;
        transition: all 0.2s;
    }
    .btn-logout:hover { background: #dc3545; color: white; }

    /* 7. TABLES */
    .modern-table { width: 100%; border-collapse: collapse; }
    .modern-table th { text-align: left; padding: 15px; color: #888; border-bottom: 2px solid #eee; font-size: 13px; text-transform: uppercase; }
    .modern-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; color: #333; }
    .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; }
</style>';

print '<div class="vendor-container">';

// --- HEADER ---
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">';
print '<div>';
print '<h1 style="margin: 0; color: #2c3e50; font-size: 32px;">👋 Welcome, '.dol_escape_htmltag($vendor->name).'!</h1>';
print '<p style="color: #666; margin: 5px 0 0 0;">Vendor Portal • ID: '.dol_escape_htmltag($vendor->ref).'</p>';
print '</div>';

// Header Actions
print '<div style="display: flex; gap: 15px; align-items: center;">';
// CHANGED: Label is now "Add Inventory" (Entrepreneur style)
print '<a href="create_donation.php" class="btn-primary">📦 Add Inventory</a>';
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="btn-logout"><span>🚪</span> Logout</a>';
print '</div>';
print '</div>';

// --- STATS CALCULATION ---
$sql_stats = "SELECT 
    COUNT(DISTINCT d.rowid) as total_batches,
    COALESCE(SUM(d.quantity), 0) as total_quantity,
    COALESCE(SUM(CASE WHEN d.status = 'Pending' THEN 1 ELSE 0 END), 0) as pending_review,
    COUNT(DISTINCT d.product_name) as unique_products
    FROM ".MAIN_DB_PREFIX."foodbank_donations d
    WHERE d.fk_vendor = ".(int)$vendor_id;

$res_stats = $db->query($sql_stats);
$stats = ($res_stats) ? $db->fetch_object($res_stats) : new stdClass();

// --- STATS GRID ---
print '<div class="stats-grid">';

print '<div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Batches</div>';
print '<div style="font-size: 36px; font-weight: bold;">'.($stats->total_batches ?? 0).'</div>';
print '</div>';

print '<div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Pending Review</div>';
print '<div style="font-size: 36px; font-weight: bold;">'.($stats->pending_review ?? 0).'</div>';
print '</div>';

print '<div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Active Products</div>';
print '<div style="font-size: 36px; font-weight: bold;">'.($stats->unique_products ?? 0).'</div>';
print '</div>';

print '<div class="stat-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Units Supplied</div>';
print '<div style="font-size: 36px; font-weight: bold;">'.number_format($stats->total_quantity ?? 0).'</div>';
print '</div>';

print '</div>'; // End Stats Grid

// --- QUICK ACTIONS MENU ---
// CHANGED: Titles are now Business-Focused
print '<h2 style="margin: 40px 0 20px 0; color: #2c3e50;">⚡ Management Console</h2>';
print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">';

$actions = [
    ['icon' => '📦', 'title' => 'Supply History', 'desc' => 'Track stock logs & status', 'link' => 'my_donations.php'],
    ['icon' => '📊', 'title' => 'Performance', 'desc' => 'View supply metrics', 'link' => 'vendor_reports.php'],
    ['icon' => '🏢', 'title' => 'Business Profile', 'desc' => 'Update company details', 'link' => 'vendor_profile.php'],
    ['icon' => '💬', 'title' => 'Vendor Support', 'desc' => 'Contact administration', 'link' => 'vendor_support.php']
];

foreach ($actions as $act) {
    print '<a href="'.$act['link'].'" class="dashboard-card">';
    print '<div style="font-size: 40px; margin-bottom: 15px;">'.$act['icon'].'</div>';
    print '<div style="font-size: 18px; font-weight: bold; margin-bottom: 5px;">'.$act['title'].'</div>';
    print '<div style="color: #999; font-size: 14px;">'.$act['desc'].'</div>';
    print '</a>';
}
print '</div>';

// --- RECENT SUPPLY TABLE ---
print '<h2 style="margin: 40px 0 20px 0; color: #2c3e50;">📋 Recent Inventory Logs</h2>';

$sql_recent = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_donations 
               WHERE fk_vendor = ".(int)$vendor_id."
               ORDER BY date_donation DESC 
               LIMIT 5";
$res_recent = $db->query($sql_recent);

if ($res_recent && $db->num_rows($res_recent) > 0) {
    print '<div style="background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">';
    print '<table class="modern-table">';
    print '<thead><tr><th>Batch Ref</th><th>Date</th><th>Product</th><th style="text-align:center;">Qty</th><th style="text-align:center;">Status</th></tr></thead>';
    print '<tbody>';
    
    while ($row = $db->fetch_object($res_recent)) {
        $color = ($row->status == 'Received') ? '#28a745' : (($row->status == 'Pending') ? '#ffc107' : '#dc3545');
        
        print '<tr>';
        print '<td><strong>'.dol_escape_htmltag($row->ref).'</strong></td>';
        print '<td>'.dol_print_date($db->jdate($row->date_donation), 'day').'</td>';
        print '<td>'.dol_escape_htmltag($row->product_name).'</td>';
        print '<td style="text-align:center;">'.number_format($row->quantity).'</td>';
        print '<td style="text-align:center;"><span class="status-badge" style="background:'.$color.'">'.dol_escape_htmltag($row->status).'</span></td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
    print '</div>';
    print '<div style="text-align:right; margin-top:15px;"><a href="my_donations.php" style="color:#667eea; text-decoration:none; font-weight:bold;">View Full Stock Log →</a></div>';
} else {
    print '<div style="text-align:center; padding:50px; background:white; border-radius:12px; border:2px dashed #eee;">';
    print '<div style="font-size:40px; margin-bottom:10px;">📉</div>';
    print '<div style="color:#999;">No inventory logs found. Start supplying products today!</div>';
    print '</div>';
}

print '</div>'; // End Container

llxFooter();
?>