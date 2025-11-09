<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Distribution ID is missing in the URL.</div>';
    print '<div><a href="distributions.php">← Back to Distributions</a></div>';
    llxFooter(); exit;
}

$id = (int) $_GET['id'];

// Fetch distribution
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE rowid = ".$id;
$resql = $db->query($sql);
$dist = $db->fetch_object($resql);

if (!$dist) {
    print '<div class="error">Distribution not found.</div>';
    print '<div><a href="distributions.php">← Back to Distributions</a></div>';
    llxFooter(); exit;
}

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $ref = $db->escape(GETPOST('ref','alpha'));
        $fk_beneficiary = (int) GETPOST('fk_beneficiary','int');
        $fk_warehouse = (int) GETPOST('fk_warehouse','int');
        $fk_user = (int) GETPOST('fk_user','int');
        $note = $db->escape(GETPOST('note','restricthtml'));

        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions 
                SET ref='".$ref."',
                    fk_beneficiary=".$fk_beneficiary.",
                    fk_warehouse=".$fk_warehouse.",
                    fk_user=".($fk_user?:'NULL').",
                    note='".$note."'
                WHERE rowid=".$id;
        
        $res = $db->query($sql);
        $notice = $res ? '<div class="ok">Distribution updated successfully!</div>'
                       : '<div class="error">Update failed: '.$db->lasterror().'</div>';
    }
}

print $notice;
print '<div><a href="distributions.php">← Back to Distributions</a></div><br>';

function select_generic($db,$sql,$name,$selected=0){
  $res=$db->query($sql); 
  print '<select class="flat" name="'.$name.'">';
  while($o=$db->fetch_object($res)){
    $sel=($o->id==$selected)?' selected':'';
    print '<option value="'.$o->id.'"'.$sel.'>'.dol_escape_htmltag($o->label).'</option>';
  } 
  print '</select>';
}
?>

<h2>Edit Distribution</h2>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF'].'?id='.$id; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  Ref: <input class="flat" type="text" name="ref" value="<?php echo dol_escape_htmltag($dist->ref); ?>" required><br>
  Beneficiary:
  <?php select_generic($db,"SELECT rowid AS id, CONCAT(ref,' - ',firstname,' ',lastname) AS label FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY label",'fk_beneficiary',$dist->fk_beneficiary); ?><br>
  Warehouse:
  <?php select_generic($db,"SELECT rowid AS id, label AS label FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY label",'fk_warehouse',$dist->fk_warehouse); ?><br>
  User (optional):
  <?php select_generic($db,"SELECT rowid AS id, login AS label FROM ".MAIN_DB_PREFIX."user ORDER BY login",'fk_user',$dist->fk_user); ?><br>
  Note:<br>
  <textarea class="flat" name="note"><?php echo dol_escape_htmltag($dist->note); ?></textarea><br>
  <input class="button" type="submit" value="Update Distribution">
</form>

<?php llxFooter(); ?>