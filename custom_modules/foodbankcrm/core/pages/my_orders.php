<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a beneficiary/subscriber
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access.');
}

// Get subscriber information
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
$subscriber = $db->fetch_object($res);
$subscriber_id = $subscriber->rowid;

llxHeader('', 'My Orders');

print '<div><a href="dashboard_beneficiary.php">← Back to Dashboard</a></div><br>';

print '<h1>📦 My Orders</h1>';

// Filter by status
$filter_status = GETPOST('status', 'alpha');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" style="margin-bottom: 20px;">';
print '<label>Filter by Status: </label>';
print '<select name="status" class="flat" onchange="this.form.submit()">';
print '<option value="">All Orders</option>';
print '<option value="Pending" '.($filter_status == 'Pending' ? 'selected' : '').'>Pending</option>';
print '<option value="Bundled" '.($filter_status == 'Bundled' ? 'selected' : '').'>Bundled</option>';
print '<option value="Picked Up" '.($filter_status == 'Picked Up' ? 'selected' : '').'>Picked Up</option>';
print '<option value="In Transit" '.($filter_status == 'In Transit' ? 'selected' : '').'>In Transit</option>';
print '<option value="Delivered" '.($filter_status == 'Delivered' ? 'selected' : '').'>Delivered</option>';
print '</select>';
print '</form>';

// Get orders
$sql = "SELECT d.*, 
        COUNT(dl.rowid) as item_count
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_distribution_lines dl ON d.rowid = dl.fk_distribution
        WHERE d.fk_beneficiary = ".(int)$subscriber_id;

if ($filter_status) {
    $sql .= " AND d.status = '".$db->escape($filter_status)."'";
}

$sql .= " GROUP BY d.rowid ORDER BY d.date_creation DESC";

$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    print '<div style="text-align: center; padding: 60px; background: #f9f9f9; border-radius: 8px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">📦</div>';
    print '<h2>No Orders Found</h2>';
    print '<p style="color: #666;">'.($filter_status ? 'No orders with status: '.$filter_status : 'You haven\'t placed any orders yet.').'</p>';
    print '<br><a class="butAction" href="product_catalog.php">Browse Products</a>';
    print '</div>';
    llxFooter();
    exit;
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Order Ref</th>';
print '<th>Date</th>';
print '<th class="center">Items</th>';
print '<th class="center">Amount</th>';
print '<th>Status</th>';
print '<th>Payment</th>';
print '<th>Delivery</th>';
print '<th class="center">Actions</th>';
print '</tr>';

while ($order = $db->fetch_object($res)) {
    // Status colors
    $status_colors = array(
        'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳'),
        'Bundled' => array('bg' => '#e3f2fd', 'color' => '#1976d2', 'icon' => '📦'),
        'Picked Up' => array('bg' => '#e1f5fe', 'color' => '#0288d1', 'icon' => '🚚'),
        'In Transit' => array('bg' => '#fff9c4', 'color' => '#f57f17', 'icon' => '🚛'),
        'Delivered' => array('bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '✓')
    );
    $colors = $status_colors[$order->status] ?? array('bg' => '#f5f5f5', 'color' => '#666', 'icon' => '?');
    
    // Payment colors
    $payment_colors = array(
        'Paid' => array('bg' => '#e8f5e9', 'color' => '#2e7d32'),
        'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00'),
        'Pay_On_Delivery' => array('bg' => '#e3f2fd', 'color' => '#1976d2')
    );
    $pay_colors = $payment_colors[$order->payment_status] ?? array('bg' => '#f5f5f5', 'color' => '#666');
    
    print '<tr class="oddeven">';
    print '<td><strong>'.dol_escape_htmltag($order->ref).'</strong></td>';
    print '<td>'.dol_print_date($db->jdate($order->date_creation), 'day').'</td>';
    print '<td class="center">'.$order->item_count.'</td>';
    print '<td class="center"><strong>₦'.number_format($order->total_amount, 2).'</strong></td>';
    print '<td>';
    print '<span style="display:inline-block; padding:4px 10px; border-radius:4px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold; font-size:11px;">';
    print $colors['icon'].' '.dol_escape_htmltag($order->status);
    print '</span>';
    print '</td>';
    print '<td>';
    print '<span style="display:inline-block; padding:4px 8px; border-radius:3px; background:'.$pay_colors['bg'].'; color:'.$pay_colors['color'].'; font-size:10px; font-weight:bold;">';
    print str_replace('_', ' ', $order->payment_status);
    print '</span>';
    print '</td>';
    print '<td style="font-size: 12px; color: #666;">'.dol_trunc($order->delivery_address, 30).'</td>';
    print '<td class="center">';
    print '<a href="view_order.php?id='.$order->rowid.'">View Details</a>';
    print '</td>';
    print '</tr>';
}

print '</table>';

llxFooter();
?>