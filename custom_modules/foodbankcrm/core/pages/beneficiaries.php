<?php
// Include Dolibarr's main.inc.php file to load the environment
require_once dirname(__DIR__, 4) . '/main.inc.php'; // Absolute path from the current file

// Include the Beneficiary class from the correct path
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php'; // Correct path to the Beneficiary class

$langs->load("admin");
llxHeader();

// Your business logic for listing beneficiaries
$beneficiary = new Beneficiary($db);
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_beneficiaries";
$res = $db->query($sql);

if ($res) {
    print '<h2>Beneficiaries</h2>';
    print '<table class="noborder" width="100%">';
    print '<tr><th>Ref</th><th>First Name</th><th>Last Name</th><th>Phone</th><th>Email</th><th>Actions</th></tr>';

    while ($obj = $db->fetch_object($res)) {
        print '<tr>';
        print '<td>' . $obj->ref . '</td>';
        print '<td>' . $obj->firstname . '</td>';
        print '<td>' . $obj->lastname . '</td>';
        print '<td>' . $obj->phone . '</td>';
        print '<td>' . $obj->email . '</td>';
        print '<td>';
        print '<a href="edit_beneficiary.php?id=' . $obj->rowid . '">Edit</a>';
        print ' | ';
        print '<a href="delete_beneficiary.php?id=' . $obj->rowid . '">Delete</a>';
        print '</td>';
        print '</tr>';
    }

    print '</table>';
} else {
    print '<p>No beneficiaries found.</p>';
}

llxFooter();
?>
