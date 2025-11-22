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

llxHeader('', 'My Profile');

$notice = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        $firstname = GETPOST('firstname', 'alpha');
        $lastname = GETPOST('lastname', 'alpha');
        $email = GETPOST('email', 'email');
        $phone = GETPOST('phone', 'alpha');
        $address = GETPOST('address', 'restricthtml');
        $household_size = GETPOST('household_size', 'int');
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET 
                firstname = '".$db->escape($firstname)."',
                lastname = '".$db->escape($lastname)."',
                email = '".$db->escape($email)."',
                phone = '".$db->escape($phone)."',
                address = '".$db->escape($address)."',
                household_size = ".(int)$household_size."
                WHERE rowid = ".(int)$subscriber_id;
        
        if ($db->query($sql)) {
            $notice = '<div class="ok">✓ Profile updated successfully!</div>';
            // Refresh data
            $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid = ".(int)$subscriber_id);
            $subscriber = $db->fetch_object($res);
        } else {
            $notice = '<div class="error">Error updating profile: '.$db->lasterror().'</div>';
        }
    }
}

print $notice;

print '<div><a href="dashboard_beneficiary.php">← Back to Dashboard</a></div><br>';

print '<h1>👤 My Profile</h1>';

print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">';

// Left: Edit Form
print '<div>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin-top: 0;">Personal Information</h2>';

print '<table class="border centpercent">';

print '<tr>';
print '<td width="30%"><span class="fieldrequired">First Name</span></td>';
print '<td><input class="flat" type="text" name="firstname" value="'.dol_escape_htmltag($subscriber->firstname).'" required style="width: 100%;"></td>';
print '</tr>';

print '<tr>';
print '<td><span class="fieldrequired">Last Name</span></td>';
print '<td><input class="flat" type="text" name="lastname" value="'.dol_escape_htmltag($subscriber->lastname).'" required style="width: 100%;"></td>';
print '</tr>';

print '<tr>';
print '<td>Email</td>';
print '<td><input class="flat" type="email" name="email" value="'.dol_escape_htmltag($subscriber->email).'" style="width: 100%;"></td>';
print '</tr>';

print '<tr>';
print '<td>Phone</td>';
print '<td><input class="flat" type="text" name="phone" value="'.dol_escape_htmltag($subscriber->phone).'" style="width: 100%;"></td>';
print '</tr>';

print '<tr>';
print '<td>Address</td>';
print '<td><textarea class="flat" name="address" rows="4" style="width: 100%;">'.dol_escape_htmltag($subscriber->address).'</textarea></td>';
print '</tr>';

print '<tr>';
print '<td>Household Size</td>';
print '<td><input class="flat" type="number" name="household_size" value="'.$subscriber->household_size.'" min="1" style="width: 150px;"></td>';
print '</tr>';

print '</table>';

print '<div style="text-align: center; margin-top: 20px;">';
print '<button type="submit" class="button">💾 Save Changes</button>';
print '</div>';

print '</div>';

print '</form>';

print '</div>'; // End left

// Right: Info Cards
print '<div>';

// Account Info
print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; margin-bottom: 20px;">';
print '<h3 style="margin-top: 0;">📋 Account Information</h3>';
print '<table style="width: 100%; color: white;">';
print '<tr><td><strong>Subscriber ID:</strong></td><td>'.dol_escape_htmltag($subscriber->ref).'</td></tr>';
print '<tr><td><strong>Member Since:</strong></td><td>'.dol_print_date($db->jdate($subscriber->date_creation), 'day').'</td></tr>';
print '<tr><td><strong>Username:</strong></td><td>'.dol_escape_htmltag($user->login).'</td></tr>';
print '</table>';
print '</div>';

// Subscription Info
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h3 style="margin-top: 0;">💳 Subscription</h3>';
print '<table style="width: 100%;">';
print '<tr><td><strong>Plan:</strong></td><td>'.dol_escape_htmltag($subscriber->subscription_type ?: 'None').'</td></tr>';
print '<tr><td><strong>Status:</strong></td><td>';

$status = $subscriber->subscription_status;
if ($subscriber->subscription_end_date && strtotime($subscriber->subscription_end_date) < time()) {
    $status = 'Expired';
}

$status_colors = array(
    'Active' => array('bg' => '#e8f5e9', 'color' => '#2e7d32'),
    'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00'),
    'Expired' => array('bg' => '#ffebee', 'color' => '#d32f2f')
);
$colors = $status_colors[$status] ?? array('bg' => '#f5f5f5', 'color' => '#666');

print '<span style="display:inline-block; padding:4px 10px; border-radius:4px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold; font-size:11px;">';
print $status;
print '</span>';
print '</td></tr>';

if ($subscriber->subscription_end_date) {
    print '<tr><td><strong>Valid Until:</strong></td><td>'.dol_print_date($db->jdate($subscriber->subscription_end_date), 'day').'</td></tr>';
}

if ($subscriber->subscription_fee) {
    print '<tr><td><strong>Fee:</strong></td><td><strong>₦'.number_format($subscriber->subscription_fee, 0).'</strong></td></tr>';
}

print '</table>';
print '</div>';

// Password Change
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h3 style="margin-top: 0;">🔒 Change Password</h3>';
print '<p style="color: #666; font-size: 13px;">To change your password, please contact the administrator or use the Dolibarr account settings.</p>';
print '<a class="button" href="/user/card.php?id='.$user->id.'">Go to Account Settings</a>';
print '</div>';

print '</div>'; // End right

print '</div>'; // End grid

llxFooter();
?>