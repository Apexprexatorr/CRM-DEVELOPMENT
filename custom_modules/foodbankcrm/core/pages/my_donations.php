<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a vendor
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$user_is_vendor) {
    accessforbidden('You do not have access.');
}

// Get vendor information
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
$vendor = $db->fetch_object($res);
$vendor_id = $vendor->rowid;

llxHeader('', 'My Donations');

print '<div><a href="dashboard_vendor.php">← Back to Dashboard</a></div><br>';

print '<h1>📦 My Donations</h1>';

// Filter by status
$filter_status = GETPOST('status', 'alpha');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" style="margin-bottom: 20px;">';
print '<label>Filter by Status: </label>';
print '<select name="status" class="flat" onchange="this.form.submit()">';
print '<option value="">All Donations</option>';
print '<option value="Pending" '.($filter_status == 'Pending' ? 'selected' : '').'>Pending</option>';
print '<option value="Received" '.($filter_status == 'Received' ? 'selected' : '').'>Received</option>';
print '<option value="Rejected" '.($filter_status == 'Rejected' ? 'selected' : '').'>Rejected</option>';
print '</select>';
print '</form>';

// Get donations
$sql = "SELECT d.*, w.name as warehouse_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        WHERE d.fk_vendor = ".(int)$vendor_id;

if ($filter_status) {
    $sql .= " AND d.status = '".$db->escape($filter_status)."'";
}

$sql .= " ORDER BY d.date_creation DESC";

$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    print '<div style="text-align: center; padding: 60px; background: #f9f9f9; border-radius: 8px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">📦</div>';
    print '<h2>No Donations Found</h2>';
    print '<p style="color: #666;">'.($filter_status ? 'No donations with status: '.$filter_status : 'You haven\'t submitted any donations yet.').'</p>';
    print '<br><a class="butAction" href="create_donation.php">+ Submit Donation</a>';
    print '</div>';
    llxFooter();
    exit;
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Ref</th>';
print '<th>Product</th>';
print '<th>Category</th>';
print '<th class="center">Quantity</th>';
print '<th class="center">Unit Price</th>';
print '<th>Warehouse</th>';
print '<th>Date Submitted</th>';
print '<th>Status</th>';
print '<th class="center">Actions</th>';
print '</tr>';

while ($donation = $db->fetch_object($res)) {
    // Status colors
    $status_colors = array(
        'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳'),
        'Received' => array('bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '✓'),
        'Rejected' => array('bg' => '#ffebee', 'color' => '#d32f2f', 'icon' => '✗')
    );
    $colors = $status_colors[$donation->status] ?? array('bg' => '#f5f5f5', 'color' => '#666', 'icon' => '?');
    
    print '<tr class="oddeven">';
    print '<td><strong>'.dol_escape_htmltag($donation->ref).'</strong></td>';
    print '<td>'.dol_escape_htmltag($donation->product_name).'</td>';
    print '<td>'.dol_escape_htmltag($donation->category).'</td>';
    print '<td class="center"><strong>'.$donation->quantity.' '.$donation->unit.'</strong></td>';
    print '<td class="center">';
    if ($donation->unit_price > 0) {
        print '₦'.number_format($donation->unit_price, 2);
    } else {
        print '<span style="color: #999;">N/A</span>';
    }
    print '</td>';
    print '<td>'.dol_escape_htmltag($donation->warehouse_name ?: 'Not assigned').'</td>';
    print '<td>'.dol_print_date($db->jdate($donation->date_creation), 'day').'</td>';
    print '<td>';
    print '<span style="display:inline-block; padding:4px 10px; border-radius:4px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold; font-size:11px;">';
    print $colors['icon'].' '.dol_escape_htmltag($donation->status);
    print '</span>';
    print '</td>';
    print '<td class="center">';
    print '<a href="view_donation.php?id='.$donation->rowid.'">View</a>';
    if ($donation->status == 'Pending') {
        print ' | <a href="edit_donation.php?id='.$donation->rowid.'">Edit</a>';
    }
    print '</td>';
    print '</tr>';
}

print '</table>';

// Summary stats
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Received' THEN quantity ELSE 0 END) as received_qty,
    SUM(CASE WHEN status = 'Pending' THEN quantity ELSE 0 END) as pending_qty
    FROM ".MAIN_DB_PREFIX."foodbank_donations
    WHERE fk_vendor = ".(int)$vendor_id;

$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

print '<div style="margin-top: 30px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">';

print '<div style="background: #e3f2fd; padding: 15px; border-radius: 5px; text-align: center;">';
print '<div style="font-size: 32px; font-weight: bold; color: #1976d2;">'.($stats->total ?? 0).'</div>';
print '<div style="color: #666; font-size: 13px;">Total Donations</div>';
print '</div>';

print '<div style="background: #e8f5e9; padding: 15px; border-radius: 5px; text-align: center;">';
print '<div style="font-size: 32px; font-weight: bold; color: #2e7d32;">'.number_format($stats->received_qty ?? 0, 0).'</div>';
print '<div style="color: #666; font-size: 13px;">Quantity Received</div>';
print '</div>';

print '<div style="background: #fff3e0; padding: 15px; border-radius: 5px; text-align: center;">';
print '<div style="font-size: 32px; font-weight: bold; color: #f57c00;">'.number_format($stats->pending_qty ?? 0, 0).'</div>';
print '<div style="color: #666; font-size: 13px;">Quantity Pending</div>';
print '</div>';

print '</div>';

llxFooter();
?>