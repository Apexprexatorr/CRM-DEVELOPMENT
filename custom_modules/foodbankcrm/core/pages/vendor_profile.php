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

llxHeader('', 'My Profile');

// 1. PORTAL MODE CSS (Hides Left Menu)
echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
body { background: #f8f9fa !important; }
.login_block { width: 100% !important; }
</style>';

$notice = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        $name = GETPOST('name', 'alpha');
        $category = GETPOST('category', 'alpha');
        $contact_person = GETPOST('contact_person', 'alpha');
        $contact_email = GETPOST('contact_email', 'email');
        $contact_phone = GETPOST('contact_phone', 'alpha');
        $address = GETPOST('address', 'restricthtml');
        $description = GETPOST('description', 'restricthtml');
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_vendors SET 
                name = '".$db->escape($name)."',
                category = '".$db->escape($category)."',
                contact_person = '".$db->escape($contact_person)."',
                contact_email = '".$db->escape($contact_email)."',
                contact_phone = '".$db->escape($contact_phone)."',
                address = '".$db->escape($address)."',
                description = '".$db->escape($description)."'
                WHERE rowid = ".(int)$vendor_id;
        
        if ($db->query($sql)) {
            $notice = '<div class="ok" style="margin-bottom: 20px; padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;">✓ Profile updated successfully!</div>';
            // Refresh data
            $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE rowid = ".(int)$vendor_id);
            $vendor = $db->fetch_object($res);
        } else {
            $notice = '<div class="error">Error updating profile: '.$db->lasterror().'</div>';
        }
    }
}

// 2. MAIN CONTAINER (Centers the layout)
print '<div style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1200px; margin: 0 auto;">';

// Header with Back Button
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1 style="margin: 0;">👤 My Business Profile</h1>';
print '<p style="color: #666; margin: 5px 0 0 0;">Manage your business information</p>';
print '</div>';
print '<a href="dashboard_vendor.php" class="butAction">← Back to Dashboard</a>';
print '</div>';

print $notice;

print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">';

// Left: Edit Form
print '<div>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px;">Business Information</h2>';

print '<table class="border centpercent" style="border: none;">';

print '<tr>';
print '<td width="30%" style="padding: 12px 0;"><span class="fieldrequired">Business Name</span></td>';
print '<td style="padding: 12px 0;"><input class="flat" type="text" name="name" value="'.dol_escape_htmltag($vendor->name).'" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;"></td>';
print '</tr>';

print '<tr>';
print '<td style="padding: 12px 0;">Category</td>';
print '<td style="padding: 12px 0;">';
print '<select class="flat" name="category" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;">';
print '<option value="">-- Select Category --</option>';
$categories = array('Grains', 'Vegetables', 'Proteins', 'Dairy', 'Beverages', 'Packaged Foods', 'Other');
foreach ($categories as $cat) {
    $selected = ($vendor->category == $cat) ? 'selected' : '';
    print '<option value="'.$cat.'" '.$selected.'>'.$cat.'</option>';
}
print '</select>';
print '</td>';
print '</tr>';

print '<tr>';
print '<td style="padding: 12px 0;">Contact Person</td>';
print '<td style="padding: 12px 0;"><input class="flat" type="text" name="contact_person" value="'.dol_escape_htmltag($vendor->contact_person).'" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;"></td>';
print '</tr>';

print '<tr>';
print '<td style="padding: 12px 0;">Contact Email</td>';
print '<td style="padding: 12px 0;"><input class="flat" type="email" name="contact_email" value="'.dol_escape_htmltag($vendor->contact_email).'" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;"></td>';
print '</tr>';

print '<tr>';
print '<td style="padding: 12px 0;">Contact Phone</td>';
print '<td style="padding: 12px 0;"><input class="flat" type="text" name="contact_phone" value="'.dol_escape_htmltag($vendor->contact_phone).'" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;"></td>';
print '</tr>';

print '<tr>';
print '<td style="padding: 12px 0;">Address</td>';
print '<td style="padding: 12px 0;"><textarea class="flat" name="address" rows="4" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;">'.dol_escape_htmltag($vendor->address).'</textarea></td>';
print '</tr>';

print '<tr>';
print '<td style="padding: 12px 0;">Description</td>';
print '<td style="padding: 12px 0;"><textarea class="flat" name="description" rows="5" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd;" placeholder="Describe your business, products, and services...">'.dol_escape_htmltag($vendor->description).'</textarea></td>';
print '</tr>';

print '</table>';

print '<div style="text-align: center; margin-top: 20px;">';
print '<button type="submit" class="butAction" style="padding: 12px 30px; font-size: 16px;">💾 Save Changes</button>';
print '</div>';

print '</div>';

print '</form>';

print '</div>'; // End left

// Right: Info Cards
print '<div>';

// Account Info
print '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<h3 style="margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 10px;">📋 Account Information</h3>';
print '<table style="width: 100%; color: white;">';
print '<tr><td style="padding: 5px 0;"><strong>Vendor ID:</strong></td><td style="text-align: right;">'.dol_escape_htmltag($vendor->ref).'</td></tr>';
print '<tr><td style="padding: 5px 0;"><strong>Member Since:</strong></td><td style="text-align: right;">'.dol_print_date($db->jdate($vendor->date_creation), 'day').'</td></tr>';
print '<tr><td style="padding: 5px 0;"><strong>Username:</strong></td><td style="text-align: right;">'.dol_escape_htmltag($user->login).'</td></tr>';
print '</table>';
print '</div>';

// Performance Stats
$sql_stats = "SELECT 
    COUNT(*) as total_donations,
    SUM(CASE WHEN status = 'Received' THEN quantity ELSE 0 END) as received_qty,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count
    FROM ".MAIN_DB_PREFIX."foodbank_donations
    WHERE fk_vendor = ".(int)$vendor_id;

$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">📊 Performance</h3>';
print '<table style="width: 100%;">';
print '<tr><td style="padding: 8px 0;"><strong>Total Donations:</strong></td><td style="text-align: right;"><strong style="font-size: 18px; color: #1976d2;">'.($stats->total_donations ?? 0).'</strong></td></tr>';
print '<tr><td style="padding: 8px 0;"><strong>Quantity Supplied:</strong></td><td style="text-align: right;"><strong style="font-size: 16px; color: #2e7d32;">'.number_format($stats->received_qty ?? 0, 0).'</strong></td></tr>';
print '<tr><td style="padding: 8px 0;"><strong>Pending Review:</strong></td><td style="text-align: right;"><strong style="font-size: 16px; color: #f57c00;">'.($stats->pending_count ?? 0).'</strong></td></tr>';
print '</table>';
print '</div>';

// Password Change
// Password Change
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🔒 Change Password</h3>';
print '<p style="color: #666; font-size: 13px; margin-bottom: 15px;">Update your login security credentials.</p>';
print '<div style="text-align: center;">';
// UPDATED LINK: Points to your new custom page
print '<a class="butAction" href="vendor_password.php">Change Password</a>';
print '</div>';
print '</div>';

print '</div>'; // End right

print '</div>'; // End grid
print '</div>'; // End main container

llxFooter();
?>