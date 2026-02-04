<?php

use Phalcon\Mvc\Controller;

class NotesController extends Controller
{
    /**
     * List all notes as JSON (for API)
     */
    public function listAction()
    {
        $this->view->disable();
        $this->response->setContentType('application/json');
        
        try {
            $notes = Notes::find(['order' => 'id DESC']);
            
            $data = [];
            foreach ($notes as $note) {
                $data[] = $note->toArray();
            }
            
            $this->response->setJsonContent([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->response->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        
        return $this->response;
    }

    /**
     * Add a new note via AJAX (alternative to create)
     */
    public function addAction()
    {
        $this->view->disable();
        $this->response->setContentType('application/json');
        
        if (!$this->request->isPost()) {
            $this->response->setStatusCode(400);
            $this->response->setJsonContent(['status' => 'error', 'message' => 'Method POST required']);
            return $this->response;
        }

        try {
            // Try to parse JSON body first
            $rawBody = $this->request->getRawBody();
            $json = json_decode($rawBody, true);
            
            // Fall back to POST data if JSON parsing fails
            $judul = $json['judul'] ?? $this->request->getPost('judul');
            $isi = $json['isi'] ?? $this->request->getPost('isi');
            $tanggal = $json['tanggal'] ?? $this->request->getPost('tanggal');
            $kategori = $json['kategori'] ?? $this->request->getPost('kategori');
            $prioritas = $json['prioritas'] ?? $this->request->getPost('prioritas');
            $status = $json['status'] ?? $this->request->getPost('status');
            
            $note = new Notes();
            $note->judul = $judul;
            $note->isi = $isi;
            $note->tanggal = $tanggal ?: date('Y-m-d');
            $note->kategori = $kategori ?: 'Umum';
            $note->prioritas = $prioritas ?: 'Normal';
            $note->status = $status ?: 'Aktif';

            if ($note->save()) {
                $this->response->setJsonContent([
                    'status' => 'success',
                    'message' => 'Note saved',
                    'data' => $note->toArray()
                ]);
            } else {
                $this->response->setStatusCode(400);
                $messages = [];
                foreach ($note->getMessages() as $msg) {
                    $messages[] = $msg->getMessage();
                }
                $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => implode(', ', $messages)
                ]);
            }
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            $this->response->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }

        return $this->response;
    }

    /**
     * Tampilkan semua catatan (untuk HTML view)
     */
    public function indexAction()
    {
        // Set JSON header if format=json or Accept header is application/json
        $format = $this->request->getQuery('format');
        $accept = $this->request->getHeader('Accept');
        
        $isJson = ($format === 'json') || 
                  ($accept && strpos($accept, 'application/json') !== false);
        
        if ($isJson) {
            // Disable view
            $this->view->disable();
            
            // Return JSON response
            $this->response->setContentType('application/json');
            $this->response->setStatusCode(200);
            
            $notes = Notes::find([
                'order' => 'id DESC'
            ]);
            
            $data = [];
            foreach ($notes as $note) {
                $data[] = $note->toArray();
            }
            
            $jsonResponse = json_encode([
                'status' => 'success',
                'data' => $data
            ]);
            
            $this->response->setContent($jsonResponse);
            $this->response->send();
            return;
        }

        // Serve HTML page - show notes.html
        $this->view->disable();
        readfile(APP_PATH . '/public/notes.html');
        return;
    }

    /**
     * Simpan catatan baru
     */
    public function createAction()
    {
        if (!$this->request->isPost()) {
            $this->handleJsonResponse(['status' => 'error', 'message' => 'Method POST required'], 400);
            return;
        }

        // Try to read JSON body first, fallback to form data
        $input = [];
        $contentType = $this->request->getContentType();
        
        if ($contentType && strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getRawBody();
            if ($rawBody) {
                $input = json_decode($rawBody, true) ?: [];
            }
        }
        
        // Fallback to form data
        if (empty($input)) {
            $input = [
                'judul' => $this->request->getPost('judul'),
                'isi' => $this->request->getPost('isi'),
                'kategori' => $this->request->getPost('kategori'),
                'prioritas' => $this->request->getPost('prioritas'),
                'status' => $this->request->getPost('status'),
                'tanggal' => $this->request->getPost('tanggal')
            ];
        }

        $note = new Notes();
        $note->judul      = $input['judul'] ?? '';
        $note->isi        = $input['isi'] ?? '';
        $note->kategori   = $input['kategori'] ?? 'Umum';
        $note->prioritas  = $input['prioritas'] ?? 'Normal';
        $note->status     = $input['status'] ?? 'Aktif';
        $note->tanggal    = $input['tanggal'] ?? date('Y-m-d');

        if (!$note->save()) {
            $errors = [];
            foreach ($note->getMessages() as $message) {
                $errors[] = (string)$message;
            }
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal menyimpan catatan',
                'errors' => $errors
            ], 400);
            return;
        }

