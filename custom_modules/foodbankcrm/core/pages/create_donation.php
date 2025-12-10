<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php'; 

$langs->load("admin");

// --- 1. SECURITY & ROLE CHECK ---
$is_admin = FoodbankPermissions::isAdmin($user);
$is_vendor = FoodbankPermissions::isVendor($user, $db);

// If neither, kick them out
if (!$is_admin && !$is_vendor) {
    accessforbidden('You do not have access to this page.');
}

// If Vendor, get their ID
$current_vendor_id = 0;
$current_vendor_name = '';
if ($is_vendor) {
    $sql = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $obj = $db->fetch_object($res)) {
        $current_vendor_id = $obj->rowid;
        $current_vendor_name = $obj->name;
    }
}

// --- 2. HEADER (Different for Admin vs Vendor) ---
if ($is_vendor) {
    llxHeader('', 'Submit Donation');
    // Hide Sidebar for Vendors (Portal Mode)
    print '<style>
        #id-left { display: none !important; }
        #id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        body { background: #f8f9fa !important; }
    </style>';
} else {
    llxHeader('', 'Create Donation');
    // Hide Top Bar for Admins (Clean Admin Mode)
    print '<style>
        #id-top { display: none !important; }
        .side-nav { top: 0 !important; height: 100vh !important; }
        #id-right { padding-top: 30px !important; }
        #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
        #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
        #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank {
            display: none !important;
        }
    </style>';
}

// --- 3. SHARED CSS ---
print '<style>
    .fb-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 40px; border: 1px solid #eee; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #444; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
</style>';

$notice = '';
$hide_form = false;

// --- 4. HANDLE SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed.</div>';
    } else {
        $d = new DonationFB($db);
        $d->ref = $_POST['ref'];
        $d->product_name = $_POST['product_name'];
        $d->category = $_POST['category'];
        $d->quantity = (int)$_POST['quantity'];
        $d->unit = $_POST['unit'];
        $d->fk_warehouse = (int)$_POST['fk_warehouse'];
        $d->note = $_POST['note'];
        
        // --- SECURE LOGIC ---
        if ($is_admin) {
            // Admin can choose any vendor and any status
            $d->fk_vendor = (int)$_POST['fk_vendor']; 
            $d->status = $_POST['status']; 
        } else {
            // Vendor is LOCKED to themselves and PENDING status
            $d->fk_vendor = $current_vendor_id; 
            $d->status = 'Pending'; 
        }
        
        $d->date_donation = dol_now();
        
        if ($d->create($user) > 0) {
            $dashboard_link = $is_admin ? "donations.php" : "dashboard_vendor.php";
            $btn_text = $is_admin ? "View Donations" : "Back to Dashboard";
            
            $notice = '<div class="ok" style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724; margin-bottom: 20px; text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 10px;">🎁</div>
                        <strong>Donation Submitted Successfully!</strong><br>
                        Ref: '.$d->ref.'<br><br>
                        <a href="'.$dashboard_link.'" class="button" style="background:#28a745; color:white;">'.$btn_text.'</a>
                        <a href="create_donation.php" class="button" style="background:#eee; color:#333; margin-left:10px;">Submit Another</a>
                       </div>';
            $hide_form = true;
        } else {
            $notice = '<div class="error">Error: '.$d->error.'</div>';
        }
    }
}

// Fetch Dropdowns
$vendors = [];
// SECURITY: Only fetch list of vendors if user is ADMIN
if ($is_admin) {
    $res = $db->query("SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY name");
    while($o = $db->fetch_object($res)) $vendors[] = $o;
}

$warehouses = [];
$res = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY label");
while($o = $db->fetch_object($res)) $warehouses[] = $o;

// --- 5. DISPLAY PAGE ---
print '<div class="fb-container">';

if (!$hide_form) {
    $back_link = $is_admin ? "donations.php" : "dashboard_vendor.php";
    print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-top: 20px;">';
    print '<div><h1 style="margin: 0;">🎁 New Donation Record</h1><p style="color:#888; margin: 5px 0 0 0;">Record incoming inventory</p></div>';
    print '<div><a href="'.$back_link.'" class="button" style="background:#eee; color:#333;">Cancel</a></div>';
    print '</div>';
}

print $notice;

if (!$hide_form) {
    print '<div class="fb-card">';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';

    print '<h3 style="margin: 0 0 25px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">📦 Product Details</h3>';

    print '<div class="form-grid">';
    print '<div class="form-group"><label>Product Name</label><input type="text" name="product_name" required placeholder="e.g. White Rice 50kg"></div>';
    print '<div class="form-group"><label>Category</label>';
    print '<select name="category">';
    print '<option value="">-- Select Category --</option>';
    $cats = ['Grains','Vegetables','Proteins','Dairy','Beverages','Packaged Foods','Other'];
    foreach($cats as $c) { print '<option value="'.$c.'">'.$c.'</option>'; }
    print '</select></div>';
    print '</div>';

    print '<div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">';
    print '<div class="form-group"><label>Quantity</label><input type="number" name="quantity" required min="1" value="1"></div>';
    print '<div class="form-group"><label>Unit</label><select name="unit"><option value="kg">kg</option><option value="bags">bags</option><option value="cartons">cartons</option><option value="liters">liters</option><option value="units">units</option></select></div>';
    
    // --- STATUS FIELD (Admin Only) ---
    if ($is_admin) {
        print '<div class="form-group"><label>Initial Status</label><select name="status" style="font-weight:bold;">';
        print '<option value="Received" selected style="color:green;">✅ Received (In Stock)</option>';
        print '<option value="Pending" style="color:orange;">⏳ Pending (Expected)</option>';
        print '</select></div>';
    } else {
        // Vendors don't see this, strictly Pending
        print '<input type="hidden" name="status" value="Pending">';
    }
    print '</div>';

    print '<h3 style="margin: 30px 0 25px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🏢 Source & Storage</h3>';

    print '<div class="form-grid">';
    
    // --- VENDOR FIELD (Smart Logic) ---
    print '<div class="form-group"><label>Source Vendor</label>';
    if ($is_admin) {
        // Admin sees dropdown
        print '<select name="fk_vendor" required>';
        print '<option value="">-- Select Vendor --</option>';
        foreach($vendors as $v) { print '<option value="'.$v->rowid.'">'.dol_escape_htmltag($v->name).'</option>'; }
        print '</select>';
    } else {
        // Vendor sees ONLY their name (Read Only)
        print '<input type="text" value="'.dol_escape_htmltag($current_vendor_name).'" disabled style="background:#f9f9f9; color:#666; font-weight:bold;">';
        print '<input type="hidden" name="fk_vendor" value="'.$current_vendor_id.'">';
    }
    print '</div>';

    print '<div class="form-group"><label>Target Warehouse</label><select name="fk_warehouse">';
    print '<option value="">-- Select Warehouse --</option>';
    foreach($warehouses as $w) { print '<option value="'.$w->rowid.'">'.dol_escape_htmltag($w->label).'</option>'; }
    print '</select></div>';
    print '</div>';

    print '<div class="form-group"><label>Notes</label><textarea name="note" rows="3" placeholder="Condition, expiry date, etc..."></textarea></div>';

    print '<div class="form-group"><label>Reference ID (Optional)</label><input type="text" name="ref" placeholder="Auto-generated if empty"></div>';

    print '<div style="margin-top: 30px; text-align: center;">';
    print '<button type="submit" class="butAction" style="padding: 12px 40px; font-size: 16px;">Record Donation</button>';
    print '</div>';

    print '</form>';
    print '</div>';
}

print '</div>';
llxFooter();
?>