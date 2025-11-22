<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a beneficiary/subscriber
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to the subscriber dashboard.');
}

// Get subscriber information
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    llxHeader('', 'Subscriber Dashboard');
    print '<div class="error">Subscriber profile not found. Please contact administrator.</div>';
    llxFooter();
    exit;
}

$subscriber = $db->fetch_object($res);
$subscriber_id = $subscriber->rowid;

llxHeader('', 'Subscriber Dashboard');

// Header
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1>👋 Welcome, '.dol_escape_htmltag($subscriber->firstname).'!</h1>';
print '<p style="color: #666; margin: 5px 0;">Subscriber ID: '.dol_escape_htmltag($subscriber->ref).'</p>';
print '</div>';
print '<a class="butAction" href="product_catalog.php">🛒 Browse Products</a>';
print '</div>';

// Subscription Status Card
print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';

// Subscription Type
print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Subscription Plan</div>';
print '<div style="font-size: 28px; font-weight: bold;">'.dol_escape_htmltag($subscriber->subscription_type ?: 'No Plan').'</div>';
print '</div>';

// Status
$status = $subscriber->subscription_status;
if ($subscriber->subscription_end_date && strtotime($subscriber->subscription_end_date) < time()) {
    $status = 'Expired';
}

$status_icon = ($status == 'Active') ? '✓' : (($status == 'Pending') ? '⏳' : '✗');
print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Status</div>';
print '<div style="font-size: 28px; font-weight: bold;">'.$status_icon.' '.dol_escape_htmltag($status).'</div>';
print '</div>';

// Expiry Date
if ($subscriber->subscription_end_date) {
    $days_left = floor((strtotime($subscriber->subscription_end_date) - time()) / 86400);
    print '<div>';
    print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Valid Until</div>';
    print '<div style="font-size: 20px; font-weight: bold;">'.dol_print_date($db->jdate($subscriber->subscription_end_date), 'day').'</div>';
    if ($days_left > 0 && $days_left < 30) {
        print '<div style="font-size: 12px; opacity: 0.9; margin-top: 5px;">⚠ Expires in '.$days_left.' days</div>';
    }
    print '</div>';
}

// Amount
if ($subscriber->subscription_fee) {
    print '<div>';
    print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Subscription Fee</div>';
    print '<div style="font-size: 28px; font-weight: bold;">₦'.number_format($subscriber->subscription_fee, 0).'</div>';
    print '</div>';
}

print '</div>';

// Action based on status
if ($status == 'Pending') {
    print '<div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.2); border-radius: 5px;">';
    print '<strong>⚠ Payment Pending</strong><br>';
    print 'Please complete your subscription payment to activate your account and start ordering.';
    print '</div>';
} elseif ($status == 'Expired') {
    print '<div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.2); border-radius: 5px;">';
    print '<strong>⚠ Subscription Expired</strong><br>';
    print 'Your subscription has expired. Please renew to continue ordering.';
    print '</div>';
}

print '</div>';

// Quick Stats
$sql_stats = "SELECT 
    COUNT(DISTINCT d.rowid) as total_orders,
    SUM(d.total_amount) as total_spent,
    SUM(CASE WHEN d.status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN d.status IN ('Pending', 'Bundled', 'Picked Up', 'In Transit') THEN 1 ELSE 0 END) as active_orders
    FROM ".MAIN_DB_PREFIX."foodbank_distributions d
    WHERE d.fk_beneficiary = ".(int)$subscriber_id;

$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">';

// Total Orders
print '<div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Orders</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->total_orders ?? 0).'</div>';
print '</div>';

// Active Orders
print '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Active Orders</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->active_orders ?? 0).'</div>';
print '</div>';

// Delivered Orders
print '<div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Delivered</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->delivered_orders ?? 0).'</div>';
print '</div>';

// Total Spent
print '<div style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.8; margin-bottom: 5px;">Total Spent</div>';
print '<div style="font-size: 32px; font-weight: bold;">₦'.number_format($stats->total_spent ?? 0, 0).'</div>';
print '</div>';

print '</div>';

// Recent Orders
$sql_orders = "SELECT d.*, 
        COUNT(dl.rowid) as item_count
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_distribution_lines dl ON d.rowid = dl.fk_distribution
        WHERE d.fk_beneficiary = ".(int)$subscriber_id."
        GROUP BY d.rowid
        ORDER BY d.date_creation DESC
        LIMIT 10";

$res_orders = $db->query($sql_orders);

if ($res_orders && $db->num_rows($res_orders) > 0) {
    print '<h2>📦 Recent Orders</h2>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Order Ref</th>';
    print '<th>Date</th>';
    print '<th class="center">Items</th>';
    print '<th class="center">Amount</th>';
    print '<th>Status</th>';
    print '<th>Payment</th>';
    print '<th class="center">Actions</th>';
    print '</tr>';
    
    while ($order = $db->fetch_object($res_orders)) {
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
        print '<td class="center">';
        print '<a href="view_order.php?id='.$order->rowid.'">View</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '</table>';
} else {
    print '<div style="text-align: center; padding: 60px; background: #f9f9f9; border-radius: 8px; margin-top: 30px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">🛒</div>';
    print '<h2>No Orders Yet</h2>';
    print '<p style="color: #666;">Start shopping to place your first order!</p>';
    print '<br><a class="butAction" href="product_catalog.php">Browse Products</a>';
    print '</div>';
}

// Quick Links
print '<div style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">';

print '<a href="product_catalog.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'; this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'">';
print '<div style="font-size: 40px; margin-bottom: 10px;">🛒</div>';
print '<h3 style="margin: 0 0 5px 0;">Browse Products</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Shop from our product catalog</p>';
print '</a>';

print '<a href="view_cart.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'; this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'">';
print '<div style="font-size: 40px; margin-bottom: 10px;">🛍️</div>';
print '<h3 style="margin: 0 0 5px 0;">View Cart</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Review your shopping cart</p>';
print '</a>';

print '<a href="my_orders.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'; this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'">';
print '<div style="font-size: 40px; margin-bottom: 10px;">📦</div>';
print '<h3 style="margin: 0 0 5px 0;">My Orders</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Track your order history</p>';
print '</a>';

print '<a href="my_profile.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'; this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'">';
print '<div style="font-size: 40px; margin-bottom: 10px;">👤</div>';
print '<h3 style="margin: 0 0 5px 0;">My Profile</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Update your information</p>';
print '</a>';

print '</div>';

llxFooter();
?>