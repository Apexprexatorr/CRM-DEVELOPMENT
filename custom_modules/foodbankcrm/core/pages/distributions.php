<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';

$langs->load("admin");
llxHeader();

print '<div class="fichecenter">';
print '<a class="butAction" href="create_distribution.php">+ Create Distribution</a>';
print '</div><br>';

$sql = "SELECT d.*, 
        b.firstname, b.lastname, b.ref as beneficiary_ref,
        w.label as warehouse_name,
        p.name as package_name,
        (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE fk_distribution = d.rowid) as item_count
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON d.fk_warehouse = w.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_packages p ON d.fk_package = p.rowid
        ORDER BY d.date_distribution DESC";

$res = $db->query($sql);

if ($res) {
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Ref</th>';
    print '<th>Beneficiary</th>';
    print '<th>Package</th>';
    print '<th class="center">Items</th>';
    print '<th>Warehouse</th>';
    print '<th>Date</th>';
    print '<th>Status</th>';
    print '<th class="center">Actions</th>';
    print '</tr>';

    if ($db->num_rows($res) == 0) {
        print '<tr><td colspan="8" class="center" style="padding: 30px; color: #999;">';
        print 'No distributions found. Create your first distribution!';
        print '</td></tr>';
    }

    while ($obj = $db->fetch_object($res)) {
        // Status colors
        $status_color = '#ff9800'; // orange for Prepared
        $status_bg = '#fff3e0';
        if ($obj->status == 'Delivered') {
            $status_color = '#2196f3'; // blue
            $status_bg = '#e3f2fd';
        } elseif ($obj->status == 'Completed') {
            $status_color = '#4caf50'; // green
            $status_bg = '#e8f5e9';
        }

        print '<tr class="oddeven">';
        print '<td><a href="view_distribution.php?id='.$obj->rowid.'"><strong>'.dol_escape_htmltag($obj->ref).'</strong></a></td>';
        print '<td>'.dol_escape_htmltag($obj->firstname.' '.$obj->lastname).'<br><span style="color:#666; font-size:11px;">'.dol_escape_htmltag($obj->beneficiary_ref).'</span></td>';
        print '<td>'.($obj->package_name ? dol_escape_htmltag($obj->package_name) : '<span style="color:#999;">Custom</span>').'</td>';
        print '<td class="center">';
        if ($obj->item_count > 0) {
            print '<span style="background:#e3f2fd; color:#1976d2; padding:3px 8px; border-radius:3px; font-weight:bold;">';
            print $obj->item_count.' items';
            print '</span>';
        } else {
            print '<span style="color:#999;">0 items</span>';
        }
        print '</td>';
        print '<td>'.dol_escape_htmltag($obj->warehouse_name).'</td>';
        print '<td>'.dol_print_date($db->jdate($obj->date_distribution), 'day').'</td>';
        print '<td>';
        print '<span style="display:inline-block; padding:3px 8px; border-radius:3px; background:'.$status_bg.'; color:'.$status_color.'; font-weight:bold; font-size:11px;">';
        print dol_escape_htmltag($obj->status);
        print '</span>';
        print '</td>';
        print '<td class="center">';
        print '<a href="view_distribution.php?id='.$obj->rowid.'">View</a> | ';
        print '<a href="edit_distribution.php?id='.$obj->rowid.'">Edit</a> | ';
        print '<a href="delete_distribution.php?id='.$obj->rowid.'" style="color:#dc3545;">Delete</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '</table>';
} else {
    print '<div class="error">SQL Error: '.dol_escape_htmltag($db->lasterror()).'</div>';
}

llxFooter();
?>