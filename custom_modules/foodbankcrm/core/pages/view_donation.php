<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

$donation_id = GETPOST('id', 'int');

if (!$donation_id) {
    header('Location: donations.php');
    exit;
}

llxHeader('', 'Donation Details');

// Get donation details
$sql = "SELECT d.*, 
        v.name as vendor_name, v.contact_email as vendor_email, v.contact_phone as vendor_phone,
        w.name as warehouse_name, w.location as warehouse_location
        FROM ".MAIN_DB_PREFIX."foodbank_donations d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        WHERE d.rowid = ".(int)$donation_id;

$res = $db->query($sql);
$donation = $db->fetch_object($res);

if (!$donation) {
    print '<div class="error">Donation not found.</div>';
    print '<div><a href="donations.php">← Back to Donations</a></div>';
    llxFooter();
    exit;
}

// Check permissions
$user_is_admin = FoodbankPermissions::isAdmin($user);
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

// If vendor, verify they own this donation
if ($user_is_vendor && !$user_is_admin) {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
    $res_check = $db->query($sql);
    $vendor_check = $db->fetch_object($res_check);
    
    if ($vendor_check->rowid != $donation->fk_vendor) {
        accessforbidden('You do not have permission to view this donation.');
    }
}

$back_link = $user_is_admin ? 'donations.php' : 'my_donations.php';
print '<div><a href="'.$back_link.'">← Back to Donations</a></div><br>';

// Status badge
$status_colors = array(
    'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳'),
    'Received' => array('bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '✓'),
    'Rejected' => array('bg' => '#ffebee', 'color' => '#d32f2f', 'icon' => '✗')
);
$colors = $status_colors[$donation->status] ?? array('bg' => '#f5f5f5', 'color' => '#666', 'icon' => '?');

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1>Donation: '.dol_escape_htmltag($donation->ref).'</h1>';
print '<span style="display:inline-block; padding:8px 16px; border-radius:5px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold; font-size:14px; margin-top: 10px;">';
print $colors['icon'].' '.dol_escape_htmltag($donation->status);
print '</span>';
print '</div>';

if ($user_is_admin && $donation->status == 'Pending') {
    print '<div>';
    print '<a class="butAction" href="approve_donation.php?id='.$donation_id.'">✓ Approve</a>';
    print '<a class="butActionDelete" href="reject_donation.php?id='.$donation_id.'">✗ Reject</a>';
    print '</div>';
}

print '</div>';

// Main info grid
print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">';

// Left: Product Details
print '<div>';

print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h2 style="margin-top: 0;">📦 Product Information</h2>';
print '<table class="border centpercent">';
print '<tr><td width="30%"><strong>Product Name:</strong></td><td><span style="font-size: 18px; font-weight: bold;">'.dol_escape_htmltag($donation->product_name).'</span></td></tr>';
print '<tr><td><strong>Category:</strong></td><td>'.dol_escape_htmltag($donation->category).'</td></tr>';
print '<tr><td><strong>Quantity:</strong></td><td><strong style="font-size: 18px; color: #1976d2;">'.$donation->quantity.' '.$donation->unit.'</strong></td></tr>';
print '<tr><td><strong>Unit:</strong></td><td>'.dol_escape_htmltag($donation->unit).'</td></tr>';

if ($donation->unit_price > 0) {
    print '<tr><td><strong>Unit Price:</strong></td><td><strong style="color: #2e7d32;">₦'.number_format($donation->unit_price, 2).'</strong></td></tr>';
    print '<tr><td><strong>Total Value:</strong></td><td><strong style="font-size: 20px; color: #2e7d32;">₦'.number_format($donation->quantity * $donation->unit_price, 2).'</strong></td></tr>';
}

if ($donation->expiry_date) {
    $days_to_expiry = floor((strtotime($donation->expiry_date) - time()) / 86400);
    print '<tr><td><strong>Expiry Date:</strong></td><td>';
    print dol_print_date($db->jdate($donation->expiry_date), 'day');
    if ($days_to_expiry > 0 && $days_to_expiry < 30) {
        print '<br><span style="color: #f57c00; font-weight: bold; font-size: 12px;">⚠ Expires in '.$days_to_expiry.' days</span>';
    } elseif ($days_to_expiry <= 0) {
        print '<br><span style="color: #d32f2f; font-weight: bold; font-size: 12px;">⚠ Expired</span>';
    }
    print '</td></tr>';
}

if ($donation->description) {
    print '<tr><td><strong>Description:</strong></td><td>'.nl2br(dol_escape_htmltag($donation->description)).'</td></tr>';
}

print '</table>';
print '</div>';

// Vendor Information
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
print '<h2 style="margin-top: 0;">👤 Vendor Information</h2>';
print '<table class="border centpercent">';
print '<tr><td width="30%"><strong>Vendor:</strong></td><td>'.dol_escape_htmltag($donation->vendor_name).'</td></tr>';
if ($donation->vendor_phone) {
    print '<tr><td><strong>Phone:</strong></td><td>'.dol_escape_htmltag($donation->vendor_phone).'</td></tr>';
}
if ($donation->vendor_email) {
    print '<tr><td><strong>Email:</strong></td><td>'.dol_escape_htmltag($donation->vendor_email).'</td></tr>';
}
print '</table>';
print '</div>';

print '</div>'; // End left column

