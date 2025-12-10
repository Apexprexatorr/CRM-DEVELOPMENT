<?php
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

$langs->load("admin");

// 1. CHECK PERMISSIONS (Allow Admin OR Vendor)
$user_is_admin = FoodbankPermissions::isAdmin($user);
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$user_is_vendor && !$user_is_admin) {
    accessforbidden('You do not have access to this page.');
}

// 2. IDENTIFY THE VENDOR (If not admin)
$vendor_id = 0;
if ($user_is_vendor && !$user_is_admin) {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $obj = $db->fetch_object($res)) {
        $vendor_id = $obj->rowid;
    } else {
        accessforbidden('Vendor profile not found linked to your user account.');
    }
}

llxHeader('', 'My Donations');

// Portal Mode CSS (Keeps your clean look)
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1400px; margin: 0 auto;">';

// Header
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
if ($user_is_admin) {
    print '<h1 style="margin: 0;">📦 All Donations (Admin View)</h1>';
    print '<p style="color: #666; margin: 5px 0 0 0;">Managing donations from all vendors</p>';
} else {
    print '<h1 style="margin: 0;">📦 My Donations</h1>';
    print '<p style="color: #666; margin: 5px 0 0 0;">Track all your submitted donations</p>';
}
print '</div>';
print '<div style="display: flex; gap: 10px;">';
if (!$user_is_admin) {
    print '<a href="create_donation.php" class="butAction">🎁 Submit New Donation</a>';
    print '<a href="dashboard_vendor.php" class="butAction">← Back to Dashboard</a>';
} else {
    print '<a href="donations.php" class="butAction">← Back to Main Admin List</a>';
}
print '</div>';
print '</div>';

// Filter
$filter_status = GETPOST('status', 'alpha');

print '<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">';
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" style="display: flex; align-items: center; gap: 15px;">';
print '<label style="font-weight: bold;">Filter by Status:</label>';
print '<select name="status" class="flat" onchange="this.form.submit()" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px;">';
print '<option value="">All Donations</option>';
print '<option value="Pending" '.($filter_status == 'Pending' ? 'selected' : '').'>⏳ Pending</option>';
print '<option value="Received" '.($filter_status == 'Received' ? 'selected' : '').'>✓ Received</option>';
print '<option value="Rejected" '.($filter_status == 'Rejected' ? 'selected' : '').'>✗ Rejected</option>';
print '</select>';
print '</form>';
print '</div>';

// 3. BUILD SQL QUERY (Dynamic based on Role)
$sql = "SELECT d.*, w.label as warehouse_name, v.name as vendor_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
        WHERE 1=1"; // Start with a dummy condition

// If Vendor (and not Admin), RESTRICT DATA
if ($user_is_vendor && !$user_is_admin) {
    $sql .= " AND d.fk_vendor = ".(int)$vendor_id;
}

if ($filter_status) {
    $sql .= " AND d.status = '".$db->escape($filter_status)."'";
}

$sql .= " ORDER BY d.date_donation DESC";

$res = $db->query($sql);

// Summary Stats Query (Dynamic)
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(quantity) as total_qty,
    SUM(CASE WHEN status = 'Received' THEN 1 ELSE 0 END) as received_count,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(quantity * unit_price) as total_value
    FROM ".MAIN_DB_PREFIX."foodbank_donations d
    WHERE 1=1";

if ($user_is_vendor && !$user_is_admin) {
    $sql_stats .= " AND d.fk_vendor = ".(int)$vendor_id;
}

$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

// Stats Cards
print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">';
print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Donations</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->total ?? 0).'</div>';
print '</div>';
print '<div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Received</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->received_count ?? 0).'</div>';
print '</div>';
print '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Pending</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->pending_count ?? 0).'</div>';
print '</div>';
print '<div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Value</div>';
print '<div style="font-size: 32px; font-weight: bold;">₦'.number_format($stats->total_value ?? 0, 0).'</div>';
print '</div>';
print '</div>';

// Donations Table
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<table style="width: 100%; border-collapse: collapse;">';
print '<thead style="background: #f8f9fa;">';
print '<tr>';
print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Reference</th>';

// IF ADMIN: Show Vendor Column
if ($user_is_admin) {
    print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Vendor</th>';
}

print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Product</th>';
print '<th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Quantity</th>';
print '<th style="padding: 15px; text-align: right; border-bottom: 2px solid #dee2e6;">Total Value</th>';
print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Warehouse</th>';
print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Date</th>';
print '<th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Status</th>';
print '<th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Actions</th>';
print '</tr>';
print '</thead>';
print '<tbody>';

$status_colors = array(
    'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳'),
    'Received' => array('bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '✓'),
    'Rejected' => array('bg' => '#ffebee', 'color' => '#d32f2f', 'icon' => '✗')
);

if ($res && $db->num_rows($res) > 0) {
    while ($donation = $db->fetch_object($res)) {
        $colors = $status_colors[$donation->status] ?? array('bg' => '#f5f5f5', 'color' => '#666', 'icon' => '?');
        $total_value = $donation->quantity * $donation->unit_price;
        
        print '<tr style="border-bottom: 1px solid #f0f0f0;">';
        print '<td style="padding: 15px;"><strong>'.dol_escape_htmltag($donation->ref).'</strong></td>';
        
        // IF ADMIN: Show Vendor Name
        if ($user_is_admin) {
            print '<td style="padding: 15px; color: #666;">'.dol_escape_htmltag($donation->vendor_name).'</td>';
        }
        
        print '<td style="padding: 15px;">'.dol_escape_htmltag($donation->product_name).'</td>';
        print '<td style="padding: 15px; text-align: center;"><strong>'.number_format($donation->quantity, 0).' '.$donation->unit.'</strong></td>';
        print '<td style="padding: 15px; text-align: right;"><strong>₦'.number_format($total_value, 2).'</strong></td>';
        print '<td style="padding: 15px;">'.dol_escape_htmltag($donation->warehouse_name ?: 'Not assigned').'</td>';
        print '<td style="padding: 15px;">'.dol_print_date($db->jdate($donation->date_donation), 'day').'</td>';
        print '<td style="padding: 15px; text-align: center;">';
        print '<span style="display:inline-block; padding:6px 14px; border-radius:20px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold; font-size:12px;">';
        print $colors['icon'].' '.dol_escape_htmltag($donation->status);
        print '</span>';
        print '</td>';
        print '<td style="padding: 15px; text-align: center;">';
        print '<a href="view_donation.php?id='.$donation->rowid.'" class="butAction" style="padding: 6px 12px; font-size: 13px;">View</a>';
        print '</td>';
        print '</tr>';
    }
} else {
    print '<tr><td colspan="9" style="padding: 50px; text-align: center; color: #666;">No donations found.</td></tr>';
}

print '</tbody>';
print '</table>';
print '</div>';
print '</div>';

llxFooter();
?>