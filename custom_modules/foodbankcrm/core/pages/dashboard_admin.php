<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// ============================================
// SECURITY CHECK - Admin Only
// ============================================
if (!FoodbankPermissions::isAdmin($user)) {
    accessforbidden('You need administrator rights to access this page.');
}

llxHeader('', 'Foodbank CRM - Admin Dashboard');

// ============================================
// CSS STYLES
// ============================================
print '<style>
/* Modern Dashboard Styles */
.fb-dashboard { max-width: 1400px; margin: 0 auto; }
.fb-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
.fb-card { 
    background: #fff; 
    border-radius: 8px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px; 
    flex: 1; 
    min-width: 280px;
}
.fb-card-header { 
    font-size: 14px; 
    color: #666; 
    margin-bottom: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.fb-number { 
    font-size: 36px; 
    font-weight: 700; 
    margin: 8px 0;
}
.fb-sub { 
    color: #888; 
    font-size: 13px; 
}
.kpi-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 20px; 
    margin-bottom: 30px; 
}
.kpi-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.kpi-card.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.kpi-card.green { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.kpi-card.orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.kpi-card.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.kpi-card.red { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.kpi-card.teal { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

.kpi-title { 
    font-size: 13px; 
    opacity: 0.9; 
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.kpi-number { 
    font-size: 40px; 
    font-weight: 700; 
    margin: 6px 0;
}
.kpi-link { 
    color: white; 
    text-decoration: none; 
    font-size: 12px;
    opacity: 0.9;
    margin-top: 8px;
    display: inline-block;
}
.kpi-link:hover { opacity: 1; text-decoration: underline; }

.small-table { 
    width: 100%; 
    border-collapse: collapse; 
}
.small-table th, .small-table td { 
    padding: 10px 8px; 
    border-bottom: 1px solid #eee; 
    text-align: left; 
    font-size: 13px; 
}
.small-table th { 
    background: #f9f9f9; 
    font-weight: 600;
    color: #333;
}
.small-table tr:hover { 
    background: #f5f5f5; 
}
.badge { 
    padding: 4px 10px; 
    border-radius: 12px; 
    color: #fff; 
    font-size: 11px; 
    font-weight: 600;
    display: inline-block; 
}
.badge.green { background: #16a34a; }
.badge.orange { background: #f97316; }
.badge.blue { background: #2563eb; }
.badge.red { background: #ef4444; }
.badge.gray { background: #6b7280; }

.alert-box {
    padding: 12px 16px;
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 4px;
    margin-bottom: 10px;
    font-size: 13px;
}
.alert-box.danger {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}
.alert-box.success {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}
</style>';

// ============================================
// FETCH DATA
// ============================================
$prefix = MAIN_DB_PREFIX;

// Quick counts
$sql = "SELECT COUNT(*) AS c FROM ".$prefix."foodbank_beneficiaries";
$res = $db->query($sql);
$beneficiaries = $res ? $db->fetch_object($res)->c : 0;

$sql = "SELECT COUNT(*) AS c FROM ".$prefix."foodbank_vendors";
$res = $db->query($sql);
$vendors = $res ? $db->fetch_object($res)->c : 0;

$sql = "SELECT COUNT(*) AS c FROM ".$prefix."foodbank_donations";
$res = $db->query($sql);
$donations = $res ? $db->fetch_object($res)->c : 0;

$sql = "SELECT COUNT(*) AS c FROM ".$prefix."foodbank_distributions";
$res = $db->query($sql);
$distributions = $res ? $db->fetch_object($res)->c : 0;

$sql = "SELECT COUNT(*) AS c FROM ".$prefix."foodbank_packages";
$res = $db->query($sql);
$packages = $res ? $db->fetch_object($res)->c : 0;

$sql = "SELECT COUNT(*) AS c FROM ".$prefix."foodbank_warehouses";
$res = $db->query($sql);
$warehouses = $res ? $db->fetch_object($res)->c : 0;

// Stock summary by unit
$sql = "SELECT unit, 
        SUM(quantity) as total_quantity,
        SUM(quantity_allocated) as total_allocated,
        SUM(quantity - quantity_allocated) as available
        FROM ".$prefix."foodbank_donations
        WHERE status = 'Received'
        GROUP BY unit
        ORDER BY available DESC";
$res = $db->query($sql);
$stock_by_unit = array();
while ($obj = $db->fetch_object($res)) {
    $stock_by_unit[] = $obj;
}

// Low stock alerts (available < 10)
$sql = "SELECT ref, label, unit, (quantity - quantity_allocated) as available
        FROM ".$prefix."foodbank_donations
        WHERE status = 'Received' 
        AND (quantity - quantity_allocated) > 0 
        AND (quantity - quantity_allocated) < 10
        ORDER BY available ASC
        LIMIT 10";
$res = $db->query($sql);
$low_stock = array();
while ($obj = $db->fetch_object($res)) {
    $low_stock[] = $obj;
}

// Recent distributions
$sql = "SELECT d.rowid, d.ref, d.date_distribution, d.status,
        CONCAT(b.firstname, ' ', b.lastname) as beneficiary_name,
        (SELECT COUNT(*) FROM ".$prefix."foodbank_distribution_lines WHERE fk_distribution = d.rowid) as item_count
        FROM ".$prefix."foodbank_distributions d
        LEFT JOIN ".$prefix."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        ORDER BY d.date_distribution DESC
        LIMIT 8";
$res = $db->query($sql);
$recent_distributions = array();
while ($obj = $db->fetch_object($res)) {
    $recent_distributions[] = $obj;
}

// Recent donations
$sql = "SELECT d.rowid, d.ref, d.label, d.quantity, d.unit, d.date_donation, d.status,
        v.name as vendor_name
        FROM ".$prefix."foodbank_donations d
        LEFT JOIN ".$prefix."foodbank_vendors v ON d.fk_vendor = v.rowid
        ORDER BY d.date_donation DESC
        LIMIT 8";
$res = $db->query($sql);
$recent_donations = array();
while ($obj = $db->fetch_object($res)) {
    $recent_donations[] = $obj;
}

// Top vendors by donation count
$sql = "SELECT v.rowid, v.name, COUNT(d.rowid) as donation_count
        FROM ".$prefix."foodbank_vendors v
        LEFT JOIN ".$prefix."foodbank_donations d ON v.rowid = d.fk_vendor
        GROUP BY v.rowid
        ORDER BY donation_count DESC
        LIMIT 5";
$res = $db->query($sql);
$top_vendors = array();
while ($obj = $db->fetch_object($res)) {
    $top_vendors[] = $obj;
}

// ============================================
// RENDER DASHBOARD
// ============================================
print '<div class="fb-dashboard">';

print '<h1 style="margin-bottom: 24px;">🎯 Foodbank CRM Dashboard</h1>';

// KPI Cards
print '<div class="kpi-grid">';

print '<div class="kpi-card purple">';
print '<div class="kpi-title">Beneficiaries</div>';
print '<div class="kpi-number">'.$beneficiaries.'</div>';
print '<a href="/custom/foodbankcrm/core/pages/beneficiaries.php" class="kpi-link">View All →</a>';
print '</div>';

print '<div class="kpi-card red">';
print '<div class="kpi-title">Vendors</div>';
print '<div class="kpi-number">'.$vendors.'</div>';
print '<a href="/custom/foodbankcrm/core/pages/vendors.php" class="kpi-link">View All →</a>';
print '</div>';

print '<div class="kpi-card blue">';
print '<div class="kpi-title">Donations</div>';
print '<div class="kpi-number">'.$donations.'</div>';
print '<a href="/custom/foodbankcrm/core/pages/donations.php" class="kpi-link">View All →</a>';
print '</div>';

print '<div class="kpi-card green">';
print '<div class="kpi-title">Distributions</div>';
print '<div class="kpi-number">'.$distributions.'</div>';
print '<a href="/custom/foodbankcrm/core/pages/distributions.php" class="kpi-link">View All →</a>';
print '</div>';

print '<div class="kpi-card orange">';
print '<div class="kpi-title">Packages</div>';
print '<div class="kpi-number">'.$packages.'</div>';
print '<a href="/custom/foodbankcrm/core/pages/packages.php" class="kpi-link">View All →</a>';
print '</div>';

print '<div class="kpi-card teal">';
print '<div class="kpi-title">Warehouses</div>';
print '<div class="kpi-number">'.$warehouses.'</div>';
print '<a href="/custom/foodbankcrm/core/pages/warehouses.php" class="kpi-link">View All →</a>';
print '</div>';

print '</div>';

// Two Column Layout
print '<div class="fb-row">';

// LEFT COLUMN - Stock Summary
print '<div class="fb-card" style="flex: 1.2;">';
print '<div class="fb-card-header">📦 Stock Summary by Unit</div>';

if (count($stock_by_unit) > 0) {
    print '<table class="small-table">';
    print '<thead><tr><th>Unit</th><th>Total</th><th>Allocated</th><th>Available</th><th>Usage %</th></tr></thead>';
    print '<tbody>';
    
    foreach ($stock_by_unit as $stock) {
        $usage_pct = $stock->total_quantity > 0 ? round(($stock->total_allocated / $stock->total_quantity) * 100, 1) : 0;
        $color = $stock->available > 0 ? '#16a34a' : '#ef4444';
        
        print '<tr>';
        print '<td><strong>'.dol_escape_htmltag($stock->unit).'</strong></td>';
        print '<td>'.number_format($stock->total_quantity, 2).'</td>';
        print '<td><span style="color: #f97316;">'.number_format($stock->total_allocated, 2).'</span></td>';
        print '<td><strong style="color:'.$color.';">'.number_format($stock->available, 2).'</strong></td>';
        print '<td>'.$usage_pct.'%</td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
} else {
    print '<p style="color: #999;">No stock data available.</p>';
}

print '</div>';

// RIGHT COLUMN - Low Stock Alerts
print '<div class="fb-card" style="flex: 0.8;">';
print '<div class="fb-card-header">⚠️ Low Stock Alerts</div>';

if (count($low_stock) > 0) {
    foreach ($low_stock as $item) {
        print '<div class="alert-box danger">';
        print '<strong>'.dol_escape_htmltag($item->label).'</strong><br>';
        print '<span style="font-size: 12px;">'.dol_escape_htmltag($item->ref).' - Only '.number_format($item->available, 2).' '.$item->unit.' remaining</span>';
        print '</div>';
    }
} else {
    print '<div class="alert-box success">✅ All stock levels are healthy!</div>';
}

print '</div>';

print '</div>';

// Three Column Layout
print '<div class="fb-row">';

// Recent Distributions
print '<div class="fb-card">';
print '<div class="fb-card-header">📦 Recent Distributions</div>';

if (count($recent_distributions) > 0) {
    print '<table class="small-table">';
    print '<thead><tr><th>Ref</th><th>Beneficiary</th><th>Items</th><th>Date</th><th>Status</th></tr></thead>';
    print '<tbody>';
    
    foreach ($recent_distributions as $dist) {
        $status_class = 'gray';
        if ($dist->status == 'Delivered') $status_class = 'blue';
        elseif ($dist->status == 'Completed') $status_class = 'green';
        elseif ($dist->status == 'Prepared') $status_class = 'orange';
        
        print '<tr>';
        print '<td><a href="/custom/foodbankcrm/core/pages/view_distribution.php?id='.$dist->rowid.'"><strong>'.$dist->ref.'</strong></a></td>';
        print '<td>'.dol_escape_htmltag($dist->beneficiary_name).'</td>';
        print '<td>'.$dist->item_count.'</td>';
        print '<td>'.dol_print_date($db->jdate($dist->date_distribution), 'day').'</td>';
        print '<td><span class="badge '.$status_class.'">'.$dist->status.'</span></td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
} else {
    print '<p style="color: #999;">No distributions yet.</p>';
}

print '</div>';

print '</div>';

// Recent Donations
print '<div class="fb-row">';

print '<div class="fb-card">';
print '<div class="fb-card-header">🎁 Recent Donations</div>';

if (count($recent_donations) > 0) {
    print '<table class="small-table">';
    print '<thead><tr><th>Ref</th><th>Product</th><th>Quantity</th><th>Vendor</th><th>Date</th><th>Status</th></tr></thead>';
    print '<tbody>';
    
    foreach ($recent_donations as $don) {
        $status_class = 'gray';
        if ($don->status == 'Received') $status_class = 'green';
        elseif ($don->status == 'Pending') $status_class = 'orange';
        elseif ($don->status == 'Allocated') $status_class = 'blue';
        print '<tr>';
        print '<td><a href="/custom/foodbankcrm/core/pages/view_donation.php?id='.$don->rowid.'"><strong>'.$don->ref.'</strong></a></td>';
        print '<td>'.dol_escape_htmltag($don->label).'</td>';
        print '<td>'.number_format($don->quantity, 2).' '.$don->unit.'</td>';
        print '<td>'.dol_escape_htmltag($don->vendor_name).'</td>';
        print '<td>'.dol_print_date($db->jdate($don->date_donation), 'day').'</td>';
        print '<td><span class="badge '.$status_class.'">'.$don->status.'</span></td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
} else {
    print '<p style="color: #999;">No donations yet.</p>';
}

print '</div>';

print '</div>';

// Top Vendors
print '<div class="fb-row">';

print '<div class="fb-card">';
print '<div class="fb-card-header">🏆 Top Donors</div>';

if (count($top_vendors) > 0) {
    print '<table class="small-table">';
    print '<thead><tr><th>Vendor</th><th>Total Donations</th><th>Actions</th></tr></thead>';
    print '<tbody>';
    
    foreach ($top_vendors as $vendor) {
        print '<tr>';
        print '<td><strong>'.dol_escape_htmltag($vendor->name).'</strong></td>';
        print '<td>'.$vendor->donation_count.' donations</td>';
        print '<td><a href="/custom/foodbankcrm/core/pages/edit_vendor.php?id='.$vendor->rowid.'">View</a></td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
} else {
    print '<p style="color: #999;">No vendors yet.</p>';
}

print '</div>';

// Quick Actions
print '<div class="fb-card" style="max-width: 300px;">';
print '<div class="fb-card-header">⚡ Quick Actions</div>';

print '<div style="display: flex; flex-direction: column; gap: 10px;">';
print '<a href="/custom/foodbankcrm/core/pages/create_distribution.php" class="button" style="text-align: center;">📦 New Distribution</a>';
print '<a href="/custom/foodbankcrm/core/pages/create_donation.php" class="button" style="text-align: center;">🎁 New Donation</a>';
print '<a href="/custom/foodbankcrm/core/pages/create_beneficiary.php" class="button" style="text-align: center;">👤 New Beneficiary</a>';
print '<a href="/custom/foodbankcrm/core/pages/create_vendor.php" class="button" style="text-align: center;">🏢 New Vendor</a>';
print '<a href="/custom/foodbankcrm/core/pages/create_package.php" class="button" style="text-align: center;">🎁 New Package</a>';
print '</div>';

print '</div>';

print '</div>';

print '</div>'; // End fb-dashboard

llxFooter();
?>