// Right: Status & Inventory
print '<div>';

// Status & Dates
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h2 style="margin-top: 0;">📊 Status & Timeline</h2>';
print '<table class="border centpercent">';
print '<tr><td width="40%"><strong>Status:</strong></td><td>';
print '<span style="display:inline-block; padding:6px 12px; border-radius:4px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold;">';
print $colors['icon'].' '.dol_escape_htmltag($donation->status);
print '</span>';
print '</td></tr>';
print '<tr><td><strong>Submitted:</strong></td><td>'.dol_print_date($db->jdate($donation->date_creation), 'dayhour').'</td></tr>';

if ($donation->date_received) {
    print '<tr><td><strong>Received:</strong></td><td>'.dol_print_date($db->jdate($donation->date_received), 'dayhour').'</td></tr>';
}

print '</table>';
print '</div>';

// Warehouse & Inventory
print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
print '<h2 style="margin-top: 0;">🏭 Warehouse & Stock</h2>';
print '<table class="border centpercent">';

if ($donation->warehouse_name) {
    print '<tr><td width="40%"><strong>Warehouse:</strong></td><td>'.dol_escape_htmltag($donation->warehouse_name).'</td></tr>';
    if ($donation->warehouse_location) {
        print '<tr><td><strong>Location:</strong></td><td>'.dol_escape_htmltag($donation->warehouse_location).'</td></tr>';
    }
} else {
    print '<tr><td colspan="2" style="color: #999; text-align: center;">Not assigned to warehouse</td></tr>';
}

if ($donation->status == 'Received') {
    $available = $donation->quantity - $donation->quantity_allocated;
    
    print '<tr><td><strong>Total Quantity:</strong></td><td>'.$donation->quantity.' '.$donation->unit.'</td></tr>';
    print '<tr><td><strong>Allocated:</strong></td><td><span style="color: #f57c00;">'.$donation->quantity_allocated.' '.$donation->unit.'</span></td></tr>';
    print '<tr><td><strong>Available:</strong></td><td><strong style="color: #2e7d32; font-size: 16px;">'.$available.' '.$donation->unit.'</strong></td></tr>';
    
    $usage_percent = ($donation->quantity > 0) ? ($donation->quantity_allocated / $donation->quantity) * 100 : 0;
    print '<tr><td><strong>Usage:</strong></td><td>';
    print '<div style="width: 100%; background: #e0e0e0; height: 10px; border-radius: 5px; overflow: hidden;">';
    print '<div style="width: '.round($usage_percent).'%; background: #4caf50; height: 100%;"></div>';
    print '</div>';
    print '<span style="font-size: 12px; color: #666;">'.round($usage_percent).'% allocated</span>';
    print '</td></tr>';
}

print '</table>';
print '</div>';

// Availability for Purchase
if ($donation->status == 'Received') {
    print '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    print '<h2 style="margin-top: 0;">🛒 Purchase Availability</h2>';
    
    $is_available = $donation->is_available_for_purchase == 1;
    
    print '<div style="text-align: center; padding: 20px;">';
    if ($is_available) {
        print '<div style="font-size: 48px; margin-bottom: 10px;">✅</div>';
        print '<div style="font-weight: bold; color: #2e7d32; font-size: 16px;">Available for Purchase</div>';
        print '<div style="font-size: 13px; color: #666; margin-top: 5px;">Visible in product catalog</div>';
    } else {
        print '<div style="font-size: 48px; margin-bottom: 10px;">🚫</div>';
        print '<div style="font-weight: bold; color: #d32f2f; font-size: 16px;">Not Available for Purchase</div>';
        print '<div style="font-size: 13px; color: #666; margin-top: 5px;">Hidden from product catalog</div>';
    }
    print '</div>';
    
    if ($user_is_admin) {
        print '<div style="text-align: center;">';
        if ($is_available) {
            print '<a class="button" href="toggle_availability.php?id='.$donation_id.'&action=disable">Disable Purchase</a>';
        } else {
            print '<a class="button" href="toggle_availability.php?id='.$donation_id.'&action=enable">Enable Purchase</a>';
        }
        print '</div>';
    }
    
    print '</div>';
}

print '</div>'; // End right column

print '</div>'; // End grid

// Admin Actions
if ($user_is_admin) {
    print '<div style="margin-top: 30px; text-align: center; padding: 20px; background: #f5f5f5; border-radius: 8px;">';
    print '<h3 style="margin-top: 0;">Admin Actions</h3>';
    print '<div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">';
    
    print '<a class="button" href="edit_donation.php?id='.$donation_id.'">✏️ Edit</a>';
    
    if ($donation->status == 'Pending') {
        print '<a class="butAction" href="approve_donation.php?id='.$donation_id.'">✓ Approve & Receive</a>';
        print '<a class="butActionDelete" href="reject_donation.php?id='.$donation_id.'">✗ Reject</a>';
    }
    
    if ($donation->status == 'Received' && !$donation->fk_warehouse) {
        print '<a class="button" href="assign_warehouse.php?id='.$donation_id.'">🏭 Assign Warehouse</a>';
    }
    
    print '<a class="button" href="delete_donation.php?id='.$donation_id.'" style="color: #d32f2f;">🗑️ Delete</a>';
    
    print '</div>';
    print '</div>';
}

llxFooter();
?>