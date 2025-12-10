<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';

$langs->load("admin");
llxHeader('', 'Edit Subscriber');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: beneficiaries.php"); exit;
}

// CSS Style
print '<style>
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    .fb-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #444; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
    
    .status-active { color: #28a745; font-weight: bold; }
    .status-pending { color: #fd7e14; font-weight: bold; }
    .status-inactive { color: #dc3545; font-weight: bold; }
</style>';

$b = new Beneficiary($db);
$b->fetch((int) $_GET['id']);

$notice = '';
$hide_form = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        // Update Object Properties
        $b->firstname = $_POST['firstname'];
        $b->lastname = $_POST['lastname'];
        $b->phone = $_POST['phone'];
        $b->email = $_POST['email'];
        $b->address = $_POST['address'];
        $b->household_size = (int)$_POST['household_size'];
        $b->note = $_POST['note'];
        
        // ADMIN ONLY: Update Subscription Status & Type
        $b->subscription_status = $_POST['subscription_status'];
        $b->subscription_type = $_POST['subscription_type'];
        
        // Use the Class update method
        if ($b->update($user) > 0) {
            // SUCCESS MESSAGE (Same style as Create Page)
            $notice = '<div class="ok" style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724; margin-bottom: 20px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">✅</div>
                        <strong>Subscriber Updated Successfully!</strong><br>
                        Ref: '.$b->ref.'<br><br>
                        <a href="beneficiaries.php" class="button" style="background:#28a745; color:white; border:none; padding:10px 20px;">Return to List</a>
                        <a href="edit_beneficiary.php?id='.$b->id.'" class="button" style="background:#eee; color:#333; margin-left:10px;">Edit Again</a>
                       </div>';
            
            $hide_form = true;
        } else {
            $notice = '<div class="error">Update failed: '.$b->error.'</div>';
        }
    }
}

print '<div class="fb-container">';

// Only show header if form is visible
if (!$hide_form) {
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-top: 20px;">';
    print '<div><h1 style="margin: 0;">✏️ Edit Subscriber</h1><p style="color:#888; margin: 5px 0 0 0;">Update account details for <strong>'.dol_escape_htmltag($b->ref).'</strong></p></div>';
    print '<a href="beneficiaries.php" class="button" style="background:#eee; color:#333;">Cancel</a>';
    print '</div>';
}

print $notice;

if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int)$b->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">Personal Information</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>First Name</label><input type="text" name="firstname" value="'.dol_escape_htmltag($b->firstname).'" required></div>';
    print '<div class="form-group"><label>Last Name</label><input type="text" name="lastname" value="'.dol_escape_htmltag($b->lastname).'" required></div>';
    print '</div>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Email</label><input type="email" name="email" value="'.dol_escape_htmltag($b->email).'"></div>';
    print '<div class="form-group"><label>Phone</label><input type="text" name="phone" value="'.dol_escape_htmltag($b->phone).'"></div>';
    print '</div>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Address</label><textarea name="address" rows="1">'.dol_escape_htmltag($b->address).'</textarea></div>';
    print '<div class="form-group"><label>Household Size</label><input type="number" name="household_size" value="'.(int)$b->household_size.'"></div>';
    print '</div>';

    // ADMIN CONTROLS
    print '<h3 style="margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #667eea;">👑 Admin Controls</h3>';

    print '<div class="form-grid">';

    // Status Dropdown
    print '<div class="form-group"><label>Subscription Status</label>';
    print '<select name="subscription_status" style="font-weight:bold;">';
    $statuses = ['Pending', 'Active', 'Inactive', 'Expired'];
    foreach ($statuses as $s) {
        $selected = ($b->subscription_status == $s) ? 'selected' : '';
        // Add color styles to options
        $style = '';
        if ($s == 'Active') $style = 'color:green; font-weight:bold;';
        if ($s == 'Inactive') $style = 'color:red;';
        print '<option value="'.$s.'" '.$selected.' style="'.$style.'">'.$s.'</option>';
    }
    print '</select></div>';

    // Plan Dropdown (Manual Override)
    print '<div class="form-group"><label>Subscription Plan</label>';
    print '<select name="subscription_type">';
    // Get tiers from DB for the dropdown
    $sql = "SELECT DISTINCT tier_type FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers";
    $res_tiers = $db->query($sql);
    if ($res_tiers) {
        while($t = $db->fetch_object($res_tiers)) {
            $selected = ($b->subscription_type == $t->tier_type) ? 'selected' : '';
            print '<option value="'.$t->tier_type.'" '.$selected.'>'.$t->tier_type.'</option>';
        }
    }
    // Fallback if DB is empty
    if (!$res_tiers || $db->num_rows($res_tiers) == 0) {
         print '<option value="Guest" '.($b->subscription_type == 'Guest' ? 'selected' : '').'>Guest</option>';
         print '<option value="Standard" '.($b->subscription_type == 'Standard' ? 'selected' : '').'>Standard</option>';
    }
    print '</select></div>';

    print '</div>'; // End Admin Grid

    print '<div class="form-group"><label>Internal Admin Note</label><textarea name="note" rows="2">'.dol_escape_htmltag($b->note).'</textarea></div>';

    print '<div style="margin-top: 30px; text-align: center;">';
    print '<button type="submit" class="butAction" style="padding: 12px 40px; font-size: 16px;">Save Changes</button>';
    print '</div>';

    print '</form>';
    print '</div>'; // End Card
}

print '</div>'; // End Container

llxFooter();
?>