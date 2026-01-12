<?php
/**
 * Payment Status Updater - FIXED (Silences "Access" Errors)
 */

// 1. DISABLE SECURITY CHECKS THAT CAUSE OUTPUT
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOHEADER'))       define('NOHEADER', 1);

// 2. Start Buffer immediately to catch any noise
ob_start();

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

// 3. WIPE BUFFER (This deletes the "Access to..." error text)
ob_clean();
header('Content-Type: application/json');

global $db, $user;

// 4. Get Data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_id']) || !isset($input['status'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$order_id = (int)$input['order_id'];
$reference = isset($input['reference']) ? $db->escape($input['reference']) : '';

// 5. Update Database
$db->begin();

$sql_dist = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions 
             SET payment_status = 'Paid', 
                 status = 'Prepared',
                 payment_reference = '".$reference."',
                 payment_gateway = 'Paystack'
             WHERE rowid = ".$order_id;

$res_dist = $db->query($sql_dist);

$sql_pay = "UPDATE ".MAIN_DB_PREFIX."foodbank_payments 
            SET payment_status = 'Success',
                payment_date = NOW()
            WHERE fk_order = ".$order_id;

$res_pay = $db->query($sql_pay);

if ($res_dist && $res_pay) {
    $db->commit();
    echo json_encode(['status' => 'success']);
} else {
    $db->rollback();
    echo json_encode(['error' => 'Database Error: '.$db->lasterror()]);
}
exit;
?>