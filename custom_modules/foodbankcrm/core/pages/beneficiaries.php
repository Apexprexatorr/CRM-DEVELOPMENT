<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php'; 

$langs->load("admin");
llxHeader();

// Your business logic for listing beneficiaries
$beneficiary = new Beneficiary($db);
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_beneficiaries";
$res = $db->query($sql);

if ($res) {
    print '<div class="fichecenter">';
    print '<a class="butAction" href="create_beneficiary.php">+ Add Beneficiary</a>';
    print '</div><br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><th>Ref</th><th>First</th><th>Last</th><th>Phone</th><th>Email</th><th>Actions</th></tr>';

    while ($obj = $db->fetch_object($res)) {
        print '<tr class="oddeven">';
        print '<td>'.$obj->ref.'</td>';
        print '<td>'.$obj->firstname.'</td>';
        print '<td>'.$obj->lastname.'</td>';
        print '<td>'.$obj->phone.'</td>';
        print '<td>'.$obj->email.'</td>';
        print '<td>';
        print '<a href="edit_beneficiary.php?id='.$obj->rowid.'">Edit</a> | ';
        print '<a href="delete_beneficiary.php?id='.$obj->rowid.'">Delete</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '</table>';
} else {
    print '<p>No beneficiaries found.</p>';
}

llxFooter();
?>