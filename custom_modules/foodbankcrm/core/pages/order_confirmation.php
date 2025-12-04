<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

$langs->load("admin");

$user_is_subscriber = FoodbankPermissions::isBeneficiary($user, $db);
$subscriber_id = null;

if ($user_is_subscriber) {
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $db->num_rows($res) > 0) {
        $subscriber = $db->fetch_object($res);
        $subscriber_id = $subscriber->rowid;
    }
}

if (!$subscriber_id) {
    accessforbidden('You must be a subscriber.');
}

$order_id = GETPOST('order_id', 'int');
$payment_pending = GETPOST('payment', 'alpha') == 'pending';

if (!$order_id) {
    header('Location: product_catalog.php');
    exit;
}

llxHeader('', 'Order Confirmation');

echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

// Get order details
$sql = "SELECT d.*, b.firstname, b.lastname, b.email, b.phone
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        WHERE d.rowid = ".(int)$order_id." 
        AND d.fk_beneficiary = ".(int)$subscriber_id;

$res = $db->query($sql);
$order = $db->fetch_object($res);

if (!$order) {
    print '<div style="padding: 30px; text-align: center;">';
    print '<div class="error">Order not found.</div>';
    print '<div><a href="dashboard_beneficiary.php" class="butAction">← Go to Dashboard</a></div>';
    print '</div>';
    llxFooter();
    exit;
}

// Get order items
$sql_items = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines 
              WHERE fk_distribution = ".(int)$order_id;
$res_items = $db->query($sql_items);

// Determine status color
$status_color = '#ffc107'; // Yellow for Pending
if ($order->status == 'Delivered') $status_color = '#28a745';
if ($order->status == 'Pending') $status_color = '#17a2b8';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1200px; margin: 0 auto;">';

// Success Header
print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 40px; border-radius: 12px; text-align: center; margin-bottom: 30px; box-shadow: 0 8px 16px rgba(102,126,234,0.3);">';
print '<div style="font-size: 80px; margin-bottom: 20px; animation: bounce 1s ease;">✓</div>';
print '<h1 style="margin: 0 0 15px 0; font-size: 36px;">Order Placed Successfully!</h1>';
print '<p style="font-size: 18px; margin: 0; opacity: 0.9;">Thank you for your order, '.dol_escape_htmltag($order->firstname).'!</p>';
print '<p style="font-size: 14px; margin: 10px 0 0 0; opacity: 0.8;">Order #'.dol_escape_htmltag($order->ref).'</p>';
print '</div>';

// Payment Pending Alert
if ($payment_pending) {
    print '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 25px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
    print '<h3 style="margin: 0 0 15px 0; color: #856404; display: flex; align-items: center;"><span style="font-size: 24px; margin-right: 10px;">⚠️</span> Payment Pending</h3>';
    print '<p style="margin: 0 0 15px 0; color: #856404;">Please complete your payment to process your order.</p>';
    print '<a href="process_order_payment.php?order_id='.$order_id.'" class="butAction" style="display: inline-block;">💳 Complete Payment with Paystack</a>';
    print '</div>';
}

// Main Content Grid
print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">';

// LEFT COLUMN - Order Items
print '<div>';

// Order Items Card
print '<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h2 style="margin: 0 0 20px 0; display: flex; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">';
print '<span style="font-size: 28px; margin-right: 10px;">📦</span> Order Items';
print '</h2>';

if ($res_items && $db->num_rows($res_items) > 0) {
    while ($item = $db->fetch_object($res_items)) {
        print '<div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f0f0f0;">';
        print '<div style="flex: 1;">';
        print '<div style="font-weight: bold; font-size: 16px; color: #333;">'.dol_escape_htmltag($item->product_name).'</div>';
        print '<div style="color: #666; font-size: 14px; margin-top: 5px;">'.number_format($item->quantity, 2).' '.dol_escape_htmltag($item->unit).'</div>';
        print '</div>';
        print '<div style="font-size: 18px; font-weight: bold; color: #28a745;">✓</div>';
        print '</div>';
    }
} else {
    print '<div style="text-align: center; padding: 40px; color: #999;">';
    print '<div style="font-size: 48px; margin-bottom: 15px;">📦</div>';
    print '<p>Order items are being processed...</p>';
    print '</div>';
}

print '</div>';

