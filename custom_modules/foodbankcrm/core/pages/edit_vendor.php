<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendorproduct.class.php';

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Vendor ID is missing in the URL.</div>';
    print '<div><a href="vendors.php">← Back to Vendors</a></div>';
    llxFooter(); exit;
}

$vendor_id = (int) $_GET['id'];
$v = new Vendor($db);
$v->fetch($vendor_id);

$notice = '';

// Handle product deletion
if (isset($_GET['delete_product'])) {
    $product_id = (int) $_GET['delete_product'];
    $vp = new VendorProduct($db);
    $vp->fetch($product_id);
    
    $result = $vp->delete($user);
    if ($result > 0) {
        $notice = '<div class="ok">Product deleted successfully!</div>';
    } elseif ($result == -2) {
        $notice = '<div class="error">'.$vp->error.'</div>';
    } else {
        $notice = '<div class="error">Error deleting product: '.$vp->error.'</div>';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        // Update vendor info
        $v->ref = $_POST['ref'];
        $v->name = $_POST['name'];
        $v->contact_person = $_POST['contact_person'];
        $v->phone = $_POST['phone'];
        $v->email = $_POST['email'];
        $v->address = $_POST['address'];
        $v->note = $_POST['note'];

        $res = $v->update($user);
        
        if ($res > 0) {
            // Handle new products
            $products_added = 0;
            $products_failed = 0;
            
            if (!empty($_POST['product_name']) && is_array($_POST['product_name'])) {
                foreach ($_POST['product_name'] as $index => $product_name) {
                    if (empty(trim($product_name))) continue;
                    
                    $vp = new VendorProduct($db);
                    $vp->fk_vendor = $vendor_id;
                    $vp->product_name = trim($product_name);
                    $vp->unit = !empty($_POST['product_unit'][$index]) ? $_POST['product_unit'][$index] : 'kg';
                    $vp->typical_quantity = !empty($_POST['product_quantity'][$index]) ? $_POST['product_quantity'][$index] : null;
                    $vp->status = 'Active';
                    
                    if ($vp->create($user) > 0) {
                        $products_added++;
                    } else {
                        $products_failed++;
                    }
                }
            }
            
            $notice = '<div class="ok">Vendor updated successfully!';
            if ($products_added > 0) {
                $notice .= '<br>✅ '.$products_added.' new product(s) added.';
            }
            if ($products_failed > 0) {
                $notice .= '<br>⚠ '.$products_failed.' product(s) failed to add.';
            }
            $notice .= '</div>';
            
            // Refresh vendor data
            $v->fetch($vendor_id);
        } else {
            $notice = '<div class="error">Update failed: '.$v->error.'</div>';
        }
    }
}

// Get existing products
$existing_products = VendorProduct::getAllByVendor($db, $vendor_id);

print $notice;
print '<div><a href="vendors.php">← Back to Vendors</a></div><br>';
?>

<h2>Edit Vendor: <?php echo dol_escape_htmltag($v->name); ?></h2>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF'].'?id='.$vendor_id; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  
  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><input class="flat" type="text" name="ref" value="<?php echo dol_escape_htmltag($v->ref); ?>" required></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Vendor Name</span></td>
      <td><input class="flat" type="text" name="name" value="<?php echo dol_escape_htmltag($v->name); ?>" required></td>
    </tr>
    <tr>
      <td>Contact Person</td>
      <td><input class="flat" type="text" name="contact_person" value="<?php echo dol_escape_htmltag($v->contact_person); ?>"></td>
    </tr>
    <tr>
      <td>Phone</td>
      <td><input class="flat" type="text" name="phone" value="<?php echo dol_escape_htmltag($v->phone); ?>"></td>
    </tr>
    <tr>
      <td>Email</td>
      <td><input class="flat" type="email" name="email" value="<?php echo dol_escape_htmltag($v->email); ?>"></td>
    </tr>
    <tr>
      <td>Address</td>
      <td><textarea class="flat" name="address" rows="3"><?php echo dol_escape_htmltag($v->address); ?></textarea></td>
    </tr>
    <tr>
      <td>Note</td>
      <td><textarea class="flat" name="note" rows="3"><?php echo dol_escape_htmltag($v->note); ?></textarea></td>
    </tr>
  </table>
  
  <br>
  <h3>📦 Products This Vendor Supplies</h3>
  
  <?php if (count($existing_products) > 0): ?>
  <h4>Current Products:</h4>
  <table class="noborder centpercent">
    <tr class="liste_titre">
      <th>Product Name</th>
      <th>Unit</th>
      <th>Typical Quantity</th>
      <th>Status</th>
      <th class="center">Action</th>
    </tr>
    <?php foreach ($existing_products as $product): ?>
    <tr class="oddeven">
      <td><strong><?php echo dol_escape_htmltag($product->product_name); ?></strong></td>
      <td><?php echo dol_escape_htmltag($product->unit); ?></td>
      <td><?php echo $product->typical_quantity ? dol_escape_htmltag($product->typical_quantity) : '—'; ?></td>
      <td>
        <span style="color: <?php echo $product->status == 'Active' ? 'green' : 'gray'; ?>;">
          <?php echo dol_escape_htmltag($product->status); ?>
        </span>
      </td>
      <td class="center">
        <a href="<?php echo $_SERVER['PHP_SELF'].'?id='.$vendor_id.'&delete_product='.$product->id.'&token='.newToken(); ?>" 
           onclick="return confirm('Delete this product?');"
           style="color: #dc3545;">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <br>
  <?php else: ?>
  <p style="color: #999;">This vendor has no products in the catalog yet.</p>
  <?php endif; ?>
  
  <h4>Add New Products:</h4>
  <div id="products-container">
    <table class="noborder centpercent">
      <tr class="liste_titre">
        <th width="40%">Product Name</th>
        <th width="15%">Unit</th>
        <th width="20%">Typical Quantity</th>
        <th width="10%">Action</th>
      </tr>
      <tr class="product-row">
        <td><input class="flat" type="text" name="product_name[]" placeholder="e.g., Rice, Oil, Beans" style="width:95%;"></td>
        <td>
          <select class="flat" name="product_unit[]" style="width:95%;">
            <option value="kg">kg</option>
            <option value="liters">liters</option>
            <option value="boxes">boxes</option>
            <option value="bags">bags</option>
            <option value="units">units</option>
          </select>
        </td>
        <td><input class="flat" type="number" name="product_quantity[]" step="0.01" placeholder="100" style="width:95%;"></td>
        <td><button type="button" class="button small" onclick="removeProductRow(this)">Remove</button></td>
      </tr>
    </table>
  </div>
  
  <br>
  <div style="margin-bottom: 20px;">
    <button type="button" class="button" onclick="addProductRow()">+ Add Another Product</button>
  </div>
  
  <br>
  <div class="center">
    <input class="button" type="submit" value="Update Vendor">
    <a class="button" href="vendors.php">Cancel</a>
  </div>
</form>

<script>
function addProductRow() {
    var container = document.getElementById('products-container').querySelector('table');
    var newRow = container.querySelector('.product-row').cloneNode(true);
    
    // Clear input values
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

function removeProductRow(button) {
    var container = document.getElementById('products-container').querySelector('table');
    var rows = container.querySelectorAll('.product-row');
    
    if (rows.length > 1) {
        button.closest('.product-row').remove();
    } else {
        alert('You must keep at least one product row. Just leave it empty if you don\'t want to add products.');
    }
}
</script>

<?php llxFooter(); ?>
