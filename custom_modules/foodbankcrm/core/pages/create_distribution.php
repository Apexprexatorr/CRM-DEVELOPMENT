<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distributionline.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/packageitem.class.php';

$langs->load("admin");
llxHeader();

$step = GETPOST('step', 'int') ?: 1;
$notice = '';

// ============================================
// STEP 3: PROCESS FINAL SUBMISSION
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_distribution'])) {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $db->begin();
        
        // Create distribution
        $distribution = new Distribution($db);
        $distribution->fk_beneficiary = GETPOST('fk_beneficiary', 'int');
        $distribution->fk_package = GETPOST('fk_package', 'int') ?: null;
        $distribution->fk_warehouse = GETPOST('fk_warehouse', 'int');
        $distribution->fk_user = $user->id;
        $distribution->note = GETPOST('note', 'restricthtml');
        $distribution->status = 'Prepared';
        
        $distribution_id = $distribution->create($user);
        
        if ($distribution_id > 0) {
            $items_added = 0;
            $items_failed = 0;
            
            // Add distribution lines
            if (!empty($_POST['item_donation']) && is_array($_POST['item_donation'])) {
                foreach ($_POST['item_donation'] as $index => $fk_donation) {
                    if (empty($fk_donation)) continue;
                    
                    $quantity = GETPOST('item_quantity', 'array')[$index];
                    if (empty($quantity) || $quantity <= 0) continue;
                    
                    $line = new DistributionLine($db);
                    $line->fk_distribution = $distribution_id;
                    $line->fk_donation = $fk_donation;
                    $line->product_name = GETPOST('item_product', 'array')[$index];
                    $line->quantity = $quantity;
                    $line->unit = GETPOST('item_unit', 'array')[$index];
                    
                    if ($line->create($user) > 0) {
                        $items_added++;
                    } else {
                        $items_failed++;
                    }
                }
            }
            
                     if ($items_added > 0) {
                          $db->commit();
    
                         // Store success message in session
                         $_SESSION['distribution_success'] = array(
                        'ref' => $distribution->ref,
                       'id' => $distribution_id,
                    'items_count' => $items_added,
                    'beneficiary' => $beneficiary->firstname.' '.$beneficiary->lastname
                      );
    
                    header('Location: view_distribution.php?id='.$distribution_id);
                    exit;
                }else {
                $db->rollback();
                $notice = '<div class="error">No items were added to the distribution. Please add at least one item.</div>';
                $step = 3; // Go back to item selection
            }
        } else {
            $db->rollback();
            $notice = '<div class="error">Error creating distribution: '.$distribution->error.'</div>';
        }
    }
}

print $notice;
print '<div><a href="distributions.php">← Back to Distributions</a></div><br>';

