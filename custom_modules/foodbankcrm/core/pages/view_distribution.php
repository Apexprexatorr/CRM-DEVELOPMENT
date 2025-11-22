<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distribution.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/distributionline.class.php';

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Distribution ID is missing.</div>';
    print '<div><a href="distributions.php">← Back to Distributions</a></div>';
    llxFooter(); exit;
}

$distribution_id = (int) $_GET['id'];
$distribution = new Distribution($db);
$distribution->fetch($distribution_id);

// Check for success message in session
$success_msg = '';
if (isset($_SESSION['distribution_success']) && $_SESSION['distribution_success']['id'] == $distribution_id) {
    $success = $_SESSION['distribution_success'];
    $success_msg = '<div class="ok" style="padding: 20px; margin-bottom: 20px; background: #e8f5e9; border: 3px solid #4caf50; border-radius: 5px;">';
    $success_msg .= '<h3 style="margin-top: 0; color: #2e7d32;">✅ Distribution Created Successfully!</h3>';
    $success_msg .= '<p style="font-size: 15px; margin: 10px 0;"><strong>Reference:</strong> '.$success['ref'].'</p>';
    $success_msg .= '<p style="font-size: 15px; margin: 10px 0;"><strong>Beneficiary:</strong> '.dol_escape_htmltag($success['beneficiary']).'</p>';
    $success_msg .= '<p style="font-size: 15px; margin: 10px 0;"><strong>Items Allocated:</strong> '.$success['items_count'].' items</p>';
    $success_msg .= '<p style="color: #2e7d32; font-weight: bold; margin-top: 15px;">🎉 The distribution has been prepared and is ready for delivery!</p>';
    $success_msg .= '</div>';
    
    unset($_SESSION['distribution_success']);
}

// Get beneficiary details
$sql = "SELECT ref, firstname, lastname, phone, email FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE rowid = ".$distribution->fk_beneficiary;
$resql = $db->query($sql);
$beneficiary = $db->fetch_object($resql);

// Get warehouse details
$sql = "SELECT ref, label, address FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid = ".$distribution->fk_warehouse;
$resql = $db->query($sql);
$warehouse = $db->fetch_object($resql);

// Get package details if used
$package_name = '—';
if ($distribution->fk_package) {
    $sql = "SELECT ref, name FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE rowid = ".$distribution->fk_package;
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $package = $db->fetch_object($resql);
        $package_name = $package->ref.' - '.$package->name;
    }
}

// Get distribution lines
$lines = DistributionLine::getAllByDistribution($db, $distribution_id);

// Calculate totals
$total_items = count($lines);
$unique_vendors = array();
foreach ($lines as $line) {
    if (!empty($line->vendor_name)) {
        $unique_vendors[$line->vendor_name] = true;
    }
}
$vendor_count = count($unique_vendors);

print '<div><a href="distributions.php">← Back to Distributions</a></div><br>';

// Print success message
print $success_msg;
?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Distribution: <?php echo dol_escape_htmltag($distribution->ref); ?></h2>
    <div>
        <a class="butAction" href="edit_distribution.php?id=<?php echo $distribution_id; ?>">Edit Distribution</a>
        <a class="butActionDelete" href="delete_distribution.php?id=<?php echo $distribution_id; ?>">Delete Distribution</a>
    </div>
</div>

