<?php
/**
 * Subscription Success Page
 */
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;
$langs->load("admin");

$tier_name = GETPOST('tier', 'alpha');
$end_date = GETPOST('end_date', 'alpha');

llxHeader('', 'Subscription Active');

print '<style>
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header { display: none !important; }
    html, body { background-color: #f8f9fa !important; margin: 0; width: 100%; overflow-x: hidden; }
    #id-right, .id-right { margin: 0 !important; width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
    .success-card { background: white; padding: 50px; border-radius: 12px; text-align: center; margin: 50px auto; max-width: 600px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .btn-dash { background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 30px; font-weight: bold; display: inline-block; }
</style>';

print '<div class="success-card">';
print '<div style="font-size: 80px; margin-bottom: 20px;">🎉</div>';
print '<h1 style="color: #28a745; margin: 0 0 10px 0;">Subscription Activated!</h1>';
print '<p style="color: #666; font-size: 18px;">You are now subscribed to the <strong>'.dol_escape_htmltag($tier_name).'</strong> plan.</p>';
if ($end_date) {
    print '<p>Valid until: <strong>'.date('M d, Y', strtotime($end_date)).'</strong></p>';
}
print '<br><a href="dashboard_beneficiary.php" class="btn-dash">Go to Dashboard</a>';
print '</div>';

llxFooter();
?>