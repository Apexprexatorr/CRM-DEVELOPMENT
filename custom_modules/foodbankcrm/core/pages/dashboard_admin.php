<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// SECURITY CHECK
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('You need administrator rights to access this page.');
}

llxHeader('', 'Admin Dashboard');

// ============================================
// 1. CSS CLEANER (Hide Top Bar & Specific Menu Items)
// ============================================
print '<style>
    /* --- HIDE TOP BAR (Global) --- */
    #id-top { display: none !important; }
    
    /* Adjust Layout since Top Bar is gone */
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    /* --- SIDEBAR: HIDE VENDOR TASKS ONLY --- */
    .side-nav a[href*="create_donation.php"] {
        display: none !important;
    }

    /* --- HIDE STANDARD DOLIBARR MODULES --- */
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank,
    #mainmenutd_products, #mainmenutd_services
    {
        display: none !important;
    }

    /* --- DASHBOARD STYLES --- */
    .fb-dashboard { max-width: 1600px; margin: 0 auto; padding: 0 20px; }
    
    .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    
    /* KPI Grid */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .kpi-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; padding: 24px; border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.2s; position: relative; overflow: hidden;
    }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-card.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .kpi-card.red { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .kpi-card.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .kpi-card.green { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    .kpi-card.orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .kpi-card.teal { background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%); }

    .kpi-title { font-size: 13px; opacity: 0.9; margin-bottom: 5px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
    .kpi-number { font-size: 36px; font-weight: 700; margin: 5px 0; }
    .kpi-link { color: white; text-decoration: none; font-size: 12px; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; display: inline-block; margin-top: 10px; }
    .kpi-link:hover { background: rgba(255,255,255,0.3); }

    .section-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .fb-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 0; margin-bottom: 25px; border: 1px solid #eee; overflow: hidden; }
    .fb-card-header { padding: 15px 20px; border-bottom: 1px solid #eee; font-weight: bold; color: #555; background: #fcfcfc; }

    .small-table { width: 100%; border-collapse: collapse; }
    .small-table th { text-align: left; padding: 15px 20px; background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .small-table td { padding: 15px 20px; border-bottom: 1px solid #f5f5f5; font-size: 14px; color: #444; }
    .small-table tr:hover { background: #fafafa; }
</style>';

$prefix = MAIN_DB_PREFIX;

// ============================================
// 2. DATA FETCHING
// ============================================

$beneficiaries = $db->fetch_object($db->query("SELECT COUNT(*) AS c FROM ".$prefix."foodbank_beneficiaries"))->c;
$vendors = $db->fetch_object($db->query("SELECT COUNT(*) AS c FROM ".$prefix."foodbank_vendors"))->c;
$donations = $db->fetch_object($db->query("SELECT COUNT(*) AS c FROM ".$prefix."foodbank_donations"))->c;
$distributions = $db->fetch_object($db->query("SELECT COUNT(*) AS c FROM ".$prefix."foodbank_distributions"))->c;
$packages = $db->fetch_object($db->query("SELECT COUNT(*) AS c FROM ".$prefix."foodbank_packages"))->c;
$warehouses = $db->fetch_object($db->query("SELECT COUNT(*) AS c FROM ".$prefix."foodbank_warehouses"))->c;

// Pending Donations
$sql_pending = "SELECT d.rowid, d.ref, d.product_name, d.quantity, d.unit, v.name as vendor_name, d.date_donation
                FROM ".$prefix."foodbank_donations d
                LEFT JOIN ".$prefix."foodbank_vendors v ON d.fk_vendor = v.rowid
                WHERE d.status = 'Pending'
                ORDER BY d.date_donation DESC LIMIT 5";
$res_pending = $db->query($sql_pending);

// ============================================
// 3. DASHBOARD CONTENT
// ============================================
print '<div class="fb-dashboard">';

// HEADER
print '<div class="admin-header">';
print '<div>';
print '<h1 style="margin: 0; font-size: 24px;">👋 Admin Overview</h1>';
print '<p style="color: #888; margin: 5px 0 0 0;">Foodbank CRM Command Center</p>';
print '</div>';
print '<div style="display: flex; gap: 10px;">';
print '<a href="'.DOL_URL_ROOT.'/admin/index.php?mainmenu=home&leftmenu=setup" class="button" style="background: #f8f9fa; color: #333; border: 1px solid #ddd;">⚙️ Settings</a>';

// --- FIXED LOGOUT LINK ---
print '<a href="'.DOL_URL_ROOT.'/user/logout.php" class="button" style="background: #dc3545; color: white;">🛑 Logout</a>';
// ------------------------

print '</div>';
print '</div>';

// KPI CARDS
print '<div class="kpi-grid">';

print '<div class="kpi-card purple">';
print '<div class="kpi-title">Beneficiaries</div>';
print '<div class="kpi-number">'.$beneficiaries.'</div>';
print '<a href="beneficiaries.php" class="kpi-link">Manage List →</a>';
print '</div>';

print '<div class="kpi-card red">';
print '<div class="kpi-title">Vendors</div>';
print '<div class="kpi-number">'.$vendors.'</div>';
print '<a href="vendors.php" class="kpi-link">View Directory →</a>';
print '</div>';

print '<div class="kpi-card blue">';
print '<div class="kpi-title">Total Donations</div>';
print '<div class="kpi-number">'.$donations.'</div>';
print '<a href="donations.php" class="kpi-link">History →</a>';
print '</div>';

print '<div class="kpi-card green">';
print '<div class="kpi-title">Distributions</div>';
print '<div class="kpi-number">'.$distributions.'</div>';
print '<a href="distributions.php" class="kpi-link">Outbound →</a>';
print '</div>';

print '<div class="kpi-card orange">';
print '<div class="kpi-title">Packages</div>';
print '<div class="kpi-number">'.$packages.'</div>';
print '<a href="packages.php" class="kpi-link">Inventory →</a>';
print '</div>';

print '<div class="kpi-card teal">';
print '<div class="kpi-title">Warehouses</div>';
print '<div class="kpi-number">'.$warehouses.'</div>';
print '<a href="warehouses.php" class="kpi-link">Stock Levels →</a>';
print '</div>';

print '</div>'; // End KPI Grid

// SPLIT ROW
print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">';

// LEFT: Pending Approvals
print '<div>';
print '<div class="section-title">⚡ Pending Approvals <span style="font-size: 12px; background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 10px; font-weight: normal; margin-left: 10px;">Action Required</span></div>';
print '<div class="fb-card">';
if ($db->num_rows($res_pending) > 0) {
    print '<table class="small-table">';
    print '<thead><tr><th>Ref</th><th>Vendor</th><th>Product</th><th>Qty</th><th>Submitted</th><th>Action</th></tr></thead>';
    print '<tbody>';
    while ($obj = $db->fetch_object($res_pending)) {
        print '<tr>';
        print '<td><strong>'.$obj->ref.'</strong></td>';
        print '<td>'.dol_trunc($obj->vendor_name, 20).'</td>';
        print '<td>'.$obj->product_name.'</td>';
        print '<td>'.number_format($obj->quantity).' '.$obj->unit.'</td>';
        print '<td>'.dol_print_date($db->jdate($obj->date_donation), 'day').'</td>';
        print '<td><a href="view_donation.php?id='.$obj->rowid.'" class="button small" style="background: #2e7d32; color: white; padding: 6px 15px; text-decoration: none; border-radius: 4px; font-size: 12px;">Review</a></td>';
        print '</tr>';
    }
    print '</tbody></table>';
} else {
    print '<div style="text-align: center; padding: 40px; color: #999;">';
    print '<div style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;">✅</div>';
    print 'All caught up! No pending donations to review.';
    print '</div>';
}
print '</div>';
print '</div>';

// RIGHT: Admin Tools
print '<div>';
print '<div class="section-title">🛠️ Admin Tools</div>';
print '<div class="fb-card" style="padding: 20px;">';
print '<div style="display: flex; flex-direction: column; gap: 15px;">';
print '<a href="create_package.php" class="butAction" style="text-align: center; padding: 15px;">🎁 Create New Package</a>';
print '<a href="create_vendor.php" class="button" style="text-align: center; background: #f8f9fa; color: #333; border: 1px solid #ddd;">🏢 Register Vendor</a>';
print '<a href="create_beneficiary.php" class="button" style="text-align: center; background: #f8f9fa; color: #333; border: 1px solid #ddd;">👤 Register Beneficiary</a>';
print '<a href="warehouses.php" class="button" style="text-align: center; background: #f8f9fa; color: #333; border: 1px solid #ddd;">🏭 Manage Warehouses</a>';
print '</div>';
print '</div>';
print '</div>';

print '</div>'; // End Split Row

print '</div>'; // End Dashboard

llxFooter();
?>