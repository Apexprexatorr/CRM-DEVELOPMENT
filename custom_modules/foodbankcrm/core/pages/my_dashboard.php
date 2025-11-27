<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

// Check if user is vendor
if (FoodbankPermissions::isVendor($user, $db)) {
    header('Location: dashboard_vendor.php');
    exit;
}

// Check if user is beneficiary
if (FoodbankPermissions::isBeneficiary($user, $db)) {
    header('Location: dashboard_beneficiary.php');
    exit;
}

// Default: show admin or regular dashboard
header('Location: /index.php');
exit;
?>