// What Happens Next
print '<div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(67,233,123,0.3);">';
print '<h3 style="margin: 0 0 20px 0; font-size: 20px; display: flex; align-items: center;">';
print '<span style="font-size: 28px; margin-right: 10px;">📋</span> What Happens Next?';
print '</h3>';
print '<div style="display: flex; flex-direction: column; gap: 15px;">';

$steps = [
    ['icon' => '⏳', 'title' => 'Order Review', 'desc' => 'We\'re reviewing your order now'],
    ['icon' => '✅', 'title' => 'Preparation', 'desc' => 'Your items will be prepared'],
    ['icon' => '📦', 'title' => 'Packing', 'desc' => 'Carefully packed for delivery'],
    ['icon' => '🚚', 'title' => 'Delivery', 'desc' => 'Delivered in 2-4 business days']
];

foreach ($steps as $i => $step) {
    print '<div style="display: flex; align-items: start; gap: 15px;">';
    print '<div style="font-size: 32px; min-width: 40px;">'.$step['icon'].'</div>';
    print '<div style="flex: 1;">';
    print '<div style="font-weight: bold; font-size: 16px; margin-bottom: 5px;">'.$step['title'].'</div>';
    print '<div style="font-size: 14px; opacity: 0.9;">'.$step['desc'].'</div>';
    print '</div>';
    print '</div>';
}

print '</div>';
print '</div>';

print '</div>'; // End left column

// RIGHT COLUMN - Order Details
print '<div>';

// Order Information Card
print '<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h3 style="margin: 0 0 20px 0; display: flex; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">';
print '<span style="font-size: 24px; margin-right: 10px;">📋</span> Order Details';
print '</h3>';

$details = [
    ['label' => 'Order Ref', 'value' => $order->ref, 'bold' => true],
    ['label' => 'Date', 'value' => dol_print_date($db->jdate($order->date_distribution), 'day')],
    ['label' => 'Status', 'value' => '<span style="background: '.$status_color.'; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold;">'.dol_escape_htmltag($order->status).'</span>', 'html' => true],
    ['label' => 'Payment', 'value' => ucfirst(str_replace('_', ' ', $order->payment_method))],
    ['label' => 'Amount', 'value' => '₦'.number_format($order->total_amount, 2), 'bold' => true, 'color' => '#28a745']
];

foreach ($details as $detail) {
    print '<div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f8f9fa;">';
    print '<span style="color: #666; font-size: 14px;">'.$detail['label'].':</span>';
    if (isset($detail['html']) && $detail['html']) {
        print '<span>'.$detail['value'].'</span>';
    } else {
        $style = 'font-size: 14px;';
        if (isset($detail['bold']) && $detail['bold']) $style .= ' font-weight: bold;';
        if (isset($detail['color'])) $style .= ' color: '.$detail['color'].';';
        print '<span style="'.$style.'">'.dol_escape_htmltag($detail['value']).'</span>';
    }
    print '</div>';
}

print '</div>';

// Delivery Information Card
print '<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
print '<h3 style="margin: 0 0 20px 0; display: flex; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">';
print '<span style="font-size: 24px; margin-right: 10px;">🚚</span> Delivery Info';
print '</h3>';

print '<div style="margin-bottom: 15px;">';
print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">DELIVERY ADDRESS</div>';
print '<div style="font-size: 14px; line-height: 1.6;">'.nl2br(dol_escape_htmltag($order->note)).'</div>';
print '</div>';

print '<div style="margin-bottom: 15px;">';
print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">PHONE NUMBER</div>';
print '<div style="font-size: 14px; font-weight: bold;">'.dol_escape_htmltag($order->phone).'</div>';
print '</div>';

print '<div>';
print '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">ESTIMATED DELIVERY</div>';
print '<div style="font-size: 14px; font-weight: bold; color: #28a745;">2-4 business days</div>';
print '</div>';

print '</div>';

print '</div>'; // End right column

print '</div>'; // End grid

// Action Buttons
print '<div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">';
print '<a href="my_orders.php" class="butAction" style="min-width: 200px; text-align: center;">📋 View All Orders</a>';
print '<a href="product_catalog.php" class="butAction" style="min-width: 200px; text-align: center;">🛒 Continue Shopping</a>';
print '<a href="dashboard_beneficiary.php" class="butAction" style="min-width: 200px; text-align: center;">🏠 Go to Dashboard</a>';
print '</div>';

print '</div>';

llxFooter();
?>
