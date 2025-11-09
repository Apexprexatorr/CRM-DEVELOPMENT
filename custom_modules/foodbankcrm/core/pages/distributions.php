<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/distribution.class.php';
llxHeader();

print '<p><a class="butAction" href="create_distribution.php">+ ADD DISTRIBUTION</a></p>';

$sql = "SELECT t.rowid, t.ref, t.date_distribution,
               CONCAT(b.firstname,' ',b.lastname) AS beneficiary_name,
               w.label AS warehouse_label,
               u.login AS user_login
        FROM ".MAIN_DB_PREFIX."foodbank_distributions t
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON b.rowid = t.fk_beneficiary
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_warehouses w ON w.rowid = t.fk_warehouse
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = t.fk_user
        ORDER BY t.date_distribution DESC";
$res = $db->query($sql);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th>Ref</th><th>Beneficiary</th><th>Warehouse</th><th>User</th><th>Date</th><th>Actions</th></tr>';
while ($o = $db->fetch_object($res)) {
  print '<tr class="oddeven">';
  print '<td>'.$o->ref.'</td>';
  print '<td>'.dol_escape_htmltag($o->beneficiary_name).'</td>';
  print '<td>'.dol_escape_htmltag($o->warehouse_label).'</td>';
  print '<td>'.dol_escape_htmltag($o->user_login).'</td>';
  print '<td>'.dol_print_date($db->jdate($o->date_distribution),'dayhour').'</td>';
  print '<td><a href="edit_distribution.php?id='.$o->rowid.'">Edit</a> | <a href="delete_distribution.php?id='.$o->rowid.'">Delete</a></td>';
  print '</tr>';
}
print '</table>';

llxFooter();
