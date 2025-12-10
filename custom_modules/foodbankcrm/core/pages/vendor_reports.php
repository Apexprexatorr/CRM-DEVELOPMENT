<?php
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

$langs->load("admin");

// 1. SECURITY CHECK
$user_is_vendor = FoodbankPermissions::isVendor($user, $db);
if (!$user_is_vendor) {
    accessforbidden('You do not have access to vendor reports.');
}

// Get Vendor ID
$sql = "SELECT rowid, name FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
$vendor = $db->fetch_object($res);
$vendor_id = $vendor->rowid;

// 2. FILTER LOGIC
$year = GETPOST('year', 'int') ? GETPOST('year', 'int') : date('Y');
$month = GETPOST('month', 'int') ? GETPOST('month', 'int') : 0; // 0 = All Year

llxHeader('', 'My Impact Report');

// 3. UPDATED CSS WITH PRINT RULES
echo '<style>
    /* SCREEN STYLES */
    #id-left { display: none !important; }
    #id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .fiche { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    body { background: #f8f9fa !important; }
    .report-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; break-inside: avoid; }
    .bar-container { background-color: #f1f1f1; border-radius: 5px; height: 10px; width: 100%; margin-top: 5px; }
    .bar-fill { height: 10px; border-radius: 5px; transition: width 0.5s; }

    /* PRINT SPECIFIC STYLES */
    @media print {
        /* Hide Dolibarr Navigation and Buttons */
        #id-top, .login_block, .side-nav, .tmenu, #tmenu_tooltip, .butAction, form {
            display: none !important;
        }

        /* Reset Layout for Paper */
        body, #id-right, #id-main, .fiche {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            box-shadow: none !important;
        }

        /* Container Adjustment */
        .main-container {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }

        /* Force Colors to Print (Chrome/Edge/Safari) */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Remove Shadows for cleaner print */
        .report-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            page-break-inside: avoid;
        }
        
        /* Adjust Font Sizes */
        h1 { font-size: 24px !important; }
        .report-card div { font-size: 12pt; }
        
        /* Ensure Grid works on paper */
        .grid-stats {
            display: grid !important;
            grid-template-columns: 1fr 1fr 1fr 1fr !important;
            gap: 10px !important;
        }
        .grid-split {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 20px !important;
        }
    }
</style>';

print '<div class="main-container" style="width: 100%; padding: 30px; box-sizing: border-box; max-width: 1200px; margin: 0 auto;">';

// HEADER
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
print '<div>';
print '<h1 style="margin: 0;">📊 Impact Report</h1>';
print '<p style="color: #666; margin: 5px 0 0 0;">Report for: <strong>'.dol_escape_htmltag($vendor->name).'</strong></p>';
if ($month > 0) {
    print '<p style="color: #666; margin: 0; font-size: 13px;">Period: '.date("F", mktime(0, 0, 0, $month, 10)).' '.$year.'</p>';
} else {
    print '<p style="color: #666; margin: 0; font-size: 13px;">Period: Full Year '.$year.'</p>';
}
print '</div>';
print '<a href="dashboard_vendor.php" class="butAction">← Back to Dashboard</a>';
print '</div>';

// FILTER FORM (Hidden on Print)
print '<div class="report-card" style="display: flex; gap: 20px; align-items: center;">';
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" style="display: flex; gap: 15px; align-items: center; width: 100%;">';
print '<strong style="font-size: 16px;">📅 Filter By:</strong>';

// Year Dropdown
print '<select name="year" class="flat" style="padding: 10px; font-size: 15px; border-radius: 5px; border: 1px solid #ddd;">';
$current_year = date('Y');
for ($i = $current_year; $i >= $current_year - 5; $i--) {
    $selected = ($year == $i) ? 'selected' : '';
    print '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
}
print '</select>';

// Month Dropdown
print '<select name="month" class="flat" style="padding: 10px; font-size: 15px; border-radius: 5px; border: 1px solid #ddd;">';
print '<option value="0">All Months</option>';
for ($m = 1; $m <= 12; $m++) {
    $selected = ($month == $m) ? 'selected' : '';
    print '<option value="'.$m.'" '.$selected.'>'.date("F", mktime(0, 0, 0, $m, 10)).'</option>';
}
print '</select>';

print '<button type="submit" class="butAction" style="padding: 10px 20px;">Generate Report</button>';
print '</form>';
print '</div>';

// SQL BASE FILTER
$sql_filter = " AND YEAR(date_donation) = ".(int)$year;
if ($month > 0) {
    $sql_filter .= " AND MONTH(date_donation) = ".(int)$month;
}

// STATS CALCULATION
$sql_stats = "SELECT 
    COUNT(*) as total_count,
    SUM(quantity) as total_qty,
    SUM(quantity * unit_price) as total_value,
    COUNT(DISTINCT fk_warehouse) as warehouses_reached
    FROM ".MAIN_DB_PREFIX."foodbank_donations
    WHERE fk_vendor = ".(int)$vendor_id . $sql_filter;

$res_stats = $db->query($sql_stats);
$stats = $db->fetch_object($res_stats);

// TOP STATS CARDS
print '<div class="grid-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">';

