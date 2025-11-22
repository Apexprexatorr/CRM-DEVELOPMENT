<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a vendor
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);

if (!$user_is_vendor) {
    accessforbidden('You do not have access to the vendor dashboard.');
}

// Get vendor information
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

// Header
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1>👋 Welcome, '.dol_escape_htmltag($vendor->name).'!</h1>';
print '<p style="color: #666; margin: 5px 0;">Vendor ID: '.dol_escape_htmltag($vendor->ref).'</p>';
print '</div>';
print '<a class="butAction" href="create_donation.php">+ Submit Donation</a>';
print '</div>';

// Vendor Profile Card
print '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';

// Vendor Name
print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Business Name</div>';
print '<div style="font-size: 24px; font-weight: bold;">'.dol_escape_htmltag($vendor->name).'</div>';
print '</div>';

// Category
print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Category</div>';
print '<div style="font-size: 20px; font-weight: bold;">'.dol_escape_htmltag($vendor->category ?: 'General').'</div>';
print '</div>';

// Contact
print '<div>';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Contact</div>';
print '<div style="font-size: 16px;">'.dol_escape_htmltag($vendor->contact_phone).'</div>';
if ($vendor->contact_email) {
    print '<div style="font-size: 13px; opacity: 0.9;">'.dol_escape_htmltag($vendor->contact_email).'</div>';
}
print '</div>';

// Address
if ($vendor->address) {
    print '<div>';
    print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Address</div>';
    print '<div style="font-size: 14px;">'.nl2br(dol_escape_htmltag($vendor->address)).'</div>';
    print '</div>';
}

print '</div>';
print '</div>';

// Quick Stats
$sql_stats = "SELECT 
    COUNT(DISTINCT d.rowid) as total_donations,
    SUM(d.quantity) as total_quantity,
    SUM(CASE WHEN d.status = 'Received' THEN d.quantity ELSE 0 END) as received_quantity,
    SUM(CASE WHEN d.status = 'Pending' THEN 1 ELSE 0 END) as pending_donations,
    COUNT(DISTINCT d.product_name) as unique_products
    FROM ".MAIN_DB_PREFIX."foodbank_donations d
    WHERE d.fk_vendor = ".(int)$vendor_id;

$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

print '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">';

// Total Donations
print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Donations</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->total_donations ?? 0).'</div>';
print '</div>';

// Pending
print '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Pending Review</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->pending_donations ?? 0).'</div>';
print '</div>';

// Unique Products
print '<div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Product Types</div>';
print '<div style="font-size: 40px; font-weight: bold;">'.($stats->unique_products ?? 0).'</div>';
print '</div>';

// Total Quantity
print '<div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
print '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Total Quantity Supplied</div>';
print '<div style="font-size: 32px; font-weight: bold;">'.number_format($stats->total_quantity ?? 0, 0).'</div>';
print '</div>';

print '</div>';

// Recent Donations
$sql_donations = "SELECT d.*, w.name as warehouse_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        WHERE d.fk_vendor = ".(int)$vendor_id."
        ORDER BY d.date_creation DESC
        LIMIT 10";

$res_donations = $db->query($sql_donations);

