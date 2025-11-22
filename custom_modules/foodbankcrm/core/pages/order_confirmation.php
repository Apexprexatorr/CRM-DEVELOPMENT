<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a subscriber
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

if (!$order_id) {
    header('Location: product_catalog.php');
    exit;
}

llxHeader('', 'Order Confirmation');

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
    print '<div><a href="dashboard_beneficiary.php">← Go to Dashboard</a></div>';
    llxFooter();
    exit;
}

// Get order items
$sql = "SELECT dl.*, d.product_name, d.unit,
        (dl.quantity_distributed * dl.unit_price) as line_total
        FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines dl
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_donations d ON dl.fk_donation = d.rowid
        WHERE dl.fk_distribution = ".(int)$order_id;

$res_items = $db->query($sql);

// Success animation
print '<div style="text-align: center; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; margin-bottom: 30px;">';
print '<div style="font-size: 80px; margin-bottom: 20px;">✓</div>';
print '<h1 style="margin: 0 0 10px 0; font-size: 32px;">Order Placed Successfully!</h1>';
print '<p style="font-size: 18px; opacity: 0.9;">Thank you for your order, '.dol_escape_htmltag($order->firstname).'!</p>';
print '</div>';

// Order details
print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">';

// Order info
print '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px;">';
print '<h3 style="margin-top: 0;">📋 Order Information</h3>';
print '<table style="width: 100%; font-size: 14px;">';
print '<tr><td><strong>Order Ref:</strong></td><td>'.dol_escape_htmltag($order->ref).'</td></tr>';
print '<tr><td><strong>Order Date:</strong></td><td>'.dol_print_date($db->jdate($order->date_creation), 'dayhour').'</td></tr>';
print '<tr><td><strong>Status:</strong></td><td><span style="background: #fff3e0; color: #f57c00; padding: 3px 8px; border-radius: 3px; font-weight: bold;">'.dol_escape_htmltag($order->status).'</span></td></tr>';
print '<tr><td><strong>Payment:</strong></td><td>'.dol_escape_htmltag($order->payment_method).'</td></tr>';
print '<tr><td><strong>Total Amount:</strong></td><td><strong style="font-size: 18px; color: #1976d2;">₦'.number_format($order->total_amount, 2).'</strong></td></tr>';
print '</table>';
print '</div>';

// Delivery info
print '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px;">';
print '<h3 style="margin-top: 0;">🚚 Delivery Information</h3>';
print '<table style="width: 100%; font-size: 14px;">';
print '<tr><td><strong>Address:</strong></td><td>'.nl2br(dol_escape_htmltag($order->delivery_address)).'</td></tr>';
print '<tr><td><strong>Phone:</strong></td><td>'.dol_escape_htmltag($order->phone).'</td></tr>';
if ($order->notes) {
    print '<tr><td><strong>Notes:</strong></td><td>'.nl2br(dol_escape_htmltag($order->notes)).'</td></tr>';
}
print '<tr><td><strong>Est. Delivery:</strong></td><td>2-4 business days</td></tr>';
print '</table>';
print '</div>';

print '</div>';

// Order items
print '<h2>📦 Order Items</h2>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Product</th>';
print '<th class="center">Quantity</th>';
print '<th class="center">Unit Price</th>';
print '<th class="center">Total</th>';
print '</tr>';

$order_total = 0;
while ($item = $db->fetch_object($res_items)) {
    $order_total += $item->line_total;
    
    print '<tr class="oddeven">';
    print '<td><strong>'.dol_escape_htmltag($item->product_name).'</strong></td>';
    print '<td class="center">'.$item->quantity_distributed.' '.$item->unit.'</td>';
    print '<td class="center">₦'.number_format($item->unit_price, 2).'</td>';
    print '<td class="center"><strong>₦'.number_format($item->line_total, 2).'</strong></td>';
    print '</tr>';
}

print '<tr class="liste_total">';
print '<td colspan="3" class="right"><strong>Total:</strong></td>';
print '<td class="center"><strong style="font-size: 18px; color: #1976d2;">₦'.number_format($order_total, 2).'</strong></td>';
print '</tr>';

print '</table>';

// Next steps
print '<div style="margin-top: 30px; background: #e8f5e9; padding: 20px; border-radius: 5px; border-left: 4px solid #4caf50;">';
print '<h3 style="margin-top: 0;">✓ What Happens Next?</h3>';
print '<ol style="margin-bottom: 0;">';
print '<li>We\'re preparing your order right now</li>';
print '<li>You\'ll receive updates as your order moves through each stage</li>';
print '<li>Track your order status in your dashboard</li>';

if ($order->payment_method == 'pay_now') {
    print '<li><strong>Payment:</strong> Complete payment via the link sent to your email</li>';
} else {
    print '<li><strong>Payment:</strong> Prepare cash payment for delivery</li>';
}

print '</ol>';
print '</div>';

// Action buttons
print '<div style="display: flex; gap: 15px; margin-top: 30px; justify-content: center;">';
print '<a class="button" href="dashboard_beneficiary.php">Go to Dashboard</a>';
print '<a class="button" href="product_catalog.php">Continue Shopping</a>';
print '</div>';

llxFooter();
?>