<table class="border centpercent">
    <tr>
        <td width="25%"><strong>Ref</strong></td>
        <td><?php echo dol_escape_htmltag($distribution->ref); ?></td>
    </tr>
    <tr>
        <td><strong>Beneficiary</strong></td>
        <td>
            <strong><?php echo dol_escape_htmltag($beneficiary->firstname.' '.$beneficiary->lastname); ?></strong><br>
            <span style="color: #666;">
                <?php echo dol_escape_htmltag($beneficiary->ref); ?>
                <?php if ($beneficiary->phone): ?> | Phone: <?php echo dol_escape_htmltag($beneficiary->phone); ?><?php endif; ?>
            </span>
        </td>
    </tr>
    <tr>
        <td><strong>Warehouse</strong></td>
        <td>
            <?php echo dol_escape_htmltag($warehouse->label); ?><br>
            <span style="color: #666;"><?php echo dol_escape_htmltag($warehouse->ref); ?></span>
        </td>
    </tr>
    <tr>
        <td><strong>Package Used</strong></td>
        <td><?php echo dol_escape_htmltag($package_name); ?></td>
    </tr>
    <tr>
        <td><strong>Date</strong></td>
        <td><?php echo dol_print_date($db->jdate($distribution->date_distribution), 'dayhour'); ?></td>
    </tr>
    <tr>
        <td><strong>Status</strong></td>
        <td>
            <?php
            $status_color = '#4caf50';
            $status_bg = '#e8f5e9';
            if ($distribution->status == 'Delivered') {
                $status_color = '#2196f3';
                $status_bg = '#e3f2fd';
            } elseif ($distribution->status == 'Completed') {
                $status_color = '#9c27b0';
                $status_bg = '#f3e5f5';
            }
            ?>
            <span style="display:inline-block; padding:5px 12px; border-radius:4px; background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>; font-weight:bold;">
                <?php echo dol_escape_htmltag($distribution->status); ?>
            </span>
        </td>
    </tr>
    <?php if (!empty($distribution->note)): ?>
    <tr>
        <td><strong>Notes</strong></td>
        <td><?php echo dol_escape_htmltag($distribution->note); ?></td>
    </tr>
    <?php endif; ?>
</table>

<br>
<h3>📦 Items in This Distribution (<?php echo $total_items; ?> items from <?php echo $vendor_count; ?> vendor<?php echo $vendor_count != 1 ? 's' : ''; ?>)</h3>

<?php if (count($lines) > 0): ?>
<table class="noborder centpercent">
    <tr class="liste_titre">
        <th>Product</th>
        <th>Quantity</th>
        <th>Unit</th>
        <th>From Donation</th>
        <th>Vendor</th>
    </tr>
    <?php foreach ($lines as $line): ?>
    <tr class="oddeven">
        <td><strong><?php echo dol_escape_htmltag($line->product_name); ?></strong></td>
        <td><?php echo dol_escape_htmltag($line->quantity); ?></td>
        <td><?php echo dol_escape_htmltag($line->unit); ?></td>
        <td>
            <a href="view_donation.php?id=<?php echo $line->fk_donation; ?>">
                <?php echo dol_escape_htmltag($line->donation_ref); ?>
            </a>
            <?php if ($line->donation_label): ?>
                <br><span style="color: #666; font-size: 11px;"><?php echo dol_escape_htmltag($line->donation_label); ?></span>
            <?php endif; ?>
        </td>
        <td>
            <?php 
            if ($line->vendor_name) {
                echo dol_escape_htmltag($line->vendor_name);
            } else {
                echo '<span style="color: #999;">Unknown vendor</span>';
            }
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
    
    <tr style="background: #f9f9f9; font-weight: bold;">
        <td colspan="5" style="text-align: right; padding: 12px;">
            <strong>Total: <?php echo $total_items; ?> items from <?php echo $vendor_count; ?> vendor<?php echo $vendor_count != 1 ? 's' : ''; ?></strong>
        </td>
    </tr>
</table>

<br>
<div style="background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid #2196f3;">
    <h4 style="margin-top: 0;">📊 Summary</h4>
    <ul style="margin: 0; padding-left: 20px;">
        <li><strong><?php echo $total_items; ?></strong> different products allocated</li>
        <li><strong><?php echo $vendor_count; ?></strong> vendor<?php echo $vendor_count != 1 ? 's' : ''; ?> contributed to this distribution</li>
        <li>Status: <strong><?php echo dol_escape_htmltag($distribution->status); ?></strong></li>
    </ul>
</div>

<?php else: ?>
<div class="warning" style="padding: 20px; text-align: center;">
    <p>⚠ This distribution has no items. <a href="edit_distribution.php?id=<?php echo $distribution_id; ?>">Add items now</a></p>
</div>
<?php endif; ?>

<br>
<div class="center">
    <a class="butAction" href="distributions.php">← Back to List</a>
    <?php if ($distribution->status == 'Prepared'): ?>
    <a class="butAction" href="mark_delivered.php?id=<?php echo $distribution_id; ?>">📦 Mark as Delivered</a>
    <?php endif; ?>
</div>

<?php llxFooter(); ?>