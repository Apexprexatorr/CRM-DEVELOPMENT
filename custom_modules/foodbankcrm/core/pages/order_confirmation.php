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

if (!$order_id) {
    header('Location: product_catalog.php');
    exit;
}

llxHeader('', 'Order Confirmation');

// --- UI CLEANUP ---
print '<style>
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
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
    print '<div style="padding: 30px; text-align: center;"><div class="error">Order not found.</div></div>';
    llxFooter();
    exit;
}

// Get items
$sql_items = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE fk_distribution = ".(int)$order_id;
$res_items = $db->query($sql_items);

// Determine Status Colors
$status_color = '#ffc107'; 
if ($order->status == 'Delivered') $status_color = '#28a745';
if ($order->status == 'Pending') $status_color = '#17a2b8';

// --- LOGIC: DETERMINE PAGE CONTENT ---
$is_cod = ($order->payment_method == 'pay_on_delivery');
$is_paid = ($order->payment_status == 'Paid' || $order->payment_status == 'Success');
$is_pending_paystack = (!$is_cod && !$is_paid);

print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1200px; margin: 0 auto; font-family: \'Segoe UI\', sans-serif;">';

// --- HEADER SECTION (CHANGES BASED ON PAYMENT) ---

if ($is_cod) {
    // 1. CASH ON DELIVERY
    print '<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 60px 40px; border-radius: 12px; text-align: center; margin-bottom: 30px; box-shadow: 0 8px 16px rgba(56,239,125,0.3);">';
    print '<div style="font-size: 80px; margin-bottom: 20px;">📦</div>';
    print '<h1 style="margin: 0 0 15px 0; font-size: 36px;">Order Confirmed!</h1>';
    print '<p style="font-size: 18px; margin: 0;">Order placed. Please pay <strong>₦'.number_format($order->total_amount, 2).'</strong> on delivery.</p>';
    print '</div>';

} elseif ($is_pending_paystack) {
    // 2. PAYSTACK PENDING (Warning)
    print '<div style="background: #fff3cd; border-left: 6px solid #ffc107; padding: 40px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">';
    print '<div style="font-size: 60px; margin-bottom: 15px;">⚠️</div>';
    print '<h1 style="margin: 0 0 10px 0; color: #856404;">Payment Incomplete</h1>';
    print '<p style="margin: 0 0 20px 0; color: #856404; font-size: 16px;">Your order is saved, but payment is pending.</p>';
    print '<a href="process_order_payment.php?order_id='.$order_id.'" class="butAction" style="display: inline-block; padding: 12px 30px; font-size: 16px; background: #28a745; color: white; border-radius: 50px;">💳 Complete Payment Now</a>';
    print '</div>';

} else {
    // 3. PAID (Standard Success)
    print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 40px; border-radius: 12px; text-align: center; margin-bottom: 30px; box-shadow: 0 8px 16px rgba(102,126,234,0.3);">';
    print '<div style="font-size: 80px; margin-bottom: 20px;">✓</div>';
    print '<h1 style="margin: 0 0 15px 0; font-size: 36px;">Order Placed Successfully!</h1>';
    print '<p style="font-size: 18px; margin: 0;">Thank you for your order, '.dol_escape_htmltag($order->firstname).'!</p>';
    print '</div>';
}

// --- GRID LAYOUT ---
print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">';

// LEFT: ITEMS
print '<div>';
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
}
print '</div></div>';

// RIGHT: DETAILS
print '<div>';
print '<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
print '<h3 style="margin: 0 0 20px 0; display: flex; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">';
print '<span style="font-size: 24px; margin-right: 10px;">📋</span> Order Details';
print '</h3>';

$pay_label = $is_cod ? 'Cash on Delivery' : ($is_paid ? 'Paid via Paystack' : 'Pending');

print '<div style="margin-bottom: 12px; display: flex; justify-content: space-between;"><span style="color:#666;">Ref:</span> <strong>'.dol_escape_htmltag($order->ref).'</strong></div>';
print '<div style="margin-bottom: 12px; display: flex; justify-content: space-between;"><span style="color:#666;">Status:</span> <strong>'.dol_escape_htmltag($order->status).'</strong></div>';
print '<div style="margin-bottom: 12px; display: flex; justify-content: space-between;"><span style="color:#666;">Payment:</span> <strong>'.$pay_label.'</strong></div>';
print '<div style="margin-top: 15px; padding-top:15px; border-top:1px solid #eee; display: flex; justify-content: space-between; font-size: 18px; color: #28a745; font-weight: bold;"><span>Total:</span> <span>₦'.number_format($order->total_amount, 2).'</span></div>';

print '</div>';

// Delivery Info
print '<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px;">';
print '<h3 style="margin: 0 0 20px 0; font-size: 18px;">🚚 Delivery Info</h3>';
print '<p style="margin-bottom: 5px; color:#666; font-size: 13px;">ADDRESS</p>';
print '<p style="margin-bottom: 15px;">'.dol_escape_htmltag($order->note).'</p>';
print '<p style="margin-bottom: 5px; color:#666; font-size: 13px;">PHONE</p>';
print '<p>'.dol_escape_htmltag($order->phone).'</p>';
print '</div>';

print '</div>'; // End right column
print '</div>'; // End grid

// Buttons
print '<div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">';
print '<a href="my_orders.php" class="butAction" style="min-width: 200px; text-align: center;">📋 View All Orders</a>';
print '<a href="product_catalog.php" class="butAction" style="min-width: 200px; text-align: center;">🛒 Continue Shopping</a>';
print '<a href="dashboard_beneficiary.php" class="butAction" style="min-width: 200px; text-align: center;">🏠 Go to Dashboard</a>';
print '</div>';

print '</div>';

llxFooter();
?>