        $this->handleJsonResponse([
            'status' => 'success',
            'message' => 'Catatan berhasil disimpan',
            'data' => $note->toArray()
        ]);
    }

    /**
     * Hapus catatan
     */
    public function deleteAction($id)
    {
        $this->view->disable();
        $this->response->setContentType('application/json');
        
        $note = Notes::findFirst($id);

        if (!$note) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Catatan tidak ditemukan'
            ], 404);
            return;
        }

        if (!$note->delete()) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal menghapus catatan'
            ], 400);
            return;
        }

        $this->handleJsonResponse([
            'status' => 'success',
            'message' => 'Catatan berhasil dihapus'
        ]);
    }

    /**
     * Form edit catatan
     */
    public function editAction($id)
    {
        // Jika JSON request, return JSON
        if ($this->request->getHeader('Accept') === 'application/json' || 
            $this->request->get('format') === 'json') {
            $note = Notes::findFirst($id);
            if (!$note) {
                $this->handleJsonResponse([
                    'status' => 'error',
                    'message' => 'Catatan tidak ditemukan'
                ], 404);
                return;
            }
            $this->handleJsonResponse([
                'status' => 'success',
                'data' => $note->toArray()
            ]);
            return;
        }

        // HTML view
        $this->view->setLayout('main');
        $note = Notes::findFirst($id);
        if (!$note) {
            return $this->response->redirect('/notes');
        }
        $this->view->note = $note;
    }

    /**
     * Update catatan
     */
    public function updateAction($id)
    {
        if (!$this->request->isPost()) {
            $this->handleJsonResponse(['status' => 'error', 'message' => 'Method POST required'], 400);
            return;
        }

        $note = Notes::findFirst($id);
        if (!$note) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Catatan tidak ditemukan'
            ], 404);
            return;
        }

        $note->judul      = $this->request->getPost('judul', 'string');
        $note->isi        = $this->request->getPost('isi', 'string');
        $note->kategori   = $this->request->getPost('kategori', 'string') ?: $note->kategori;
        $note->prioritas  = $this->request->getPost('prioritas', 'string') ?: $note->prioritas;
        $note->status     = $this->request->getPost('status', 'string') ?: $note->status;
        $note->tanggal    = $this->request->getPost('tanggal', 'string') ?: $note->tanggal;

        if (!$note->save()) {
            $errors = [];
            foreach ($note->getMessages() as $message) {
                $errors[] = (string)$message;
            }
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal mengupdate catatan',
                'errors' => $errors
            ], 400);
            return;
        }

        $this->handleJsonResponse([
            'status' => 'success',
            'message' => 'Catatan berhasil diupdate',
            'data' => $note->toArray()
        ]);
    }

    /**
     * Helper untuk response JSON
     */
    private function handleJsonResponse($data, $statusCode = 200)
    {
        $this->response->setStatusCode($statusCode);
        $this->response->setContentType('application/json');
        $this->response->setContent(json_encode($data));
        $this->response->send();
    }

    /**
     * Sync data dari Odoo
     * GET /notes/sync-odoo
     */
    public function syncOdooAction()
    {
        try {
            // Initialize Odoo Service (uses default odoo:8069 inside Docker)
            $odoo = new OdooService();

            // Fetch Sales Orders
            $salesOrders = $odoo->read('sale.order', 
                ['id', 'name', 'partner_id', 'date_order', 'state', 'amount_total']
            );

            // Fetch Purchase Orders
            $purchaseOrders = $odoo->read('purchase.order',
                ['id', 'name', 'partner_id', 'date_order', 'state', 'amount_total']
            );

            // Fetch Invoices
            $invoices = $odoo->read('account.move',
                ['id', 'name', 'partner_id', 'invoice_date', 'state', 'amount_total']
            );

            $this->handleJsonResponse([
                'status'  => 'success',
                'message' => 'Data synced from Odoo',
                'data'    => [
                    'sales_orders'     => $salesOrders,
                    'purchase_orders'  => $purchaseOrders,
                    'invoices'         => $invoices
                ]
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal sync dengan Odoo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Odoo connection status
     * GET /notes/odoo-status
     */
    public function odooStatusAction()
    {
        try {
            // Simple check - try to connect to Odoo HTTP endpoint
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'http://odoo:8069',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("Network Error: $error");
            }
            
            if ($httpCode === 0) {
                throw new Exception("Odoo not responding on odoo:8069");
            }

            $this->handleJsonResponse([
                'status'  => 'success',
                'message' => 'Odoo is accessible',
                'odoo_url' => 'http://odoo:8069',
                'http_code' => $httpCode
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Cannot connect to Odoo: ' . $e->getMessage(),
                'odoo_url' => 'http://odoo:8069'
            ], 500);
        }
    }

    /**
     * Link catatan dengan Odoo document
     * POST /notes/link-odoo/{id}
     */
    public function linkOdooAction($id)
    {
        if (!$this->request->isPost()) {
            $this->handleJsonResponse(['status' => 'error', 'message' => 'Method POST required'], 400);
            return;
        }

        $note = Notes::findFirst($id);
        if (!$note) {
            $this->handleJsonResponse(['status' => 'error', 'message' => 'Catatan tidak ditemukan'], 404);
            return;
        }

        // Link to Odoo document
        $modelType = $this->request->getPost('model');      // e.g., 'sale.order'
        $recordId  = $this->request->getPost('record_id');  // Odoo record ID

        $note->odoo_model_id = $recordId;
        $note->odoo_model_type = $modelType;

        if (!$note->save()) {
            $this->handleJsonResponse(['status' => 'error', 'message' => 'Gagal linking catatan'], 400);
            return;
        }

        $this->handleJsonResponse([
            'status'  => 'success',
            'message' => 'Catatan berhasil dilink dengan Odoo',
            'data'    => $note->toArray()
        ]);
    }

    /**
     * Get Odoo documents untuk linking
     * GET /notes/odoo-documents?type=sale.order
     */
    public function odooDocumentsAction()
    {
        try {
            $type = $this->request->getQuery('type', 'string') ?: 'sale.order';
            
            $odoo = new OdooService();

            $documents = $odoo->read($type, ['id', 'name', 'state'], [], 100);

            $this->handleJsonResponse([
                'status'  => 'success',
                'type'    => $type,
                'data'    => $documents
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch documents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Sales Orders dengan Inventory Connection
     * GET /api/odoo/sales-with-inventory
     */
    public function salesWithInventoryAction()
    {
        try {
            $odoo = new OdooService();
            
            // Fetch sales orders
            $salesOrders = $odoo->read('sale.order', [
                'id', 'name', 'partner_id', 'date_order', 'state', 
                'amount_total', 'order_line'
            ], [], 100);

            // Fetch all products (inventory hub)
            $products = $odoo->read('product.product', [
                'id', 'name', 'qty_available', 'virtual_available', 'list_price'
            ], [['type', '!=', 'service']], 500);

            // Enrich sales orders with product info
            foreach ($salesOrders as &$sale) {
                $sale['products_info'] = [];
                if (isset($sale['order_line']) && is_array($sale['order_line'])) {
                    foreach ($sale['order_line'] as $line_id) {
                        // Find product in orders
                        foreach ($products as $product) {
                            // Match logic here - in real scenario would fetch line details
                            $sale['products_info'][] = [
                                'product_id' => $product['id'],
                                'product_name' => $product['name'],
                                'qty_available' => $product['qty_available'],
                                'virtual_available' => $product['virtual_available']
                            ];
                        }
                    }
                }
            }

            $this->handleJsonResponse([
                'status'  => 'success',
                'module'  => 'Sales (with Inventory)',
                'total'   => count($salesOrders),
                'inventory_hub' => [
                    'total_products' => count($products),
                    'products' => $products
                ],
                'data'    => $salesOrders
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Sales with Inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Purchase Orders dengan Inventory Connection
     * GET /api/odoo/purchases-with-inventory
     */
    public function purchasesWithInventoryAction()
    {
        try {
            $odoo = new OdooService();
            
            // Fetch purchase orders
            $purchaseOrders = $odoo->read('purchase.order', [
                'id', 'name', 'partner_id', 'date_order', 'state',
                'amount_total', 'order_line'
            ], [], 100);

            // Fetch all products (inventory hub)
            $products = $odoo->read('product.product', [
                'id', 'name', 'qty_available', 'virtual_available', 'standard_price'
            ], [['type', '!=', 'service']], 500);

            // Enrich purchase orders with product info
            foreach ($purchaseOrders as &$purchase) {
                $purchase['products_info'] = [];
                if (isset($purchase['order_line']) && is_array($purchase['order_line'])) {
                    foreach ($purchase['order_line'] as $line_id) {
                        foreach ($products as $product) {
                            $purchase['products_info'][] = [
                                'product_id' => $product['id'],
                                'product_name' => $product['name'],
                                'qty_available' => $product['qty_available'],
                                'virtual_available' => $product['virtual_available'],
                                'current_cost' => $product['standard_price']
                            ];
                        }
                    }
                }
            }

            $this->handleJsonResponse([
                'status'  => 'success',
                'module'  => 'Purchase (with Inventory)',
                'total'   => count($purchaseOrders),
                'inventory_hub' => [
                    'total_products' => count($products),
                    'products' => $products
                ],
                'data'    => $purchaseOrders
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Purchases with Inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Invoices dengan Inventory Connection
     * GET /api/odoo/invoices-with-inventory
     */
    public function invoicesWithInventoryAction()
    {
        try {
            $odoo = new OdooService();
            
            // Fetch invoices
            $invoices = $odoo->read('account.move', [
                'id', 'name', 'partner_id', 'invoice_date', 'state',
                'amount_total', 'invoice_line_ids'
            ], [['move_type', '=', 'out_invoice']], 100);

            // Fetch all products (inventory hub)
            $products = $odoo->read('product.product', [
                'id', 'name', 'qty_available', 'virtual_available', 'list_price'
            ], [['type', '!=', 'service']], 500);

            // Enrich invoices with product info
            foreach ($invoices as &$invoice) {
                $invoice['products_info'] = [];
                if (isset($invoice['invoice_line_ids']) && is_array($invoice['invoice_line_ids'])) {
                    foreach ($invoice['invoice_line_ids'] as $line_id) {
                        foreach ($products as $product) {
                            $invoice['products_info'][] = [
                                'product_id' => $product['id'],
                                'product_name' => $product['name'],
                                'qty_available' => $product['qty_available'],
                                'sale_price' => $product['list_price']
                            ];
                        }
                    }
                }
            }

            $this->handleJsonResponse([
                'status'  => 'success',
                'module'  => 'Invoicing (with Inventory)',
                'total'   => count($invoices),
                'inventory_hub' => [
                    'total_products' => count($products),
                    'products' => $products
                ],
                'data'    => $invoices
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Invoices with Inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Inventory Hub - Pusat semua modules
     * GET /api/odoo/inventory-hub
     */
    public function inventoryHubAction()
    {
        try {
            $odoo = new OdooService();
            
            // Central: Fetch all products
            $products = $odoo->read('product.product', [
                'id', 'name', 'qty_available', 'virtual_available', 
                'list_price', 'standard_price', 'categ_id'
            ], [['type', '!=', 'service']], 500);

            // Related Sales
            $sales = $odoo->read('sale.order', [
                'id', 'name', 'state', 'amount_total'
            ], [], 100);

            // Related Purchases
            $purchases = $odoo->read('purchase.order', [
                'id', 'name', 'state', 'amount_total'
            ], [], 100);

            // Related Invoices
            $invoices = $odoo->read('account.move', [
                'id', 'name', 'state', 'amount_total'
            ], [['move_type', '=', 'out_invoice']], 100);

            // Stock Pickings
            $pickings = $odoo->read('stock.picking', [
                'id', 'name', 'state', 'scheduled_date'
            ], [], 100);

            $this->handleJsonResponse([
                'status'  => 'success',
                'message' => 'Inventory Hub - Central Connection Point',
                'inventory_center' => [
                    'total_products' => count($products),
                    'products' => $products,
                    'low_stock' => array_filter($products, function($p) {
                        return $p['qty_available'] < 10;
                    })
                ],
                'connections' => [
                    'sales' => [
                        'total' => count($sales),
                        'active' => count(array_filter($sales, function($s) { 
                            return $s['state'] === 'sale'; 
                        })),
                        'data' => $sales
                    ],
                    'purchases' => [
                        'total' => count($purchases),
                        'active' => count(array_filter($purchases, function($p) { 
                            return $p['state'] === 'purchase'; 
                        })),
                        'data' => $purchases
                    ],
                    'invoices' => [
                        'total' => count($invoices),
                        'data' => $invoices
                    ],
                    'pickings' => [
                        'total' => count($pickings),
                        'completed' => count(array_filter($pickings, function($pick) { 
                            return $pick['state'] === 'done'; 
                        })),
                        'data' => $pickings
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Inventory Hub: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Purchase Orders dari Odoo
     * GET /api/odoo/purchases
     */
    public function oodooPurchasesAction()
    {
        try {
            $odoo = new OdooService();
            
            // Fetch purchase orders with details
            $purchaseOrders = $odoo->read('purchase.order', [
                'id', 'name', 'partner_id', 'date_order', 'state',
                'amount_total', 'amount_untaxed', 'order_line'
            ], [], 100);

            $this->handleJsonResponse([
                'status'  => 'success',
                'module'  => 'Purchase',
                'total'   => count($purchaseOrders),
                'data'    => $purchaseOrders
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Purchase Orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Invoices dari Odoo
     * GET /api/odoo/invoices
     */
    public function odooInvoicesAction()
    {
        try {
            $odoo = new OdooService();
            
            // Fetch invoices (account.move in Odoo 17)
            $invoices = $odoo->read('account.move', [
                'id', 'name', 'partner_id', 'invoice_date', 'state',
                'amount_total', 'amount_untaxed', 'move_type'
            ], [['move_type', '=', 'out_invoice']], 100);

            $this->handleJsonResponse([
                'status'  => 'success',
                'module'  => 'Invoicing',
                'total'   => count($invoices),
                'data'    => $invoices
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch Inventory/Stock dari Odoo
     * GET /api/odoo/inventory
     */
    public function odooInventoryAction()
    {
        try {
            $odoo = new OdooService();
            
            // Fetch stock pickings (deliveries)
            $pickings = $odoo->read('stock.picking', [
                'id', 'name', 'partner_id', 'scheduled_date', 'state',
                'origin', 'move_ids'
            ], [], 100);

            // Fetch products with stock
            $products = $odoo->read('product.product', [
                'id', 'name', 'qty_available', 'virtual_available', 
                'list_price', 'standard_price'
            ], [['type', '!=', 'service']], 100);

            $this->handleJsonResponse([
                'status'  => 'success',
                'module'  => 'Inventory',
                'data'    => [
                    'pickings' => [
                        'total' => count($pickings),
                        'items' => $pickings
                    ],
                    'products' => [
                        'total' => count($products),
                        'items' => $products
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard - Fetch summary dari semua modules
     * GET /api/odoo/dashboard
     */
    public function oodooDashboardAction()
    {
        try {
            $odoo = new OdooService();
            
            // Get sales summary
            $sales = $odoo->read('sale.order', ['id', 'state', 'amount_total'], [], 50);
            $confirmedSales = array_filter($sales, function($s) { 
                return $s['state'] === 'sale'; 
            });
            
            // Get purchase summary
            $purchases = $odoo->read('purchase.order', ['id', 'state', 'amount_total'], [], 50);
            $confirmedPurchases = array_filter($purchases, function($p) { 
                return $p['state'] === 'purchase'; 
            });
            
            // Get invoice summary
            $invoices = $odoo->read('account.move', 
                ['id', 'state', 'amount_total'], 
                [['move_type', '=', 'out_invoice']], 
                50
            );
            
            // Get stock picking summary
            $pickings = $odoo->read('stock.picking', ['id', 'state'], [], 50);
            $donePickings = array_filter($pickings, function($p) { 
                return $p['state'] === 'done'; 
            });

            // Calculate totals
            $totalSalesAmount = array_sum(array_column($confirmedSales, 'amount_total'));
            $totalPurchaseAmount = array_sum(array_column($confirmedPurchases, 'amount_total'));
            $totalInvoiceAmount = array_sum(array_column($invoices, 'amount_total'));

            $this->handleJsonResponse([
                'status'  => 'success',
                'summary' => [
                    'sales' => [
                        'total_orders' => count($sales),
                        'confirmed_orders' => count($confirmedSales),
                        'total_amount' => $totalSalesAmount
                    ],
                    'purchases' => [
                        'total_orders' => count($purchases),
                        'confirmed_orders' => count($confirmedPurchases),
                        'total_amount' => $totalPurchaseAmount
                    ],
                    'invoices' => [
                        'total_invoices' => count($invoices),
                        'total_amount' => $totalInvoiceAmount
                    ],
                    'inventory' => [
                        'total_pickings' => count($pickings),
                        'completed_pickings' => count($donePickings)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========== CRUD OPERATIONS ==========

    /**
     * Fetch Sales Orders (simplified, non-inventory version)
     * GET /api/odoo/sales
     */
    public function salesOrdersAction()
    {
        try {
            error_log('[SALES] Fetching sales orders...');
            $odoo = new OdooService();
            
            $salesOrders = $odoo->read('sale.order', [
                'id', 'name', 'partner_id', 'date_order', 'state', 
                'amount_total', 'amount_untaxed', 'currency_id', 'user_id'
            ], [], 100);

            error_log('[SALES] Fetch result: ' . json_encode($salesOrders));

            if (isset($salesOrders['error'])) {
                throw new \Exception($salesOrders['error']);
            }

            $this->handleJsonResponse([
                'status'  => 'success',
                'module'  => 'Sales Orders',
                'total'   => count($salesOrders),
                'data'    => $salesOrders
            ]);
        } catch (\Exception $e) {
            error_log('[SALES ERROR] ' . $e->getMessage());
            $this->handleJsonResponse([
                'status'  => 'error',
                'message' => 'Gagal fetch Sales Orders: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createSalesAction()
    {
        try {
            $input = $this->getJsonInput();
            error_log('[SALES CREATE] Input: ' . json_encode($input));
            
            $odoo = new OdooService();
            
            // Validate required fields
            if (!isset($input['partner_id'])) {
                error_log('[SALES CREATE] Partner ID missing');
                return $this->handleJsonResponse([
                    'status' => 'error',
                    'message' => 'Partner ID harus diisi'
                ], 400);
            }

            // Prepare data untuk create
            $createData = [
                'partner_id' => intval($input['partner_id'])
            ];

            error_log('[SALES CREATE] CreateData: ' . json_encode($createData));

            // Optional fields
            if (isset($input['order_line'])) {
                $createData['order_line'] = $input['order_line'];
            }

            // Create sale order
            error_log('[SALES CREATE] Calling Odoo create...');
            $result = $odoo->create('sale.order', $createData);
            error_log('[SALES CREATE] Result: ' . json_encode($result));
            
            // Check if result is error
            if (is_array($result) && isset($result['error'])) {
                $errorMsg = $result['error'];
                error_log('[SALES CREATE] Odoo error: ' . $errorMsg);
                throw new \Exception($errorMsg);
            }

            if ($result === null || $result === false) {
                throw new \Exception('Odoo returned null or false');
            }

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Sales Order berhasil dibuat',
                'sale_order_id' => $result
            ]);
        } catch (\Exception $e) {
            error_log('[SALES CREATE ERROR] ' . $e->getMessage());
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal membuat Sales Order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATE Sales Order
     * POST /api/odoo/sales/update/{id}
     */
    public function updateSalesAction($id)
    {
        try {
            $input = $this->getJsonInput();
            $odoo = new OdooService();
            
            $updateData = [];
            if (isset($input['amount_total'])) $updateData['amount_total'] = floatval($input['amount_total']);
            if (isset($input['state'])) $updateData['state'] = $input['state'];
            if (isset($input['note'])) $updateData['note'] = $input['note'];

            $odoo->write('sale.order', [intval($id)], $updateData);

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Sales Order berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal update Sales Order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE Sales Order
     * GET/DELETE /api/odoo/sales/delete/{id}
     */
    public function deleteSalesAction($id)
    {
        try {
            $odoo = new OdooService();
            $odoo->delete('sale.order', [intval($id)]);

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Sales Order berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal delete Sales Order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * CREATE Purchase Order
     * POST /api/odoo/purchases/create
     */
    public function createPurchaseAction()
    {
        try {
            $input = $this->getJsonInput();
            
            $odoo = new OdooService();
            
            if (!isset($input['partner_id']) || !isset($input['amount_total'])) {
                return $this->handleJsonResponse([
                    'status' => 'error',
                    'message' => 'Partner dan Amount harus diisi'
                ], 400);
            }

            $purchaseOrderId = $odoo->create('purchase.order', [
                'partner_id' => intval($input['partner_id']),
                'amount_total' => floatval($input['amount_total']),
                'state' => 'draft'
            ]);

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Purchase Order berhasil dibuat',
                'purchase_order_id' => $purchaseOrderId
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal membuat Purchase Order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATE Purchase Order
     * POST /api/odoo/purchases/update/{id}
     */
    public function updatePurchaseAction($id)
    {
        try {
            $input = $this->getJsonInput();
            $odoo = new OdooService();
            
            $updateData = [];
            if (isset($input['amount_total'])) $updateData['amount_total'] = floatval($input['amount_total']);
            if (isset($input['state'])) $updateData['state'] = $input['state'];
            if (isset($input['note'])) $updateData['note'] = $input['note'];

            $odoo->write('purchase.order', [intval($id)], $updateData);

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Purchase Order berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal update Purchase Order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE Purchase Order
     * GET/DELETE /api/odoo/purchases/delete/{id}
     */
    public function deletePurchaseAction($id)
    {
        try {
            $odoo = new OdooService();
            $odoo->delete('purchase.order', [intval($id)]);

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Purchase Order berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal delete Purchase Order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * CREATE Invoice
     * POST /api/odoo/invoices/create
     */
    public function createInvoiceAction()
    {
        try {
            $input = $this->getJsonInput();
            
            $odoo = new OdooService();
            
            if (!isset($input['partner_id']) || !isset($input['amount_total'])) {
                return $this->handleJsonResponse([
                    'status' => 'error',
                    'message' => 'Partner dan Amount harus diisi'
                ], 400);
            }

            $invoiceId = $odoo->create('account.move', [
                'partner_id' => intval($input['partner_id']),
                'amount_total' => floatval($input['amount_total']),
                'move_type' => 'out_invoice',
                'state' => 'draft'
            ]);

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Invoice berhasil dibuat',
                'invoice_id' => $invoiceId
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal membuat Invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATE Invoice
     * POST /api/odoo/invoices/update/{id}
     */
    public function updateInvoiceAction($id)
    {
        try {
            $input = $this->getJsonInput();
            $odoo = new OdooService();
            
            $updateData = [];
            if (isset($input['amount_total'])) $updateData['amount_total'] = floatval($input['amount_total']);
            if (isset($input['state'])) $updateData['state'] = $input['state'];
            if (isset($input['note'])) $updateData['note'] = $input['note'];

            $odoo->write('account.move', [intval($id)], $updateData);

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Invoice berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal update Invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE Invoice
     * GET/DELETE /api/odoo/invoices/delete/{id}
     */
    public function deleteInvoiceAction($id)
    {
        try {
            $odoo = new OdooService();
            $odoo->delete('account.move', [intval($id)]);

            $this->handleJsonResponse([
                'status' => 'success',
                'message' => 'Invoice berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal delete Invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET Partners (untuk dropdown di form create)
     * GET /api/odoo/partners
     */
    public function getPartnersAction()
    {
        try {
            $odoo = new OdooService();
            $partners = $odoo->read('res.partner', ['id', 'name', 'email', 'phone'], [], 100);

            $this->handleJsonResponse([
                'status' => 'success',
                'data' => $partners
            ]);
        } catch (\Exception $e) {
            $this->handleJsonResponse([
                'status' => 'error',
                'message' => 'Gagal fetch Partners: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Get JSON input
     */
    private function getJsonInput()
    {
        $contentType = $this->request->getContentType();
        
        if ($contentType && strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getRawBody();
            return $rawBody ? json_decode($rawBody, true) : [];
        }
        
        return [];
    }
}
