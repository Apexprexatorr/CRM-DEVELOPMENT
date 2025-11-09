<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';

$langs->load("admin");
llxHeader();

$vendor = new Vendor($db);
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_vendors";
$res = $db->query($sql);

if ($res) {
    print '<div class="fichecenter">';
    print '<a class="butAction" href="create_vendor.php">+ Add Vendor</a>';
    print '</div><br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><th>Ref</th><th>Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Actions</th></tr>';

 while ($obj = $db->fetch_object($res)) {
        print '<tr class="oddeven">';
        print '<td>'.$obj->ref.'</td>';
        print '<td>'.$obj->name.'</td>';
        print '<td>'.$obj->contact_person.'</td>';
        print '<td>'.$obj->phone.'</td>';
        print '<td>'.$obj->email.'</td>';
        print '<td>';
        print '<a href="edit_vendor.php?id='.$obj->rowid.'">Edit</a> | ';
        print '<a href="delete_vendor.php?id='.$obj->rowid.'">Delete</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '</table>';
} else {
    print '<p>No vendors found.</p>';
}

llxFooter();
?>