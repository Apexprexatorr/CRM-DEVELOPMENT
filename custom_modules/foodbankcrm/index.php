<?php
require_once dirname(__DIR__, 2) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Determine user role and redirect to appropriate dashboard
if (FoodbankPermissions::isAdmin($user)) {
    // Admin - redirect to admin dashboard
    header('Location: /custom/foodbankcrm/core/pages/dashboard_admin.php');
    exit;
} elseif (FoodbankPermissions::isVendor($user, $db)) {
    // Vendor - redirect to vendor dashboard
    header('Location: /custom/foodbankcrm/core/pages/dashboard_vendor.php');
    exit;
} elseif (FoodbankPermissions::isBeneficiary($user, $db)) {
    // Beneficiary - redirect to beneficiary dashboard
    header('Location: /custom/foodbankcrm/core/pages/dashboard_beneficiary.php');
    exit;
} else {
    // No permissions - show error
    llxHeader();
    
    print '<div style="text-align: center; padding: 60px 20px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">🔒</div>';
    print '<h2 style="color: #dc3545;">Access Denied</h2>';
    print '<p style="color: #666; font-size: 16px;">You do not have permission to access the Foodbank CRM system.</p>';
    print '<p style="color: #666;">Current role: '.FoodbankPermissions::getUserRole($user, $db).'</p>';
    print '<p style="color: #666;">Please contact your administrator to request access.</p>';
    print '<br>';
    print '<a href="/" class="button">Return to Home</a>';
    print '</div>';
    
    llxFooter();
}
?>