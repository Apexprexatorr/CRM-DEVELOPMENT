<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';

$langs->load("admin");
llxHeader();

// Handle status update from inline form
if (isset($_POST['update_status'])) {
    if (isset($_POST['token']) && $_POST['token'] == $_SESSION['newtoken']) {
        $id = (int)$_POST['id'];
        $status = $_POST['status'];
        
        $donation = new DonationFB($db);
        if ($donation->fetch($id) > 0) {
            $donation->status = $status;
            
            if ($donation->update($user) > 0) {
                setEventMessages('Status updated successfully!', null, 'mesgs');
            } else {
                setEventMessages('Error updating status: '.$donation->error, null, 'errors');
            }
        }
    }
}

print '<div class="fichecenter">';
print '<a class="butAction" href="create_donation.php">+ Create Donation</a>';
print '</div><br>';

// Add filters
$filter_status = GETPOST('filter_status', 'alpha');
$filter_vendor = GETPOST('filter_vendor', 'int');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
print '<table class="noborder" style="width: auto;">';
print '<tr>';
print '<td><strong>Filter by Status:</strong> ';
print '<select name="filter_status" class="flat">';
print '<option value="">All Statuses</option>';
print '<option value="Pending"'.($filter_status == 'Pending' ? ' selected' : '').'>⏳ Pending</option>';
print '<option value="Received"'.($filter_status == 'Received' ? ' selected' : '').'>✅ Received</option>';
print '<option value="Allocated"'.($filter_status == 'Allocated' ? ' selected' : '').'>📦 Allocated</option>';
print '</select>';
print '</td>';
print '<td style="padding-left: 15px;"><strong>Filter by Vendor:</strong> ';
print '<select name="filter_vendor" class="flat">';
print '<option value="">All Vendors</option>';
$sql_vendors = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY name";
$resql_vendors = $db->query($sql_vendors);
while ($obj = $db->fetch_object($resql_vendors)) {
    print '<option value="'.$obj->rowid.'"'.($filter_vendor == $obj->rowid ? ' selected' : '').'>'.dol_escape_htmltag($obj->name).'</option>';
}
print '</select>';
print '</td>';
print '<td style="padding-left: 10px;"><input type="submit" class="button small" value="Apply Filter"> ';
if ($filter_status || $filter_vendor) {
    print '<a href="'.$_SERVER['PHP_SELF'].'" class="button small">Clear</a>';
}
print '</td>';
print '</tr>';
print '</table>';
print '</form>';

// Build query with filters
$sql = "SELECT d.rowid, d.ref, d.label, d.quantity, d.quantity_allocated, d.unit, 
               d.date_donation, d.status,
               (d.quantity - d.quantity_allocated) as available,
               v.name AS vendor_name,
               CONCAT(b.firstname, ' ', b.lastname) AS beneficiary_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations AS d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors AS v ON v.rowid = d.fk_vendor
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries AS b ON b.rowid = d.fk_beneficiary
        WHERE 1=1";

if ($filter_status) {
    $sql .= " AND d.status = '".$db->escape($filter_status)."'";
}
if ($filter_vendor) {
    $sql .= " AND d.fk_vendor = ".(int)$filter_vendor;
}

$sql .= " ORDER BY d.date_donation DESC";

$res = $db->query($sql);
if (!$res) {
    print '<div class="error">SQL error: '.dol_escape_htmltag($db->lasterror()).'</div>';
    llxFooter(); exit;
}

// Calculate summary statistics BY UNIT
$total_donations = 0;
$stats_by_unit = array();
$donations_data = array();

while ($o = $db->fetch_object($res)) {
    $donations_data[] = $o;
    $total_donations++;
    
    $unit = $o->unit ?: 'unknown';
    
    if (!isset($stats_by_unit[$unit])) {
        $stats_by_unit[$unit] = array(
            'total_quantity' => 0,
            'total_allocated' => 0,
            'total_available' => 0
        );
    }
    
    $stats_by_unit[$unit]['total_quantity'] += $o->quantity;
    $stats_by_unit[$unit]['total_allocated'] += $o->quantity_allocated;
    $stats_by_unit[$unit]['total_available'] += $o->available;
}

// Calculate overall allocation percentage
$total_qty_all = 0;
$total_allocated_all = 0;
foreach ($stats_by_unit as $unit_stats) {
    $total_qty_all += $unit_stats['total_quantity'];
    $total_allocated_all += $unit_stats['total_allocated'];
}
$allocation_percentage = $total_qty_all > 0 ? round(($total_allocated_all / $total_qty_all) * 100, 1) : 0;

// Summary section
print '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ddd;">';

// Top stats boxes
print '<div style="display: flex; gap: 15px; margin-bottom: 15px;">';

print '<div style="flex: 1; background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid #2196f3;">';
print '<div style="font-size: 24px; font-weight: bold; color: #1976d2;">'.$total_donations.'</div>';
print '<div style="color: #666; font-size: 13px;">Total Donations</div>';
print '</div>';

print '<div style="flex: 1; background: #f3e5f5; padding: 15px; border-radius: 5px; border-left: 4px solid #9c27b0;">';
print '<div style="font-size: 24px; font-weight: bold; color: #7b1fa2;">'.$allocation_percentage.'%</div>';
print '<div style="color: #666; font-size: 13px;">Overall Allocation Rate</div>';
print '</div>';

print '<div style="flex: 1; background: #fff3e0; padding: 15px; border-radius: 5px; border-left: 4px solid #ff9800;">';
print '<div style="font-size: 24px; font-weight: bold; color: #f57c00;">'.count($stats_by_unit).'</div>';
print '<div style="color: #666; font-size: 13px;">Different Units</div>';
print '</div>';

