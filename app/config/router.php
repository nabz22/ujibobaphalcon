<?php

// Ambil router dari DI
/** @var \Phalcon\Mvc\Router $router */
$router = $di->getRouter();

// ===== MAIN ROUTES =====

// Halaman landing ("/") -> redirect ke notes.html
$router->addGet(
    '/',
    [
        'controller' => 'index',
        'action'     => 'index',
    ]
);

// Dashboard -> redirect ke notes.html
$router->addGet(
    '/dashboard',
    [
        'controller' => 'dashboard',
        'action'     => 'index',
    ]
);

// Logout
$router->addGet(
    '/logout',
    [
        'controller' => 'index',
        'action'     => 'logout',
    ]
);

// ===== NOTES API =====

// List catatan (JSON)
$router->addGet(
    '/notes/list',
    [
        'controller' => 'notes',
        'action'     => 'list',
    ]
);

// Tambah catatan (JSON)
$router->addPost(
    '/notes/add',
    [
        'controller' => 'notes',
        'action'     => 'add',
    ]
);

// Hapus catatan
$router->addGet(
    '/notes/delete/{id:[0-9]+}',
    [
        'controller' => 'notes',
        'action'     => 'delete',
    ]
);

// ===== API NOTES (JSON) =====
$router->addGet('/api/notes', [
    'controller' => 'api',
    'action'     => 'notes',
]);

$router->addPost('/api/notes/create', [
    'controller' => 'api',
    'action'     => 'notesCreate',
]);

$router->addGet('/api/notes/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'notesGet',
]);

$router->addPost('/api/notes/update/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'notesUpdate',
]);

$router->addDelete('/api/notes/delete/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'notesDelete',
]);

// Health check
$router->addGet('/api/health', [
    'controller' => 'api',
    'action'     => 'health',
]);

// ===== PRODUCT CRUD (LOCAL INVENTORY) =====
// NOTE: Order matters! More specific routes first

// Sync from Odoo (must be before /{id})
$router->addGet('/api/inventory/products/sync-odoo', [
    'controller' => 'api',
    'action'     => 'inventoryProductsSyncOdoo',
]);

// Delete product
$router->addGet('/api/inventory/products/delete/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'inventoryProductsDelete',
]);

// Create product
$router->addPost('/api/inventory/products/create', [
    'controller' => 'api',
    'action'     => 'inventoryProductsCreate',
]);

// Update product
$router->addPost('/api/inventory/products/update/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'inventoryProductsUpdate',
]);

// Get all products
$router->addGet('/api/inventory/products', [
    'controller' => 'api',
    'action'     => 'inventoryProducts',
]);

// Get single product (must be last for /api/inventory/products/*)
// Use regex constraint to only match numeric IDs
$router->addGet('/api/inventory/products/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'inventoryProductsGet',
]);

// ===== COMMERCE ROUTES (Sales, Purchase, Invoice) =====

// SALES ORDERS
$router->addGet('/api/commerce/sales', [
    'controller' => 'api',
    'action'     => 'commerceSalesList',
]);

$router->addPost('/api/commerce/sales/create', [
    'controller' => 'api',
    'action'     => 'commerceSalesCreate',
]);

$router->addPost('/api/commerce/sales/update/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'commerceSalesUpdate',
]);

$router->addGet('/api/commerce/sales/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'commerceSalesGet',
]);

// PURCHASE ORDERS
$router->addGet('/api/commerce/purchase', [
    'controller' => 'api',
    'action'     => 'commercePurchaseList',
]);

$router->addPost('/api/commerce/purchase/create', [
    'controller' => 'api',
    'action'     => 'commercePurchaseCreate',
]);

$router->addPost('/api/commerce/purchase/update/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'commercePurchaseUpdate',
]);

$router->addPost('/api/commerce/purchase/confirm/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'commercePurchaseConfirm',
]);

$router->addGet('/api/commerce/purchase/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'commercePurchaseGet',
]);

// INVOICES
$router->addGet('/api/commerce/invoices', [
    'controller' => 'api',
    'action'     => 'commerceInvoicesList',
]);

$router->addPost('/api/commerce/invoices/create', [
    'controller' => 'api',
    'action'     => 'commerceInvoicesCreate',
]);

$router->addPost('/api/commerce/invoices/confirm/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'commerceInvoicesConfirm',
]);

$router->addPost('/api/commerce/invoices/update/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'commerceInvoicesUpdate',
]);

$router->addGet('/api/commerce/invoices/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'commerceInvoicesGet',
]);

// INVENTORY MOVEMENTS
$router->addGet('/api/commerce/inventory-movements', [
    'controller' => 'api',
    'action'     => 'commerceInventoryMovements',
]);

// ===== CUSTOMERS ROUTES =====

$router->addGet('/customers', [
    'controller' => 'customers',
    'action'     => 'index',
]);

$router->addGet('/api/customers', [
    'controller' => 'api',
    'action'     => 'customers',
]);

$router->addPost('/api/customers/add', [
    'controller' => 'api',
    'action'     => 'customersAdd',
]);

$router->addPost('/api/customers/delete', [
    'controller' => 'api',
    'action'     => 'customersDelete',
]);

// ===== ODOO INTEGRATION (Optional) =====

$router->addGet('/api/odoo/test', [
    'controller' => 'api',
    'action'     => 'odooTest',
]);

$router->addGet('/api/odoo/partners', [
    'controller' => 'api',
    'action'     => 'odooPartners',
]);

$router->addGet('/api/odoo/products', [
    'controller' => 'api',
    'action'     => 'products',
]);

$router->addGet('/api/odoo/dashboard', [
    'controller' => 'api',
    'action'     => 'dashboard',
]);

$router->addGet('/api/odoo/customers', [
    'controller' => 'api',
    'action'     => 'customers',
]);

$router->addPost('/api/odoo/customers', [
    'controller' => 'api',
    'action'     => 'customersCreate',
]);

$router->addDelete('/api/odoo/customers/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'customersDelete',
]);

// ===== VENDORS ROUTES =====

$router->addGet('/vendors', [
    'controller' => 'vendors',
    'action'     => 'index',
]);

$router->addGet('/api/odoo/vendors', [
    'controller' => 'api',
    'action'     => 'vendors',
]);

$router->addPost('/api/odoo/vendors', [
    'controller' => 'api',
    'action'     => 'vendorsCreate',
]);

$router->addDelete('/api/odoo/vendors/{id:[0-9]+}', [
    'controller' => 'api',
    'action'     => 'vendorsDelete',
]);

// Kembalikan instance router ke DI
return $router;
