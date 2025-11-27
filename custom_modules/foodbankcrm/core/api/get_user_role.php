<?php
/**
 * API endpoint to return user role and redirect URL
 */

define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

global $user, $db;

$response = array();

if (empty($user->id)) {
    echo json_encode(array('error' => 'Not logged in', 'role' => 'none', 'redirect_url' => null));
    exit;
}

// Check if already on a custom dashboard - don't redirect
$current_url = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($current_url, '/custom/foodbankcrm/core/pages/dashboard_') !== false) {
    echo json_encode(array('role' => 'on_custom_dashboard', 'redirect_url' => null));
    exit;
}

// Check user role and set redirect URL
if (FoodbankPermissions::isAdmin($user)) {
    $response['role'] = 'admin';
    $response['redirect_url'] = null; // Admins stay on default dashboard
} elseif (FoodbankPermissions::isVendor($user, $db)) {
    $response['role'] = 'vendor';
    $response['redirect_url'] = '/custom/foodbankcrm/core/pages/dashboard_vendor.php';
} elseif (FoodbankPermissions::isBeneficiary($user, $db)) {
    $response['role'] = 'beneficiary';
    $response['redirect_url'] = '/custom/foodbankcrm/core/pages/dashboard_beneficiary.php';
} else {
    $response['role'] = 'none';
    $response['redirect_url'] = null;
}

echo json_encode($response);
?>

