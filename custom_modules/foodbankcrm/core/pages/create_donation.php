<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/donation.class.php';
$langs->load("admin");
llxHeader();

$notice = '';
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['create'])) {
  if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
    $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
  } else {
    $donation = new DonationFB($db);
    $donation->ref = GETPOST('ref','alpha'); // Will auto-generate if empty
    $donation->fk_vendor = GETPOST('fk_vendor','int');
    $donation->fk_beneficiary = GETPOST('fk_beneficiary','int');
    $donation->label = GETPOST('label','alpha');
    $donation->quantity = GETPOST('quantity','int');
    $donation->unit = GETPOST('unit','alpha');
    $donation->note = GETPOST('note','restricthtml');
    
    $result = $donation->create($user);
    
    if ($result > 0) {
      $notice = '<div class="ok">Donation created successfully! Ref: '.$donation->ref.' (ID: '.$result.')</div>';
    } else {
      $notice = '<div class="error">Error creating donation: '.$donation->error.'</div>';
    }
  }
}

print $notice;
print '<div><a href="donations.php">← Back to Donations</a></div><br>';

// Fetch all vendors
$sql_vendor = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors ORDER BY name";
$res_vendor = $db->query($sql_vendor);

// Fetch all beneficiaries
$sql_ben = "SELECT rowid, ref, firstname, lastname FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY ref";
$res_ben = $db->query($sql_ben);
?>

<h2>Create Donation</h2>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  
  <table class="border centpercent">
    <tr>
      <td width="25%"><span class="fieldrequired">Ref</span></td>
      <td><input class="flat" type="text" name="ref" placeholder="Leave empty for auto-generation (DON2025-0001)"></td>
    </tr>
    
    <tr>
      <td><span class="fieldrequired">Vendor</span></td>
      <td>
        <select class="flat" name="fk_vendor" required>
          <option value="">-- Select Vendor --</option>
          <?php
          while ($obj = $db->fetch_object($res_vendor)) {
              print '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->name).'</option>';
          }
          ?>
        </select>
      </td>
    </tr>
    
    <tr>
      <td><span class="fieldrequired">Beneficiary</span></td>
      <td>
        <select class="flat" name="fk_beneficiary" required>
          <option value="">-- Select Beneficiary --</option>
          <?php
          while ($obj = $db->fetch_object($res_ben)) {
              print '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->ref.' - '.$obj->firstname.' '.$obj->lastname).'</option>';
          }
          ?>
        </select>
      </td>
    </tr>
    
    <tr>
      <td>Label/Description</td>
      <td><input class="flat" type="text" name="label" placeholder="e.g., Rice donation"></td>
    </tr>
    
    <tr>
      <td><span class="fieldrequired">Quantity</span></td>
      <td><input class="flat" type="number" name="quantity" min="1" value="1" required></td>
    </tr>
    
    <tr>
      <td><span class="fieldrequired">Unit</span></td>
      <td><input class="flat" type="text" name="unit" value="kg" required></td>
    </tr>
    
    <tr>
      <td>Note</td>
      <td><textarea class="flat" name="note" rows="3" cols="50"></textarea></td>
    </tr>
  </table>
  
  <br>
  <div class="center">
    <input type="submit" class="button" name="create" value="Create Donation">
    <a class="button" href="donations.php">Cancel</a>
  </div>
</form>

<?php llxFooter(); ?>