<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Donation ID is missing in the URL.</div>';
    print '<div><a href="donations.php">← Back to Donations</a></div>';
    llxFooter(); exit;
}

$id = (int) $_GET['id'];

// Fetch donation
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_donations WHERE rowid = ".$id;
$resql = $db->query($sql);
$donation = $db->fetch_object($resql);

if (!$donation) {
    print '<div class="error">Donation not found.</div>';
    print '<div><a href="donations.php">← Back to Donations</a></div>';
    llxFooter(); exit;
}

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $ref = $db->escape(GETPOST('ref','alpha'));
        $label = $db->escape(GETPOST('label','alpha'));
        $qty = (int) GETPOST('quantity','int');
        $unit = $db->escape(GETPOST('unit','alpha'));
        $fk_vendor = (int) GETPOST('fk_vendor','int');
        $fk_beneficiary = (int) GETPOST('fk_beneficiary','int');

        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_donations 
                SET ref='".$ref."',
                    label='".$label."',
                    quantity=".$qty.",
                    unit='".$unit."',
                    fk_vendor=".$fk_vendor.",
                    fk_beneficiary=".$fk_beneficiary."
                WHERE rowid=".$id;
        
        $res = $db->query($sql);
        $notice = $res ? '<div class="ok">Donation updated successfully!</div>'
                       : '<div class="error">Update failed: '.$db->lasterror().'</div>';
    }
}

print $notice;
print '<div><a href="donations.php">← Back to Donations</a></div><br>';

function render_select($db,$table,$labelfield,$name,$selected=0){
  $res=$db->query("SELECT rowid,$labelfield AS lbl FROM ".MAIN_DB_PREFIX."$table ORDER BY lbl");
  print '<select class="flat" name="'.$name.'" required>';
  while($o=$db->fetch_object($res)){
    $sel = ($o->rowid==$selected)?' selected':'';
    print '<option value="'.$o->rowid.'"'.$sel.'>'.dol_escape_htmltag($o->lbl).'</option>';
  }
  print '</select>';
}
?>

<h2>Edit Donation</h2>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF'].'?id='.$id; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  Ref: <input class="flat" type="text" name="ref" value="<?php echo dol_escape_htmltag($donation->ref); ?>" required><br>
  Label: <input class="flat" type="text" name="label" value="<?php echo dol_escape_htmltag($donation->label); ?>"><br>
  Quantity: <input class="flat" type="number" name="quantity" value="<?php echo $donation->quantity; ?>" min="0"><br>
  Unit: <input class="flat" type="text" name="unit" value="<?php echo dol_escape_htmltag($donation->unit); ?>"><br>
  Vendor: <?php render_select($db,'foodbank_vendors','name','fk_vendor',$donation->fk_vendor); ?><br>
  Beneficiary: <?php render_select($db,'foodbank_beneficiaries',"CONCAT(ref,' - ',firstname,' ',lastname)",'fk_beneficiary',$donation->fk_beneficiary); ?><br>
  <input class="button" type="submit" value="Update Donation">
</form>

<?php llxFooter(); ?>