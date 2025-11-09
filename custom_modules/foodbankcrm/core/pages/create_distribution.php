<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
$langs->load("admin");
llxHeader();

$notice = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
  if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
    $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
  } else {
    $dist = new Distribution($db);
    $dist->ref = GETPOST('ref','alpha'); // Will auto-generate if empty
    $dist->fk_beneficiary = GETPOST('fk_beneficiary','int');
    $dist->fk_warehouse = GETPOST('fk_warehouse','int');
    $dist->fk_user = GETPOST('fk_user','int');
    $dist->note = GETPOST('note','restricthtml');
    
    $result = $dist->create($user);
    
    if ($result > 0) {
      $notice = '<div class="ok">Distribution created successfully! Ref: '.$dist->ref.' (ID: '.$result.')</div>';
    } else {
      $notice = '<div class="error">Error creating distribution: '.$dist->error.'</div>';
    }
  }
}

print $notice;
print '<div><a href="distributions.php">← Back to Distributions</a></div><br>';

function select_generic($db,$sql,$name,$selected=0){
  $res=$db->query($sql); 
  print '<select class="flat" name="'.$name.'" required>';
  print '<option value="">-- Select --</option>';
  while($o=$db->fetch_object($res)){
    $sel=($o->id==$selected)?' selected':'';
    print '<option value="'.$o->id.'"'.$sel.'>'.dol_escape_htmltag($o->label).'</option>';
  } 
  print '</select>';
}
?>

<h2>Create Distribution</h2>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  
  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><input class="flat" type="text" name="ref" placeholder="Leave empty for auto-generation (DIS2025-0001)"></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Beneficiary</span></td>
      <td><?php select_generic($db,"SELECT rowid AS id, CONCAT(ref,' - ',firstname,' ',lastname) AS label FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY label",'fk_beneficiary'); ?></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Warehouse</span></td>
      <td><?php select_generic($db,"SELECT rowid AS id, label AS label FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY label",'fk_warehouse'); ?></td>
    </tr>
    <tr>
      <td>User </td>
      <td><?php select_generic($db,"SELECT rowid AS id, login AS label FROM ".MAIN_DB_PREFIX."user ORDER BY login",'fk_user'); ?></td>
    </tr>
    <tr>
      <td>Note</td>
      <td><textarea class="flat" name="note" rows="3" cols="50"></textarea></td>
    </tr>
  </table>
  
  <br>
  <div class="center">
    <input class="button" type="submit" value="Create Distribution">
    <a class="button" href="distributions.php">Cancel</a>
  </div>
</form>

<?php llxFooter(); ?>