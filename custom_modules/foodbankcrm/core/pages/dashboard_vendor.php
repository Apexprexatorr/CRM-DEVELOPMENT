<?php
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$user_is_vendor) {
    accessforbidden('You do not have access to the vendor dashboard.');
}

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    llxHeader('', 'Vendor Dashboard');
    print '<div class="error">Vendor profile not found. Please contact administrator.</div>';
    llxFooter();
    exit;
}

$vendor = $db->fetch_object($res);
$vendor_id = $vendor->rowid;

llxHeader('', 'Vendor Dashboard');

// HIDE LEFT MENU - FULL WIDTH
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

print '<div style="width: 100%; padding: 30px; box-sizing: border-box;">';

// Header
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1 style="margin: 0;">👋 Welcome, '.dol_escape_htmltag($vendor->name).'!</h1>';
print '<p style="color: #666; margin: 5px 0 0 0;">Vendor ID: '.dol_escape_htmltag($vendor->ref).'</p>';
print '</div>';
print '<a class="butAction" href="create_donation.php">🎁 Submit Donation</a>';
print '</div>';

// Vendor Info Card
print '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Business Name</div>';
print '<div style="font-size: 24px; font-weight: bold;">'.dol_escape_htmltag($vendor->name).'</div>';
print '</div>';

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Category</div>';
print '<div style="font-size: 20px; font-weight: bold;">'.dol_escape_htmltag($vendor->category ?: 'General').'</div>';
print '</div>';

print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Contact</div>';
print '<div style="font-size: 16px;">'.dol_escape_htmltag($vendor->contact_phone ?: $vendor->phone).'</div>';
if ($vendor->contact_email || $vendor->email) {
    print '<div style="font-size: 13px; opacity: 0.9;">'.dol_escape_htmltag($vendor->contact_email ?: $vendor->email).'</div>';
}
print '</div>';

if ($vendor->address) {
    print '<div>';
    print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Address</div>';
    print '<div style="font-size: 14px;">'.nl2br(dol_escape_htmltag($vendor->address)).'</div>';
    print '</div>';
}

print '</div>';
print '</div>';

// Get statistics
$sql_stats = "SELECT 
    COUNT(DISTINCT d.rowid) as total_donations,
    COALESCE(SUM(d.quantity), 0) as total_quantity,
    COALESCE(SUM(CASE WHEN d.status = 'Received' THEN d.quantity ELSE 0 END), 0) as received_quantity,
    COALESCE(SUM(CASE WHEN d.status = 'Pending' THEN 1 ELSE 0 END), 0) as pending_donations,
    COUNT(DISTINCT d.product_name) as unique_products
    FROM ".MAIN_DB_PREFIX."foodbank_donations d
    WHERE d.fk_vendor = ".(int)$vendor_id;

$res_stats = $db->query($sql_stats);

$stats = new stdClass();
$stats->total_donations = 0;
$stats->total_quantity = 0;
$stats->received_quantity = 0;
$stats->pending_donations = 0;
$stats->unique_products = 0;

if ($res_stats) {
    $stats = $db->fetch_object($res_stats);
}

// Stats Cards
print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">';

print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Donations</div>';
print '<div style="font-size: 48px; font-weight: bold;">'.($stats->total_donations ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Pending Review</div>';
print '<div style="font-size: 48px; font-weight: bold;">'.($stats->pending_donations ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Product Types</div>';
print '<div style="font-size: 48px; font-weight: bold;">'.($stats->unique_products ?? 0).'</div>';
print '</div>';

print '<div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Quantity Supplied</div>';
print '<div style="font-size: 36px; font-weight: bold;">'.number_format($stats->total_quantity ?? 0, 0).'</div>';
print '</div>';

print '</div>';

// Welcome Message
print '<h2>🎉 Welcome to Your Vendor Dashboard!</h2>';
print '<p style="color: #666; font-size: 16px;">Manage your donations and track their distribution to beneficiaries.</p>';

// Quick Actions
print '<div style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">';

$actions = [
    ['icon' => '🎁', 'title' => 'Submit Donation', 'desc' => 'Add new product donation', 'link' => 'create_donation.php'],
    ['icon' => '📦', 'title' => 'My Donations', 'desc' => 'View donation history', 'link' => 'my_donations.php'],
    ['icon' => '📊', 'title' => 'Reports', 'desc' => 'View impact reports', 'link' => 'vendor_reports.php'],
    ['icon' => '👤', 'title' => 'My Profile', 'desc' => 'Update business info', 'link' => 'vendor_profile.php']
];

foreach ($actions as $action) {
    print '<a href="'.$action['link'].'" style="display: block; padding: 25px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#667eea\'; this.style.transform=\'translateY(-3px)\'; this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.1)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'none\'">';
    print '<div style="font-size: 48px; margin-bottom: 15px;">'.$action['icon'].'</div>';
    print '<h3 style="margin: 0 0 8px 0; font-size: 20px;">'.$action['title'].'</h3>';
    print '<p style="margin: 0; color: #666; font-size: 14px;">'.$action['desc'].'</p>';
    print '</a>';
}

print '</div>';

// Recent Donations
print '<h2 style="margin-top: 50px;">📋 Recent Donations</h2>';

$sql_recent = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_donations 
               WHERE fk_vendor = ".(int)$vendor_id."
               ORDER BY date_donation DESC 
               LIMIT 5";
$res_recent = $db->query($sql_recent);

if ($res_recent && $db->num_rows($res_recent) > 0) {
    print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    print '<table style="width: 100%; border-collapse: collapse;">';
    print '<thead style="background: #f8f9fa;"><tr>';
    print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Reference</th>';
    print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Date</th>';
    print '<th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Product</th>';
    print '<th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Quantity</th>';
    print '<th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Status</th>';
    print '<th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Actions</th>';
    print '</tr></thead><tbody>';
    
    while ($donation = $db->fetch_object($res_recent)) {
        $status_color = $donation->status == 'Received' ? '#28a745' : '#ffc107';
        
        print '<tr style="border-bottom: 1px solid #f0f0f0;">';
        print '<td style="padding: 15px;"><strong>'.dol_escape_htmltag($donation->ref).'</strong></td>';
        print '<td style="padding: 15px;">'.dol_print_date($db->jdate($donation->date_donation), 'day').'</td>';
        print '<td style="padding: 15px;">'.dol_escape_htmltag($donation->product_name).'</td>';
        print '<td style="padding: 15px; text-align: center;">'.number_format($donation->quantity, 0).' '.$donation->unit.'</td>';
        print '<td style="padding: 15px; text-align: center;"><span style="background: '.$status_color.'; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">'.dol_escape_htmltag($donation->status).'</span></td>';
        print '<td style="padding: 15px; text-align: center;"><a href="donation_details.php?id='.$donation->rowid.'" class="butAction" style="padding: 6px 12px; font-size: 13px;">View</a></td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
    print '</div>';
    
    print '<div style="text-align: center; margin-top: 20px;">';
    print '<a href="my_donations.php" class="butAction">View All Donations</a>';
    print '</div>';
} else {
    print '<div style="text-align: center; padding: 60px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">🎁</div>';
    print '<h3>No donations yet</h3>';
    print '<p style="color: #666;">Start making a difference by submitting your first donation!</p>';
    print '<br><a href="create_donation.php" class="butAction">Submit First Donation</a>';
    print '</div>';
}

print '</div>';

llxFooter();
?>
