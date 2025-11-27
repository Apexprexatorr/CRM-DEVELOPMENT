/**
 * Auto-redirect users from default Dolibarr dashboard to custom dashboards
 * Runs on EVERY page to ensure redirect happens
 */
(function() {
    // Pages where we should redirect from
    const redirectPages = [
        '/index.php',
        '/',
        '/comm/index.php',
        '/comm/action/index.php'
    ];
    
    const currentPath = window.location.pathname;
    
    // Check if we're on a page that should trigger redirect
    const shouldRedirect = redirectPages.some(page => currentPath.endsWith(page) || currentPath === page);
    
    if (shouldRedirect) {
        console.log('Foodbank CRM: Checking for redirect...');
        
        // Make AJAX call to check user role
        fetch('/custom/foodbankcrm/core/api/get_user_role.php', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Foodbank CRM: User role data:', data);
            
            if (data.redirect_url && data.redirect_url !== '') {
                console.log('Foodbank CRM: Redirecting to', data.redirect_url);
                // Redirect immediately
                window.location.replace(data.redirect_url);
            } else {
                console.log('Foodbank CRM: No redirect needed');
            }
        })
        .catch(error => {
            console.log('Foodbank CRM: Error checking user role', error);
        });
    }
    
    // ALSO: Hide menu items that user shouldn't see
    hideUnauthorizedMenus();
})();

/**
 * Hide menu items based on user role
 */
function hideUnauthorizedMenus() {
    // Make AJAX call to get user role
    fetch('/custom/foodbankcrm/core/api/get_user_role.php', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        const userRole = data.role;
        
        if (!userRole || userRole === 'none') {
            return; // Admin or no role - show everything
        }
        
        console.log('Foodbank CRM: Hiding menus for role:', userRole);
        
        // Get all left menu items
        const leftMenu = document.querySelector('.side-nav-menu') || document.querySelector('#id-left');
        
        if (!leftMenu) {
            console.log('Foodbank CRM: Left menu not found');
            return;
        }
        
        // Define which menus to hide for each role
        const menusToHide = {
            'vendor': [
                'Beneficiaries',
                'Vendors',
                'Donations',
                'Packages',
                'Distributions',
                'Warehouses',
                'Subscription Tiers',
                'User Management',
                'Product Catalog',
                'My Orders',
                'Available Packages'
            ],
            'beneficiary': [
                'Beneficiaries',
                'Vendors',
                'Donations',
                'Packages',
                'Distributions',
                'Warehouses',
                'Subscription Tiers',
                'User Management',
                'Product Catalog',
                'My Donations',
                'Submit Donation'
            ]
        };
        
        const hideList = menusToHide[userRole] || [];
        
        // Find and hide menu items
        const menuLinks = leftMenu.querySelectorAll('a.vmenu, a.vmenudisabled');
        
        menuLinks.forEach(link => {
            const menuText = link.textContent.trim();
            
            if (hideList.includes(menuText)) {
                // Hide the entire menu item (including parent li if exists)
                const menuItem = link.closest('div') || link.closest('li') || link;
                menuItem.style.display = 'none';
                console.log('Foodbank CRM: Hiding menu:', menuText);
            }
        });
    })
    .catch(error => {
        console.log('Foodbank CRM: Error hiding menus', error);
    });
}
