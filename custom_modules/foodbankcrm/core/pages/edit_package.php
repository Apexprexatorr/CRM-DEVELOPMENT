<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Package ID is missing.</div>';
    print '<div><a href="packages.php">← Back to Packages</a></div>';
    llxFooter(); exit;
}

$package_id = (int) $_GET['id'];
$package = new Package($db);
$package->fetch($package_id);

$notice = '';

// Handle item deletion
if (isset($_GET['delete_item'])) {
    $item_id = (int) $_GET['delete_item'];
    $item = new PackageItem($db);
    $item->fetch($item_id);
    
    if ($item->delete($user) > 0) {
        $notice = '<div class="ok">Item deleted successfully!</div>';
    } else {
        $notice = '<div class="error">Error deleting item.</div>';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        // Update package
        $package->name = $_POST['name'];
        $package->description = $_POST['description'];
        $package->status = $_POST['status'];
        
        if ($package->update($user) > 0) {
            // Add new items
            $items_added = 0;
            $items_failed = 0;
            
            if (!empty($_POST['product_name']) && is_array($_POST['product_name'])) {
                foreach ($_POST['product_name'] as $index => $product_name) {
                    if (empty(trim($product_name))) continue;
                    
                    $item = new PackageItem($db);
                    $item->fk_package = $package_id;
                    $item->product_name = trim($product_name);
                    $item->quantity = !empty($_POST['product_quantity'][$index]) ? $_POST['product_quantity'][$index] : 0;
                    $item->unit = !empty($_POST['product_unit'][$index]) ? $_POST['product_unit'][$index] : 'kg';
                    $item->fk_vendor_preferred = !empty($_POST['product_vendor'][$index]) ? $_POST['product_vendor'][$index] : null;
                    
                    if ($item->create($user) > 0) {
                        $items_added++;
                    } else {
                        $items_failed++;
                    }
                }
            }
            
            $notice = '<div class="ok">Package updated successfully!';
            if ($items_added > 0) {
                $notice .= '<br>✅ '.$items_added.' new item(s) added.';
            }
            if ($items_failed > 0) {
                $notice .= '<br>⚠ '.$items_failed.' item(s) failed to add.';
            }
            $notice .= '</div>';
            
            // Refresh package data
            $package->fetch($package_id);
        } else {
            $notice = '<div class="error">Update failed: '.$package->error.'</div>';
        }
    }
}

// Get existing items
$existing_items = PackageItem::getAllByPackage($db, $package_id);

// Get all vendors for dropdown
$vendors_list = array();
$sql = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE 1 ORDER BY name ASC";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $vendors_list[$obj->rowid] = $obj->name;
    }
}

print $notice;
print '<div><a href="packages.php">← Back to Packages</a></div><br>';
?>

<h2>Edit Package: <?php echo dol_escape_htmltag($package->name); ?></h2>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF'].'?id='.$package_id; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  
  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><strong><?php echo dol_escape_htmltag($package->ref); ?></strong> (cannot be changed)</td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Package Name</span></td>
      <td><input class="flat" type="text" name="name" value="<?php echo dol_escape_htmltag($package->name); ?>" required></td>
    </tr>
    <tr>
      <td>Description</td>
      <td><textarea class="flat" name="description" rows="3"><?php echo dol_escape_htmltag($package->description); ?></textarea></td>
    </tr>
    <tr>
      <td>Status</td>
      <td>
        <select class="flat" name="status">
          <option value="Active" <?php echo $package->status == 'Active' ? 'selected' : ''; ?>>Active</option>
          <option value="Inactive" <?php echo $package->status == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
      </td>
    </tr>
  </table>
  
  <br>
  <h3>📦 Items in This Package</h3>
  
  <?php if (count($existing_items) > 0): ?>
  <h4>Current Items:</h4>
  <table class="noborder centpercent">
    <tr class="liste_titre">
      <th>Product Name</th>
      <th>Quantity</th>
      <th>Unit</th>
      <th>Preferred Vendor</th>
      <th class="center">Action</th>
    </tr>
    <?php foreach ($existing_items as $item): ?>
    <tr class="oddeven">
      <td><strong><?php echo dol_escape_htmltag($item->product_name); ?></strong></td>
      <td><?php echo dol_escape_htmltag($item->quantity); ?></td>
      <td><?php echo dol_escape_htmltag($item->unit); ?></td>
      <td><?php echo $item->vendor_name ? dol_escape_htmltag($item->vendor_name) : '—'; ?></td>
      <td class="center">
        <a href="<?php echo $_SERVER['PHP_SELF'].'?id='.$package_id.'&delete_item='.$item->id.'&token='.newToken(); ?>" 
           onclick="return confirm('Delete this item?');"
           style="color: #dc3545;">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <br>
  <?php else: ?>
  <p style="color: #999;">This package has no items yet.</p>
  <?php endif; ?>
  
  <h4>Add New Items:</h4>
  <div id="items-container">
    <table class="noborder centpercent">
      <tr class="liste_titre">
        <th width="30%">Product Name</th>
        <th width="15%">Quantity</th>
        <th width="10%">Unit</th>
        <th width="25%">Preferred Vendor</th>
        <th width="10%">Action</th>
      </tr>
      <tr class="item-row">
        <td><input class="flat" type="text" name="product_name[]" placeholder="e.g., Rice, Oil, Beans" style="width:95%;"></td>
        <td><input class="flat" type="number" name="product_quantity[]" step="0.01" placeholder="10" style="width:95%;"></td>
        <td>
          <select class="flat" name="product_unit[]" style="width:95%;">
            <option value="kg">kg</option>
            <option value="liters">liters</option>
            <option value="boxes">boxes</option>
            <option value="bags">bags</option>
            <option value="units">units</option>
          </select>
        </td>
        <td>
          <select class="flat" name="product_vendor[]" style="width:95%;">
            <option value="">-- No Preference --</option>
            <?php foreach ($vendors_list as $vendor_id => $vendor_name): ?>
            <option value="<?php echo $vendor_id; ?>"><?php echo dol_escape_htmltag($vendor_name); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><button type="button" class="button small" onclick="removeItemRow(this)">Remove</button></td>
      </tr>
    </table>
  </div>
  
  <br>
  <div style="margin-bottom: 20px;">
    <button type="button" class="button" onclick="addItemRow()">+ Add Another Item</button>
  </div>
  
  <br>
  <div class="center">
    <input class="button" type="submit" value="Update Package">
    <a class="button" href="packages.php">Cancel</a>
  </div>
</form>

<script>
function addItemRow() {
    var container = document.getElementById('items-container').querySelector('table');
    var newRow = container.querySelector('.item-row').cloneNode(true);
    
    var inputs = newRow.querySelectorAll('input, select');
    inputs.forEach(function(input) {
        if (input.type === 'text' || input.type === 'number') {
            input.value = '';
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });
    
    container.appendChild(newRow);
}

function removeItemRow(button) {
    var container = document.getElementById('items-container').querySelector('table');
    var rows = container.querySelectorAll('.item-row');
    
    if (rows.length > 1) {
        button.closest('.item-row').remove();
    } else {
        alert('You must keep at least one item row. Just leave it empty if you don\'t want to add items.');
    }
}
</script>

<?php llxFooter(); ?>