print '</div>';

// Stock breakdown by unit
if (count($stats_by_unit) > 0) {
    print '<h4 style="margin: 15px 0 10px 0;">📦 Available Stock by Unit</h4>';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Unit</th>';
    print '<th>Total Donated</th>';
    print '<th>Allocated</th>';
    print '<th>Available</th>';
    print '<th class="center">Allocation %</th>';
    print '</tr>';

    foreach ($stats_by_unit as $unit => $stats) {
        $unit_pct = $stats['total_quantity'] > 0 ? round(($stats['total_allocated'] / $stats['total_quantity']) * 100, 1) : 0;
        $available_color = $stats['total_available'] > 0 ? '#4caf50' : '#f44336';
        
        print '<tr class="oddeven">';
        print '<td><strong>'.dol_escape_htmltag($unit).'</strong></td>';
        print '<td>'.number_format($stats['total_quantity'], 2).'</td>';
        print '<td><span style="color: #ff9800;">'.number_format($stats['total_allocated'], 2).'</span></td>';
        print '<td><strong style="color:'.$available_color.'; font-size: 16px;">'.number_format($stats['total_available'], 2).'</strong></td>';
        print '<td class="center">';
        
        // Progress bar
        print '<div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden; position: relative;">';
        print '<div style="background: #ff9800; height: 100%; width: '.$unit_pct.'%;"></div>';
        print '<div style="position: absolute; top: 0; left: 0; right: 0; text-align: center; line-height: 20px; font-size: 11px; font-weight: bold; color: #333;">'.$unit_pct.'%</div>';
        print '</div>';
        
        print '</td>';
        print '</tr>';
    }

    print '</table>';
}

print '</div>';

// Donations table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Ref</th>';
print '<th>Product</th>';
print '<th>Total Qty</th>';
print '<th>Allocated</th>';
print '<th>Available</th>';
print '<th>Unit</th>';
print '<th>Vendor</th>';
print '<th>Date</th>';
print '<th>Status</th>';
print '<th class="center">Actions</th>';
print '</tr>';

if (count($donations_data) == 0) {
    print '<tr><td colspan="10" class="center" style="padding: 30px; color: #999;">';
    if ($filter_status || $filter_vendor) {
        print 'No donations found matching your filters.';
    } else {
        print 'No donations found. <a href="create_donation.php">Create your first donation!</a>';
    }
    print '</td></tr>';
}

foreach ($donations_data as $o) {
    $vendor = $o->vendor_name !== null ? dol_escape_htmltag($o->vendor_name) : '<span style="color:#999;">No vendor</span>';
    $date = $o->date_donation ? dol_print_date($db->jdate($o->date_donation), 'day') : '—';
    
    // Status styling
    $status_color = '#999';
    $status_bg = '#f0f0f0';
    $status_icon = '❓';
    
    if ($o->status == 'Pending') {
        $status_color = '#ff9800';
        $status_bg = '#fff3e0';
        $status_icon = '⏳';
    } elseif ($o->status == 'Received') {
        $status_color = '#4caf50';
        $status_bg = '#e8f5e9';
        $status_icon = '✅';
    } elseif ($o->status == 'Allocated') {
        $status_color = '#2196f3';
        $status_bg = '#e3f2fd';
        $status_icon = '📦';
    }
    
    // Available quantity styling
    $available_color = '#4caf50';
    if ($o->available <= 0) {
        $available_color = '#f44336';
    } elseif ($o->available < ($o->quantity * 0.2)) {
        $available_color = '#ff9800';
    }

    print '<tr class="oddeven">';
    print '<td><a href="view_donation.php?id='.$o->rowid.'"><strong>'.dol_escape_htmltag($o->ref).'</strong></a></td>';
    print '<td>'.dol_escape_htmltag($o->label).'</td>';
    print '<td>'.number_format($o->quantity, 2).'</td>';
    print '<td><span style="color: #ff9800;">'.number_format($o->quantity_allocated, 2).'</span></td>';
    print '<td><strong style="color:'.$available_color.';">'.number_format($o->available, 2).'</strong></td>';
    print '<td>'.dol_escape_htmltag($o->unit).'</td>';
    print '<td>'.$vendor.'</td>';
    print '<td>'.$date.'</td>';
    
    // Status with quick update
    print '<td>';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="margin:0;">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="id" value="'.$o->rowid.'">';
    
    print '<span style="display:inline-block; padding:3px 8px; border-radius:3px; background:'.$status_bg.'; color:'.$status_color.'; font-weight:bold; font-size:11px;">';
    print $status_icon.' '.dol_escape_htmltag($o->status);
    print '</span><br>';
    
    print '<select name="status" class="flat" style="width:110px; font-size:11px; margin-top:3px;">';
    print '<option value="Pending"'.($o->status=='Pending'?' selected':'').'>⏳ Pending</option>';
    print '<option value="Received"'.($o->status=='Received'?' selected':'').'>✅ Received</option>';
    print '<option value="Allocated"'.($o->status=='Allocated'?' selected':'').'>📦 Allocated</option>';
    print '</select> ';
    print '<input type="submit" name="update_status" class="button small" value="Update" style="font-size:10px; padding:2px 6px;">';
    print '</form>';
    print '</td>';
    
    print '<td class="center">';
    print '<a href="view_donation.php?id='.$o->rowid.'">View</a> | ';
    print '<a href="edit_donation.php?id='.$o->rowid.'">Edit</a> | ';
    print '<a href="delete_donation.php?id='.$o->rowid.'" style="color:#dc3545;">Delete</a>';
    print '</td>';
    print '</tr>';
}

print '</table>';

llxFooter();
?>