if ($res_donations && $db->num_rows($res_donations) > 0) {
    print '<h2>📦 Recent Donations</h2>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Ref</th>';
    print '<th>Product</th>';
    print '<th>Category</th>';
    print '<th class="center">Quantity</th>';
    print '<th>Warehouse</th>';
    print '<th>Date</th>';
    print '<th>Status</th>';
    print '<th class="center">Actions</th>';
    print '</tr>';
    
    while ($donation = $db->fetch_object($res_donations)) {
        // Status colors
        $status_colors = array(
            'Pending' => array('bg' => '#fff3e0', 'color' => '#f57c00', 'icon' => '⏳'),
            'Received' => array('bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => '✓'),
            'Rejected' => array('bg' => '#ffebee', 'color' => '#d32f2f', 'icon' => '✗')
        );
        $colors = $status_colors[$donation->status] ?? array('bg' => '#f5f5f5', 'color' => '#666', 'icon' => '?');
        
        print '<tr class="oddeven">';
        print '<td><strong>'.dol_escape_htmltag($donation->ref).'</strong></td>';
        print '<td>'.dol_escape_htmltag($donation->product_name).'</td>';
        print '<td>'.dol_escape_htmltag($donation->category).'</td>';
        print '<td class="center"><strong>'.$donation->quantity.' '.$donation->unit.'</strong></td>';
        print '<td>'.dol_escape_htmltag($donation->warehouse_name ?: 'Not assigned').'</td>';
        print '<td>'.dol_print_date($db->jdate($donation->date_creation), 'day').'</td>';
        print '<td>';
        print '<span style="display:inline-block; padding:4px 10px; border-radius:4px; background:'.$colors['bg'].'; color:'.$colors['color'].'; font-weight:bold; font-size:11px;">';
        print $colors['icon'].' '.dol_escape_htmltag($donation->status);
        print '</span>';
        print '</td>';
        print '<td class="center">';
        print '<a href="view_donation.php?id='.$donation->rowid.'">View</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '</table>';
} else {
    print '<div style="text-align: center; padding: 60px; background: #f9f9f9; border-radius: 8px; margin-top: 30px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">📦</div>';
    print '<h2>No Donations Yet</h2>';
    print '<p style="color: #666;">Submit your first donation to get started!</p>';
    print '<br><a class="butAction" href="create_donation.php">+ Submit Donation</a>';
    print '</div>';
}

// Products Catalog (what this vendor supplies)
$sql_products = "SELECT DISTINCT product_name, category, unit, 
        SUM(quantity) as total_supplied,
        SUM(CASE WHEN status = 'Received' THEN quantity ELSE 0 END) as received_qty
        FROM ".MAIN_DB_PREFIX."foodbank_donations
        WHERE fk_vendor = ".(int)$vendor_id."
        GROUP BY product_name, category, unit
        ORDER BY total_supplied DESC";

$res_products = $db->query($sql_products);

if ($res_products && $db->num_rows($res_products) > 0) {
    print '<h2 style="margin-top: 40px;">📋 Your Product Catalog</h2>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Product Name</th>';
    print '<th>Category</th>';
    print '<th class="center">Total Supplied</th>';
    print '<th class="center">Successfully Received</th>';
    print '</tr>';
    
    while ($product = $db->fetch_object($res_products)) {
        print '<tr class="oddeven">';
        print '<td><strong>'.dol_escape_htmltag($product->product_name).'</strong></td>';
        print '<td>'.dol_escape_htmltag($product->category).'</td>';
        print '<td class="center">'.number_format($product->total_supplied, 1).' '.$product->unit.'</td>';
        print '<td class="center"><strong style="color: #2e7d32;">'.number_format($product->received_qty, 1).' '.$product->unit.'</strong></td>';
        print '</tr>';
    }
    
    print '</table>';
}

// Quick Links
print '<div style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">';

print '<a href="create_donation.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'; this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'">';
print '<div style="font-size: 40px; margin-bottom: 10px;">➕</div>';
print '<h3 style="margin: 0 0 5px 0;">Submit Donation</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Add a new product donation</p>';
print '</a>';

print '<a href="my_donations.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'; this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'">';
print '<div style="font-size: 40px; margin-bottom: 10px;">📦</div>';
print '<h3 style="margin: 0 0 5px 0;">My Donations</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">View all your donation history</p>';
print '</a>';

print '<a href="vendor_products.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'; this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'">';
print '<div style="font-size: 40px; margin-bottom: 10px;">📋</div>';
print '<h3 style="margin: 0 0 5px 0;">Product Catalog</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Manage your product offerings</p>';
print '</a>';

print '<a href="vendor_profile.php" style="display: block; padding: 20px; background: white; border: 2px solid #e0e0e0; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'; this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.transform=\'translateY(0)\'">';
print '<div style="font-size: 40px; margin-bottom: 10px;">👤</div>';
print '<h3 style="margin: 0 0 5px 0;">My Profile</h3>';
print '<p style="margin: 0; color: #666; font-size: 13px;">Update your business information</p>';
print '</a>';

print '</div>';

llxFooter();
?>