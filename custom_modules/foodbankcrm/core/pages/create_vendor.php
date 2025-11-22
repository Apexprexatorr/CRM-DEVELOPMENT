<?php
require_once dirname(__DIR__, 4) . '/main.inc.php'; 
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php'; 
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendorproduct.class.php';

$langs->load("admin");
llxHeader();

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $v = new Vendor($db);
        $v->ref = $_POST['ref']; // Will auto-generate if empty
        $v->name = $_POST['name'];
        $v->contact_person = $_POST['contact_person'];
        $v->phone = $_POST['phone'];
        $v->email = $_POST['email'];
        $v->address = $_POST['address'];
        $v->note = $_POST['note'];

        $res = $v->create($user);
        if ($res > 0) {
            // Vendor created successfully, now add products
            $products_added = 0;
            $products_failed = 0;
            
            // Check if products were submitted
            if (!empty($_POST['product_name']) && is_array($_POST['product_name'])) {
                foreach ($_POST['product_name'] as $index => $product_name) {
                    // Skip empty product names
                    if (empty(trim($product_name))) continue;
                    
                    $vp = new VendorProduct($db);
                    $vp->fk_vendor = $res; // The vendor ID we just created
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
            
            $notice = '<div class="ok">Vendor created successfully! Ref: '.$v->ref.' (ID: '.$res.')';
            if ($products_added > 0) {
                $notice .= '<br>✅ '.$products_added.' product(s) added to vendor catalog.';
            }
            if ($products_failed > 0) {
                $notice .= '<br>⚠ '.$products_failed.' product(s) failed to add.';
            }
            $notice .= '</div>';
            
            // Optionally redirect to vendor list
            // header('Location: vendors.php');
            // exit;
        } else {
            $notice = '<div class="error">Error creating vendor: '.dol_escape_htmltag($v->error).'</div>';
        }
    }
}

print $notice;
print '<div><a href="vendors.php">← Back to Vendors</a></div><br>';
?>

<h2>Create Vendor</h2>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  
  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><input class="flat" type="text" name="ref" placeholder="Leave empty for auto-generation (VEN2025-0001)"></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Vendor Name</span></td>
      <td><input class="flat" type="text" name="name" required></td>
    </tr>
    <tr>
      <td>Contact Person</td>
      <td><input class="flat" type="text" name="contact_person"></td>
    </tr>
    <tr>
      <td>Phone</td>
      <td><input class="flat" type="text" name="phone"></td>
    </tr>
    <tr>
      <td>Email</td>
      <td><input class="flat" type="email" name="email"></td>
    </tr>
    <tr>
      <td>Address</td>
      <td><textarea class="flat" name="address" rows="3"></textarea></td>
    </tr>
    <tr>
      <td>Note</td>
      <td><textarea class="flat" name="note" rows="3"></textarea></td>
    </tr>
  </table>
  
  <br>
  <h3>📦 Products This Vendor Supplies (Optional)</h3>
  <p style="color: #666; font-size: 12px;">Add products that this vendor typically supplies. This makes creating donations faster.</p>
  
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
    <input class="button" type="submit" value="Create Vendor & Products">
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
    
    // Keep at least one row
    if (rows.length > 1) {
        button.closest('.product-row').remove();
    } else {
        alert('You must keep at least one product row. Just leave it empty if you don\'t want to add products.');
    }
}
</script>

<?php llxFooter(); ?>
