<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/donation.class.php';

$langs->load("admin");
llxHeader();

print '<p><a class="butAction" href="create_donation.php">+ ADD DONATION</a></p>';

// Build query (columns that exist in your schema)
$sql = "SELECT d.rowid, d.ref, d.label, d.quantity, d.unit, d.date_donation,
               v.name AS vendor_name,
               CONCAT(b.firstname, ' ', b.lastname) AS beneficiary_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations AS d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors AS v
               ON v.rowid = d.fk_vendor
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries AS b
               ON b.rowid = d.fk_beneficiary
        ORDER BY d.date_donation DESC";

$res = $db->query($sql);
if (! $res) {
    print '<div class="error">SQL error: '.dol_escape_htmltag($db->lasterror()).'</div>';
    llxFooter(); exit;
}

$tok = newToken();

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th>Ref</th><th>Label</th><th>Qty</th><th>Unit</th><th>Vendor</th><th>Beneficiary</th><th>Date</th><th>Actions</th>';
print '</tr>';

$odd = 0;
while ($o = $db->fetch_object($res)) {
    $vendor = $o->vendor_name !== null ? dol_escape_htmltag($o->vendor_name) : '—';
    $benef  = $o->beneficiary_name !== null ? dol_escape_htmltag($o->beneficiary_name) : '—';
    $date   = $o->date_donation ? dol_print_date($db->jdate($o->date_donation), 'dayhour') : '—';

    print '<tr class="oddeven'.($odd ? ' odd' : '').'">';
    print '<td>'.dol_escape_htmltag($o->ref).'</td>';
    print '<td>'.dol_escape_htmltag($o->label).'</td>';
    print '<td>'.(int)$o->quantity.'</td>';
    print '<td>'.dol_escape_htmltag($o->unit).'</td>';
    print '<td>'.$vendor.'</td>';
    print '<td>'.$benef.'</td>';
    print '<td>'.$date.'</td>';
    print '<td>'.
          '<a href="edit_donation.php?id='.(int)$o->rowid.'">Edit</a> | '.
          '<a href="delete_donation.php?id='.(int)$o->rowid.'&token='.$tok.'">Delete</a>'.
          '</td>';
    print '</tr>';
    $odd = 1 - $odd;
}
print '</table>';

llxFooter();