// ============================================
// STEP 1: SELECT BENEFICIARY & WAREHOUSE
// ============================================
if ($step == 1) {
    ?>
    <h2>Create Distribution - Step 1: Select Beneficiary</h2>
    <p style="color: #666; font-size: 13px;">Choose the beneficiary and warehouse for this distribution.</p>
    
    <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="step" value="2">
        
        <table class="border centpercent">
            <tr>
                <td width="30%"><span class="fieldrequired">Beneficiary</span></td>
                <td>
                    <select class="flat" name="fk_beneficiary" required style="width: 100%;">
                        <option value="">-- Select Beneficiary --</option>
                        <?php
                        $sql = "SELECT rowid, ref, firstname, lastname FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries ORDER BY ref";
                        $resql = $db->query($sql);
                        while ($obj = $db->fetch_object($resql)) {
                            echo '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->ref.' - '.$obj->firstname.' '.$obj->lastname).'</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><span class="fieldrequired">Warehouse</span></td>
                <td>
                    <select class="flat" name="fk_warehouse" required style="width: 100%;">
                        <option value="">-- Select Warehouse --</option>
                        <?php
                        $sql = "SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses ORDER BY ref";
                        $resql = $db->query($sql);
                        while ($obj = $db->fetch_object($resql)) {
                            echo '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->ref.' - '.$obj->label).'</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <br>
        <div class="center">
            <button type="submit" class="button">Next: Choose Package →</button>
            <a class="button" href="distributions.php">Cancel</a>
        </div>
    </form>
    <?php
}

// ============================================
// STEP 2: SELECT PACKAGE (OPTIONAL)
// ============================================
elseif ($step == 2) {
    $fk_beneficiary = GETPOST('fk_beneficiary', 'int');
    $fk_warehouse = GETPOST('fk_warehouse', 'int');
    
    // Get beneficiary name
    $sql = "SELECT ref, firstname, lastname FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid = ".$fk_beneficiary;
    $resql = $db->query($sql);
    $beneficiary = $db->fetch_object($resql);
    
    // Get warehouse name
    $sql = "SELECT ref, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid = ".$fk_warehouse;
    $resql = $db->query($sql);
    $warehouse = $db->fetch_object($resql);
    
    ?>
    <h2>Create Distribution - Step 2: Choose Package</h2>
    
    <div style="background: #e8f5e9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <strong>Beneficiary:</strong> <?php echo dol_escape_htmltag($beneficiary->firstname.' '.$beneficiary->lastname); ?><br>
        <strong>Warehouse:</strong> <?php echo dol_escape_htmltag($warehouse->label); ?>
    </div>
    
    <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="step" value="3">
        <input type="hidden" name="fk_beneficiary" value="<?php echo $fk_beneficiary; ?>">
        <input type="hidden" name="fk_warehouse" value="<?php echo $fk_warehouse; ?>">
        
        <p><strong>Would you like to use a package template?</strong></p>
        <p style="color: #666; font-size: 13px;">Package templates pre-fill the items list. You can adjust quantities in the next step.</p>
        
        <table class="border centpercent">
            <tr>
                <td width="30%">
                    <input type="radio" name="fk_package" value="0" checked> 
                    <strong>No template (custom distribution)</strong>
                </td>
                <td>
                    <span style="color: #999;">Manually select items one by one</span>
                </td>
            </tr>
            <?php
            $sql = "SELECT rowid, ref, name, description FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE status = 'Active' ORDER BY name";
            $resql = $db->query($sql);
            while ($obj = $db->fetch_object($resql)) {
                ?>
                <tr>
                    <td>
                        <input type="radio" name="fk_package" value="<?php echo $obj->rowid; ?>"> 
                        <strong><?php echo dol_escape_htmltag($obj->name); ?></strong>
                    </td>
                    <td>
                        <?php echo dol_escape_htmltag($obj->description); ?>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
        
        <br>
        <div class="center">
            <button type="submit" class="button">Next: Select Items →</button>
            <a class="button" href="distributions.php">Cancel</a>
        </div>
    </form>
    <?php
}

// ============================================
// STEP 3: SELECT ITEMS & MATCH DONATIONS
// ============================================
elseif ($step == 3) {
    $fk_beneficiary = GETPOST('fk_beneficiary', 'int');
    $fk_warehouse = GETPOST('fk_warehouse', 'int');
    $fk_package = GETPOST('fk_package', 'int');
    
    // Get beneficiary name
    $sql = "SELECT ref, firstname, lastname FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid = ".$fk_beneficiary;
    $resql = $db->query($sql);
    $beneficiary = $db->fetch_object($resql);
    
    // Get warehouse name
    $sql = "SELECT ref, label FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid = ".$fk_warehouse;
    $resql = $db->query($sql);
    $warehouse = $db->fetch_object($resql);
    
    // Get package items if package selected
    $package_items = array();
    $package_name = 'Custom Distribution';
    if ($fk_package > 0) {
        $package_items = PackageItem::getAllByPackage($db, $fk_package);
        $package = new Package($db);
        $package->fetch($fk_package);
        $package_name = $package->name;
    }
    
    ?>
    <h2>Create Distribution - Step 3: Select Items</h2>
    
    <div style="background: #e8f5e9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <strong>Beneficiary:</strong> <?php echo dol_escape_htmltag($beneficiary->firstname.' '.$beneficiary->lastname); ?><br>
        <strong>Warehouse:</strong> <?php echo dol_escape_htmltag($warehouse->label); ?><br>
        <strong>Package:</strong> <?php echo dol_escape_htmltag($package_name); ?>
    </div>
    
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="fk_beneficiary" value="<?php echo $fk_beneficiary; ?>">
        <input type="hidden" name="fk_warehouse" value="<?php echo $fk_warehouse; ?>">
        <input type="hidden" name="fk_package" value="<?php echo $fk_package; ?>">
        <input type="hidden" name="create_distribution" value="1">
        
        <div id="items-container">
            <table class="noborder centpercent">
                <tr class="liste_titre">
                    <th width="20%">Product</th>
                    <th width="10%">Need</th>
                    <th width="30%">Select Donation</th>
                    <th width="10%">Available</th>
                    <th width="10%">Allocate Qty</th>
                    <th width="10%">Unit</th>
                    <th width="10%">Action</th>
                </tr>
                
                <?php
                if (count($package_items) > 0) {
                    // Package selected - pre-fill items
                    foreach ($package_items as $index => $item) {
                        $available_donations = Distribution::getAvailableDonations($db, $item->product_name);
                        ?>
                        <tr class="item-row oddeven">
                            <td>
                                <input type="hidden" name="item_product[]" value="<?php echo dol_escape_htmltag($item->product_name); ?>">
                                <strong><?php echo dol_escape_htmltag($item->product_name); ?></strong>
                            </td>
                            <td><?php echo $item->quantity.' '.$item->unit; ?></td>
                            <td>
                                <select class="flat" name="item_donation[]" style="width: 100%;" onchange="updateAvailable(this, <?php echo $index; ?>)">
                                    <option value="">-- Select Donation --</option>
                                    <?php foreach ($available_donations as $donation): ?>
                                    <option value="<?php echo $donation['id']; ?>" 
                                            data-available="<?php echo $donation['available']; ?>"
                                            data-unit="<?php echo $donation['unit']; ?>">
                                        <?php echo dol_escape_htmltag($donation['ref'].' - '.$donation['label'].' ('.$donation['vendor_name'].')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <span id="available_<?php echo $index; ?>" style="font-weight: bold;">—</span>
                            </td>
                            <td>
                                <input type="number" class="flat" name="item_quantity[]" 
                                       value="<?php echo $item->quantity; ?>" 
                                       step="0.01" min="0" style="width: 90%;">
                            </td>
                            <td>
                                <input type="text" class="flat" name="item_unit[]" 
                                       value="<?php echo $item->unit; ?>" 
                                       style="width: 70%;">
                            </td>
                            <td>—</td>
                        </tr>
                        <?php
                    }
                } else {
                    // No package - show one empty row
                    ?>
                    <tr class="item-row oddeven">
                        <td>
                            <input type="text" class="flat" name="item_product[]" 
                                   placeholder="e.g., Rice" style="width: 100%;">
                        </td>
                        <td>—</td>
                        <td>
                            <select class="flat" name="item_donation[]" style="width: 100%;">
                                <option value="">-- Select Donation --</option>
                                <?php
                                $all_donations = Distribution::getAvailableDonations($db);
                                foreach ($all_donations as $donation) {
                                    echo '<option value="'.$donation['id'].'" data-available="'.$donation['available'].'" data-unit="'.$donation['unit'].'">'.
                                         dol_escape_htmltag($donation['ref'].' - '.$donation['label'].' ('.$donation['vendor_name'].')').'</option>';
                                }
                                ?>
                            </select>
                        </td>
                        <td>—</td>
                        <td><input type="number" class="flat" name="item_quantity[]" value="1" step="0.01" style="width: 90%;"></td>
                        <td><input type="text" class="flat" name="item_unit[]" value="kg" style="width: 70%;"></td>
                        <td><button type="button" class="button small" onclick="removeRow(this)">Remove</button></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>
        
        <?php if (count($package_items) == 0): ?>
        <br>
        <button type="button" class="button" onclick="addItemRow()">+ Add Another Item</button>
        <?php endif; ?>
        
        <br><br>
        <h3>Additional Notes (Optional)</h3>
        <textarea class="flat" name="note" rows="3" style="width: 100%;" placeholder="Any special instructions or notes..."></textarea>
        
        <br><br>
        <div class="center">
            <button type="submit" class="button" name="create_distribution">✅ Create Distribution</button>
            <a class="button" href="distributions.php">Cancel</a>
        </div>
    </form>
    
    <script>
    function updateAvailable(selectElement, index) {
        var selectedOption = selectElement.options[selectElement.selectedIndex];
        var available = selectedOption.getAttribute('data-available');
        var unit = selectedOption.getAttribute('data-unit');
        
        var availableSpan = document.getElementById('available_' + index);
        if (available) {
            availableSpan.textContent = available + ' ' + unit;
            availableSpan.style.color = parseFloat(available) > 0 ? 'green' : 'red';
        } else {
            availableSpan.textContent = '—';
            availableSpan.style.color = 'gray';
        }
    }
    
    function addItemRow() {
        // Clone the first row
        var container = document.getElementById('items-container').querySelector('table');
        var firstRow = container.querySelector('.item-row');
        var newRow = firstRow.cloneNode(true);
        
        // Clear values
        var inputs = newRow.querySelectorAll('input, select');
        inputs.forEach(function(input) {
            if (input.type === 'text' || input.type === 'number') {
                input.value = input.name.includes('quantity') ? '1' : '';
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            }
        });
        
        container.appendChild(newRow);
    }
    
    function removeRow(button) {
        var container = document.getElementById('items-container').querySelector('table');
        var rows = container.querySelectorAll('.item-row');
        
        if (rows.length > 1) {
            button.closest('.item-row').remove();
        } else {
            alert('You must keep at least one item.');
        }
    }
    </script>
    <?php
}

llxFooter();
?>