<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a beneficiary
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access.');
}

// Get subscriber information
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
$subscriber = $db->fetch_object($res);
$subscriber_id = $subscriber->rowid;

$order_id = GETPOST('id', 'int');

if (!$order_id) {
    header('Location: my_orders.php');
    exit;
}

llxHeader('', 'Order Details');

// Get order details
$sql = "SELECT d.*, b.firstname, b.lastname, b.email, b.phone
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        WHERE d.rowid = ".(int)$order_id." 
        AND d.fk_beneficiary = ".(int)$subscriber_id;

$res = $db->query($sql);
$order = $db->fetch_object($res);

if (!$order) {
    print '<div class="error">Order not found.</div>';
    print '<div><a href="my_orders.php">← Back to Orders</a></div>';
    llxFooter();
    exit;
}

print '<div><a href="my_orders.php">← Back to My Orders</a></div><br>';

print '<h1>Order Details: '.dol_escape_htmltag($order->ref).'</h1>';

// Order Tracking Timeline
print '<div style="background: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin-top: 0;">📍 Order Tracking</h2>';

$statuses = array('Pending', 'Bundled', 'Picked Up', 'In Transit', 'Delivered');
$status_icons = array(
    'Pending' => '📋',
    'Bundled' => '📦',
    'Picked Up' => '🚚',
    'In Transit' => '🚛',
    'Delivered' => '✅'
);

$current_status_index = array_search($order->status, $statuses);

print '<div style="display: flex; justify-content: space-between; position: relative; margin: 30px 0;">';

// Progress line
print '<div style="position: absolute; top: 20px; left: 0; right: 0; height: 4px; background: #e0e0e0; z-index: 1;"></div>';
print '<div style="position: absolute; top: 20px; left: 0; width: '.(($current_status_index / (count($statuses) - 1)) * 100).'%; height: 4px; background: #4caf50; z-index: 2; transition: width 0.5s;"></div>';

foreach ($statuses as $index => $status) {
    $is_completed = $index <= $current_status_index;
    $is_current = $index == $current_status_index;
    
    $circle_color = $is_completed ? '#4caf50' : '#e0e0e0';
    $text_color = $is_completed ? '#333' : '#999';
    
    print '<div style="flex: 1; text-align: center; position: relative; z-index: 3;">';
    print '<div style="width: 40px; height: 40px; border-radius: 50%; background: '.$circle_color.'; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 20px; '.($is_current ? 'box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.2); animation: pulse 2s infinite;' : '').'">';
    print $status_icons[$status];
    print '</div>';
    print '<div style="margin-top: 10px; font-size: 12px; font-weight: bold; color: '.$text_color.';">'.$status.'</div>';
    print '</div>';
}

print '</div>';

print '<style>
@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.2); }
    50% { box-shadow: 0 0 0 8px rgba(76, 175, 80, 0.4); }
}
</style>';

print '</div>';

// Order Info Grid
print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">';

// Order Information
print '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px;">';
print '<h3 style="margin-top: 0;">📋 Order Information</h3>';
print '<table style="width: 100%; font-size: 14px;">';
print '<tr><td width="40%"><strong>Order Ref:</strong></td><td>'.dol_escape_htmltag($order->ref).'</td></tr>';
print '<tr><td><strong>Order Date:</strong></td><td>'.dol_print_date($db->jdate($order->date_creation), 'dayhour').'</td></tr>';
print '<tr><td><strong>Status:</strong></td><td>';

$status_colors = array(
    'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00'),
    'Bundled' => array('bg' => '#e3f2fd', 'color' => '#1976d2'),
    'Picked Up' => array('bg' => '#e1f5fe', 'color' => '#0288d1'),
    'In Transit' => array('bg' => '#fff9c4', 'color' => '#f57f17'),
    'Delivered' => array('bg' => '#e8f5e9', 'color' => '#2e7d32')
);
$colors = $status_colors[$order->status] ?? array('bg' => '#f5f5f5', 'color' => '#666');

print '<span style="display:inline-block; padding:5px 12px; border-radius:4px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold;">';
print dol_escape_htmltag($order->status);
print '</span>';
print '</td></tr>';
print '<tr><td><strong>Payment Method:</strong></td><td>'.dol_escape_htmltag(str_replace('_', ' ', $order->payment_method)).'</td></tr>';
print '<tr><td><strong>Payment Status:</strong></td><td>'.dol_escape_htmltag(str_replace('_', ' ', $order->payment_status)).'</td></tr>';
print '</table>';
print '</div>';

// Delivery Information
print '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px;">';
print '<h3 style="margin-top: 0;">🚚 Delivery Information</h3>';
print '<table style="width: 100%; font-size: 14px;">';
print '<tr><td width="40%"><strong>Name:</strong></td><td>'.dol_escape_htmltag($order->firstname.' '.$order->lastname).'</td></tr>';
print '<tr><td><strong>Phone:</strong></td><td>'.dol_escape_htmltag($order->phone).'</td></tr>';
print '<tr><td><strong>Address:</strong></td><td>'.nl2br(dol_escape_htmltag($order->delivery_address)).'</td></tr>';
if ($order->notes) {
    print '<tr><td><strong>Notes:</strong></td><td>'.nl2br(dol_escape_htmltag($order->notes)).'</td></tr>';
}
if ($order->scheduled_date) {
    print '<tr><td><strong>Scheduled:</strong></td><td>'.dol_print_date($db->jdate($order->scheduled_date), 'day').'</td></tr>';
}
print '</table>';
print '</div>';

print '</div>';

// Order Items
$sql_items = "SELECT dl.*, d.product_name, d.unit, d.category,
        (dl.quantity_distributed * dl.unit_price) as line_total
        FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines dl
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_donations d ON dl.fk_donation = d.rowid
        WHERE dl.fk_distribution = ".(int)$order_id;

$res_items = $db->query($sql_items);

print '<h2>📦 Order Items</h2>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Product</th>';
print '<th>Category</th>';
print '<th class="center">Quantity</th>';
print '<th class="center">Unit Price</th>';
print '<th class="center">Total</th>';
print '</tr>';

$order_total = 0;
while ($item = $db->fetch_object($res_items)) {
    $order_total += $item->line_total;
    
    print '<tr class="oddeven">';
    print '<td><strong>'.dol_escape_htmltag($item->product_name).'</strong></td>';
    print '<td>'.dol_escape_htmltag($item->category).'</td>';
    print '<td class="center">'.$item->quantity_distributed.' '.$item->unit.'</td>';
    print '<td class="center">₦'.number_format($item->unit_price, 2).'</td>';
    print '<td class="center"><strong>₦'.number_format($item->line_total, 2).'</strong></td>';
    print '</tr>';
}

print '<tr class="liste_total">';
print '<td colspan="4" class="right"><strong>Grand Total:</strong></td>';
print '<td class="center"><strong style="font-size: 20px; color: #1976d2;">₦'.number_format($order_total, 2).'</strong></td>';
print '</tr>';

print '</table>';

// Action buttons
if ($order->status != 'Delivered' && $order->payment_status == 'Pending') {
    print '<div style="margin-top: 30px; background: #fff3e0; padding: 20px; border-radius: 5px; border-left: 4px solid #f57c00;">';
    print '<h3 style="margin-top: 0;">💳 Payment Required</h3>';
    print '<p>Your payment is still pending. Please complete payment to avoid order cancellation.</p>';
    print '<a class="button" href="make_payment.php?order_id='.$order_id.'">Complete Payment Now</a>';
    print '</div>';
}

llxFooter();
?>