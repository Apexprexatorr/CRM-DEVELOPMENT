<?php
/**
 * Activate Subscription - DEBUG MODE
 * This will reveal exactly why Paystack is rejecting the reference.
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
ob_start();

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

ob_clean();
header('Content-Type: application/json');

global $user, $db;

// 1. GET DATA
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['reference'])) {
    echo json_encode(['status' => 'error', 'message' => 'No reference received from browser']);
    exit;
}
$reference = $input['reference'];
$tier_type = $db->escape($input['tier']);
$subscriber_id = (int)$input['subscriber_id'];

// 2. VERIFY WITH PAYSTACK
// Using the key you provided (Confirmed Valid format)
$paystack_secret_key = trim('sk_test_24845eca974e163568aa6dd497590551e1ad2260'); 

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false, // Temporary: Bypass local SSL issues
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $paystack_secret_key,
        "Cache-Control: no-cache",
    ],
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['status' => 'error', 'message' => 'Curl Connection Error: '.$err]);
    exit;
}

$result = json_decode($response);

// 3. CHECK FOR FAILURE
if (!$result || !isset($result->data) || $result->data->status !== 'success') {
    // 🔴 THIS IS WHERE WE SEND THE RAW PAYSTACK ERROR BACK TO YOU 🔴
    $paystack_msg = isset($result->message) ? $result->message : 'No message from Paystack';
    echo json_encode([
        'status' => 'error', 
        'message' => 'Paystack says: ' . $paystack_msg . ' | Ref: ' . $reference
    ]);
    exit;
}

// 4. SUCCESS - UPDATE DB
$sql_tier = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE tier_type = '".$tier_type."'";
$res_tier = $db->query($sql_tier);
$tier = $db->fetch_object($res_tier);

if (!$tier) {
    echo json_encode(['status' => 'error', 'message' => 'Internal Error: Invalid Tier Code']);
    exit;
}

$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+' . $tier->duration_days . ' days'));
$amount_paid = $result->data->amount / 100;

$db->begin();
try {
    $sql_update = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET
                   subscription_type = '".$tier_type."',
                   subscription_status = 'Active',
                   subscription_start_date = '".$db->escape($start_date)."',
                   subscription_end_date = '".$db->escape($end_date)."',
                   subscription_fee = ".(float)$tier->price.",
                   payment_method = 'Paystack',
                   last_payment_date = NOW()
                   WHERE rowid = ".$subscriber_id;
    
    if (!$db->query($sql_update)) throw new Exception('DB Update Failed');
    
    // Check for duplicate payment record to prevent double entry
    $sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_payments WHERE payment_reference = '".$db->escape($reference)."'";
    if ($db->num_rows($db->query($sql_check)) == 0) {
        $sql_payment = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments 
                        (fk_subscriber, payment_type, amount, payment_method, payment_status, 
                         payment_reference, payment_date, datec)
                        VALUES (
                            ".$subscriber_id.",
                            'Subscription',
                            ".(float)$amount_paid.",
                            'Paystack',
                            'Success',
                            '".$db->escape($reference)."',
                            NOW(),
                            NOW()
                        )";
        if (!$db->query($sql_payment)) throw new Exception('Payment Record Failed');
    }
    
    $db->commit();
    echo json_encode(['status' => 'success', 'end_date' => $end_date]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
?>