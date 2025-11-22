<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';

$langs->load("admin");
llxHeader();

print '<div class="fichecenter">';
print '<a class="butAction" href="create_package.php">+ Create Package</a>';
print '</div><br>';

$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE fk_package = p.rowid) as item_count,
        (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_package = p.rowid) as usage_count
        FROM ".MAIN_DB_PREFIX."foodbank_packages p
        ORDER BY p.rowid DESC";

$res = $db->query($sql);

if ($res) {
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Ref</th>';
    print '<th>Package Name</th>';
    print '<th>Description</th>';
    print '<th class="center">Items</th>';
    print '<th class="center">Used In</th>';
    print '<th>Status</th>';
    print '<th class="center">Actions</th>';
    print '</tr>';

    if ($db->num_rows($res) == 0) {
        print '<tr><td colspan="7" class="center" style="padding: 30px; color: #999;">';
        print 'No packages found. Create your first package to get started!';
        print '</td></tr>';
    }

    while ($obj = $db->fetch_object($res)) {
        // Status badge colors
        $status_color = '#4caf50'; // green for Active
        $status_bg = '#e8f5e9';
        if ($obj->status == 'Inactive') {
            $status_color = '#999';
            $status_bg = '#f5f5f5';
        }

        print '<tr class="oddeven">';
        print '<td><strong>'.dol_escape_htmltag($obj->ref).'</strong></td>';
        print '<td><a href="view_package.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->name).'</a></td>';
        print '<td>'.dol_escape_htmltag(dol_trunc($obj->description, 60)).'</td>';
        print '<td class="center">';
        if ($obj->item_count > 0) {
            print '<span style="background: #e3f2fd; color: #1976d2; padding: 3px 8px; border-radius: 3px; font-weight: bold;">';
            print $obj->item_count.' items';
            print '</span>';
        } else {
            print '<span style="color: #999;">0 items</span>';
        }
        print '</td>';
        print '<td class="center">';
        if ($obj->usage_count > 0) {
            print '<span style="color: #f57c00;">'.$obj->usage_count.' distributions</span>';
        } else {
            print '<span style="color: #999;">Not used yet</span>';
        }
        print '</td>';
        print '<td>';
        print '<span style="display:inline-block; padding:3px 8px; border-radius:3px; background:'.$status_bg.'; color:'.$status_color.'; font-weight:bold; font-size:11px;">';
        print dol_escape_htmltag($obj->status);
        print '</span>';
        print '</td>';
        print '<td class="center">';
        print '<a href="view_package.php?id='.$obj->rowid.'">View</a> | ';
        print '<a href="edit_package.php?id='.$obj->rowid.'">Edit</a> | ';
        print '<a href="delete_package.php?id='.$obj->rowid.'" style="color: #dc3545;">Delete</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '</table>';
} else {
    print '<div class="error">SQL Error: '.dol_escape_htmltag($db->lasterror()).'</div>';
}

llxFooter();
?>