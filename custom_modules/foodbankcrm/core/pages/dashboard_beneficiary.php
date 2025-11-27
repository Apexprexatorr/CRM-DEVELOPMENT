<?php
/**
 * Beneficiary/Subscriber Dashboard - Custom dashboard for subscribers
 */

// CRITICAL: Define these BEFORE including main.inc.php to bypass CSRF checks
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

$langs->load("admin");

// Security check - beneficiary only
$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to the beneficiary dashboard.');
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

?>
<script>
document.body.classList.add('beneficiary-user');
</script>
<?php

llxHeader('', 'My Dashboard');

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1>👋 Welcome, '.dol_escape_htmltag($subscriber->firstname).'!</h1>';
print '<p style="color: #666; margin: 5px 0;">Subscriber ID: '.dol_escape_htmltag($subscriber->ref).'</p>';
print '</div>';
print '<a class="butAction" href="browse_products.php">🛒 Browse Products</a>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Subscription Plan</div>';
print '<div style="font-size: 24px; font-weight: bold;">'.dol_escape_htmltag($subscriber->subscription_type ?: 'None').'</div>';
print '</div>';

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Status</div>';
print '<div style="display: inline-block; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 20px; font-weight: bold; font-size: 16px;">';
print dol_escape_htmltag($subscriber->subscription_status);
print '</div>';
print '</div>';

if ($subscriber->subscription_end_date) {
    print '<div>';
    print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Valid Until</div>';
    print '<div style="font-size: 20px; font-weight: bold;">'.dol_print_date($db->jdate($subscriber->subscription_end_date), 'day').'</div>';
    print '</div>';
}

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Household Size</div>';
print '<div style="font-size: 24px; font-weight: bold;">'.$subscriber->household_size.' '.($subscriber->household_size == 1 ? 'person' : 'people').'</div>';
print '</div>';

print '</div>';
print '</div>';

$sql_stats = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status IN ('Pending', 'Bundled', 'Picked Up', 'In Transit') THEN 1 ELSE 0 END) as active_orders,
    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(total_amount) as total_spent
    FROM ".MAIN_DB_PREFIX."foodbank_distributions
    WHERE fk_beneficiary = ".(int)$subscriber_id;

$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">';

print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Orders</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->total_orders ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Active Orders</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->active_orders ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Delivered</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->delivered_orders ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Spent</div>';
print '<div style="font-size: 28px; font-weight: bold;">₦'.number_format($stats->total_spent ?? 0, 0).'</div>';
print '</div>';

print '</div>';

print '<h2>🎉 Subscriber Dashboard Loaded Successfully!</h2>';
print '<p style="color: #666;">Your custom dashboard is working. Start browsing products to place orders.</p>';

print '<div style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">';

print '<a href="browse_products.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit;">';
print '<div style="font-size: 40px; margin-bottom: 10px;">🛒</div>';
print '<h3 style="margin: 0 0 5px 0;">Browse Products</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">View available items</p>';
print '</a>';

print '<a href="view_cart.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit;">';
print '<div style="font-size: 40px; margin-bottom: 10px;">🛍️</div>';
print '<h3 style="margin: 0 0 5px 0;">My Cart</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Manage your cart</p>';
print '</a>';

print '<a href="my_orders.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit;">';
print '<div style="font-size: 40px; margin-bottom: 10px;">📦</div>';
print '<h3 style="margin: 0 0 5px 0;">My Orders</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">View order history</p>';
print '</a>';

print '<a href="my_profile.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit;">';
print '<div style="font-size: 40px; margin-bottom: 10px;">👤</div>';
print '<h3 style="margin: 0 0 5px 0;">My Profile</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Update your info</p>';
print '</a>';

print '</div>';

llxFooter();
?>