// Total Value
print '<div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); -webkit-print-color-adjust: exact;">';
print '<div style="font-size: 14px; opacity: 0.9;">Total Value Donated</div>';
print '<div style="font-size: 36px; font-weight: bold;">₦'.number_format($stats->total_value ?? 0, 0).'</div>';
print '</div>';

// Total Items
print '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); -webkit-print-color-adjust: exact;">';
print '<div style="font-size: 14px; opacity: 0.9;">Total Quantity</div>';
print '<div style="font-size: 36px; font-weight: bold;">'.number_format($stats->total_qty ?? 0).' <span style="font-size: 16px;">Units</span></div>';
print '</div>';

// Total Donations
print '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); -webkit-print-color-adjust: exact;">';
print '<div style="font-size: 14px; opacity: 0.9;">Donations Made</div>';
print '<div style="font-size: 36px; font-weight: bold;">'.number_format($stats->total_count ?? 0).'</div>';
print '</div>';

print '</div>';

// GRID FOR TABLES
print '<div class="grid-split" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">';

// 1. BREAKDOWN BY CATEGORY
print '<div class="report-card">';
print '<h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px;">📦 Donations by Category</h2>';

$sql_cat = "SELECT category, COUNT(*) as count, SUM(quantity) as qty, SUM(quantity * unit_price) as val 
            FROM ".MAIN_DB_PREFIX."foodbank_donations 
            WHERE fk_vendor = ".(int)$vendor_id . $sql_filter . "
            GROUP BY category 
            ORDER BY val DESC";
$res_cat = $db->query($sql_cat);

if ($db->num_rows($res_cat) > 0) {
    print '<table style="width: 100%; border-collapse: collapse;">';
    print '<thead><tr style="color: #666; font-size: 13px; text-align: left;"><th>Category</th><th style="text-align:right">Value</th><th style="text-align:right">%</th></tr></thead>';
    print '<tbody>';
    
    $max_val = ($stats->total_value > 0) ? $stats->total_value : 1;

    while ($cat = $db->fetch_object($res_cat)) {
        $percent = ($cat->val / $max_val) * 100;
        print '<tr>';
        print '<td style="padding: 12px 0; border-bottom: 1px solid #f0f0f0;">';
        print '<strong>'.dol_escape_htmltag($cat->category).'</strong><br>';
        print '<span style="font-size: 12px; color: #888;">'.number_format($cat->qty).' units</span>';
        print '</td>';
        print '<td style="padding: 12px 0; border-bottom: 1px solid #f0f0f0; text-align: right; font-weight: bold;">₦'.number_format($cat->val, 0).'</td>';
        print '<td style="padding: 12px 0 12px 15px; border-bottom: 1px solid #f0f0f0; width: 60px;">';
        print '<div class="bar-container"><div class="bar-fill" style="width: '.$percent.'%; background-color: #667eea; -webkit-print-color-adjust: exact;"></div></div>';
        print '</td>';
        print '</tr>';
    }
    print '</tbody></table>';
} else {
    print '<p style="color: #666;">No data available for this period.</p>';
}
print '</div>';

// 2. RECENT ACTIVITY LOG
print '<div class="report-card">';
print '<h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px;">🕒 Recent Activity</h2>';

$sql_log = "SELECT ref, date_donation, status, total_value 
            FROM ".MAIN_DB_PREFIX."foodbank_donations 
            WHERE fk_vendor = ".(int)$vendor_id . $sql_filter . "
            ORDER BY date_donation DESC LIMIT 10";
$res_log = $db->query($sql_log);

if ($db->num_rows($res_log) > 0) {
    print '<table style="width: 100%; border-collapse: collapse;">';
    while ($log = $db->fetch_object($res_log)) {
        $status_color = ($log->status == 'Received') ? '#28a745' : '#ffc107';
        
        print '<tr style="border-bottom: 1px solid #f0f0f0;">';
        print '<td style="padding: 12px 0;">';
        print '<strong>'.dol_escape_htmltag($log->ref).'</strong><br>';
        print '<span style="font-size: 12px; color: #888;">'.dol_print_date($db->jdate($log->date_donation), 'day').'</span>';
        print '</td>';
        print '<td style="padding: 12px 0; text-align: right;">';
        print '<div style="font-weight: bold;">₦'.number_format($log->total_value, 0).'</div>';
        print '<span style="font-size: 11px; color: white; background: '.$status_color.'; padding: 2px 8px; border-radius: 10px; -webkit-print-color-adjust: exact;">'.$log->status.'</span>';
        print '</td>';
        print '</tr>';
    }
    print '</table>';
    
    // Hide "View All" on print
    print '<div style="margin-top: 20px; text-align: center;" class="butAction">';
    print '<a href="my_donations.php" style="color: white; text-decoration: none;">View All Transactions</a>';
    print '</div>';
} else {
    print '<p style="color: #666;">No activity found.</p>';
}
print '</div>';

print '</div>'; // End Grid

// EXPORT BUTTON (Hidden on Print)
print '<div style="text-align: center; margin-top: 20px;">';
print '<button class="butAction" onclick="window.print()">🖨️ Print Report</button>';
print '</div>';

print '</div>'; // End Main Container

llxFooter();
?>