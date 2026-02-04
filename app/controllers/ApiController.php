<?php

use Phalcon\Mvc\Controller;

// Load all models
require_once(APP_PATH . '/models/Product.php');
require_once(APP_PATH . '/models/SalesOrder.php');
require_once(APP_PATH . '/models/PurchaseOrder.php');
require_once(APP_PATH . '/models/Invoice.php');
require_once(APP_PATH . '/library/CommerceService.php');

class ApiController extends Controller
{
    private $odooService;
    private $commerceService;

    public function initialize()
    {
        // Disable view
        $this->view->disable();
        
        // Set header JSON
        $this->response->setContentType('application/json');
    }
    
    private function initOdooService()
    {
        if ($this->odooService) {
            return true;
        }
        
        try {
            // Load OdooService
            $odooServicePath = APP_PATH . '/library/OdooService.php';
            if (!file_exists($odooServicePath)) {
                throw new Exception('OdooService file not found: ' . $odooServicePath);
            }
            
            require_once $odooServicePath;
            
            $this->odooService = new OdooService([
                'url'      => 'http://odoo:8069',
                'database' => 'odoo',
                'username' => 'admin',
                'password' => 'admin'
            ]);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get notes dari database lokal Phalcon
     */
    public function notesAction()
    {
        $notes = Notes::find([
            'order' => 'created_at DESC'
        ]);

        $data = [];

        foreach ($notes as $note) {
            $data[] = [
                'id'        => $note->id,
                'judul'     => $note->judul,
                'isi'       => $note->isi,
                'kategori'  => $note->kategori,
                'prioritas' => $note->prioritas,
                'status'    => $note->status,
                'tanggal'   => $note->tanggal,
                'created_at'=> $note->created_at,
                'updated_at'=> $note->updated_at
            ];
        }

        return $this->response->setJsonContent([
            'status' => 'success',
            'data'   => $data
        ]);
    }

    /**
     * Create note
     */
    public function notesCreateAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'POST method required'
            ]);
        }

        // Get JSON body
        $json = json_decode($this->request->getRawBody(), true);
        if (!$json) {
            // Fallback to form data
            $json = [];
            $json['judul'] = $this->request->getPost('judul');
            $json['isi'] = $this->request->getPost('isi');
            $json['kategori'] = $this->request->getPost('kategori');
            $json['prioritas'] = $this->request->getPost('prioritas');
            $json['status'] = $this->request->getPost('status');
            $json['tanggal'] = $this->request->getPost('tanggal');
        }

        $note = new Notes();
        $note->judul      = $json['judul'] ?? '';
        $note->isi        = $json['isi'] ?? '';
        $note->kategori   = $json['kategori'] ?: 'Umum';
        $note->prioritas  = $json['prioritas'] ?: 'Normal';
        $note->status     = $json['status'] ?: 'Aktif';
        $note->tanggal    = $json['tanggal'] ?: date('Y-m-d');

        if (!$note->save()) {
            $errors = [];
            foreach ($note->getMessages() as $message) {
                $errors[] = (string)$message;
            }
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Failed to save note',
                'errors' => $errors
            ]);
        }

        return $this->response->setJsonContent([
            'status' => 'success',
            'message' => 'Note created successfully',
            'data' => [
                'id' => $note->id,
                'judul' => $note->judul,
                'isi' => $note->isi,
                'kategori' => $note->kategori,
                'prioritas' => $note->prioritas,
                'status' => $note->status,
                'tanggal' => $note->tanggal
            ]
        ]);
    }

    /**
     * Get single note
     */
    public function notesGetAction($id)
    {
        $note = Notes::findFirst($id);
        
        if (!$note) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Note not found'
            ]);
        }

        return $this->response->setJsonContent([
            'status' => 'success',
            'data' => [
                'id' => $note->id,
                'judul' => $note->judul,
                'isi' => $note->isi,
                'kategori' => $note->kategori,
                'prioritas' => $note->prioritas,
                'status' => $note->status,
                'tanggal' => $note->tanggal,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at
            ]
        ]);
    }

    /**
     * Update note
     */
    public function notesUpdateAction($id)
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'POST method required'
            ]);
        }

        $note = Notes::findFirst($id);
        if (!$note) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Note not found'
            ]);
        }

        $note->judul     = $this->request->getPost('judul', 'string') ?: $note->judul;
        $note->isi       = $this->request->getPost('isi', 'string') ?: $note->isi;
        $note->kategori  = $this->request->getPost('kategori', 'string') ?: $note->kategori;
        $note->prioritas = $this->request->getPost('prioritas', 'string') ?: $note->prioritas;
        $note->status    = $this->request->getPost('status', 'string') ?: $note->status;
        $note->tanggal   = $this->request->getPost('tanggal', 'string') ?: $note->tanggal;

        if (!$note->save()) {
            $errors = [];
            foreach ($note->getMessages() as $message) {
                $errors[] = (string)$message;
            }
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Failed to update note',
                'errors' => $errors
            ]);
        }

        return $this->response->setJsonContent([
            'status' => 'success',
            'message' => 'Note updated successfully',
            'data' => [
                'id' => $note->id,
                'judul' => $note->judul,
                'isi' => $note->isi,
                'kategori' => $note->kategori,
                'prioritas' => $note->prioritas,
                'status' => $note->status,
                'tanggal' => $note->tanggal
            ]
        ]);
    }

    /**
     * Delete note
     */
    public function notesDeleteAction($id)
    {
        $note = Notes::findFirst($id);
        if (!$note) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Note not found'
            ]);
        }

        if (!$note->delete()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Failed to delete note'
            ]);
        }

        return $this->response->setJsonContent([
            'status' => 'success',
            'message' => 'Note deleted successfully'
        ]);
    }

    /**
     * Health check endpoint
     */
    public function healthAction()
    {
        $services = [
            'phalcon_db' => 'connected'
        ];

        // Check Odoo connection
        try {
            $this->initOdooService();
            $odooTest = $this->odooService->testConnection();
            $services['odoo'] = $odooTest['status'] === 'success' ? 'connected' : 'disconnected';
        } catch (Exception $e) {
            $services['odoo'] = 'error';
        }

        return $this->response->setJsonContent([
            'status'   => true,
            'message'  => 'Phalcon API is running',
            'services' => $services
        ]);
    }

    /**
     * Get partners dari Odoo
     */
    public function odooPartnersAction()
    {
        try {
            $this->initOdooService();
            $partners = $this->odooService->getPartners(50);

            if (isset($partners['error'])) {
                throw new Exception($partners['error']);
            }

            return $this->response->setJsonContent([
                'status' => true,
                'source' => 'odoo',
                'data'   => $partners
            ]);

        } catch (Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJsonContent([
                    'status'  => false,
                    'error'   => $e->getMessage(),
                    'source'  => 'odoo'
                ]);
        }
    }

    /**
     * Get sales orders dari Odoo
     */
    public function odooSalesOrdersAction()
    {
        $this->view->disable();

        try {
            $this->initOdooService();
            
            if (!$this->odooService) {
                throw new Exception('Odoo service not initialized');
            }

            $orders = $this->odooService->getSalesOrders(50);

            if (isset($orders['error'])) {
                throw new Exception($orders['error']);
            }

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status' => true,
                    'source' => 'odoo',
                    'data'   => $orders
                ]);

        } catch (Exception $e) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setJsonContent([
                    'status'  => false,
                    'error'   => $e->getMessage(),
                    'source'  => 'odoo'
                ]);
        }
    }

    /**
     * Get data dari Odoo model
     * POST /api/odoo-read
     * Body: { "model": "res.partner", "fields": ["name", "email"], "domain": [], "limit": 10 }
     */
    public function odooReadAction()
    {
        $this->view->disable();

        try {
            $this->initOdooService();
            
            if (!$this->odooService) {
                throw new Exception('Odoo service not initialized');
            }

            $data = $this->request->getJsonRawBody(true);
            
            $model = $data['model'] ?? 'res.partner';
            $fields = $data['fields'] ?? [];
            $domain = $data['domain'] ?? [];
            $limit = $data['limit'] ?? 10;

            $result = $this->odooService->read($model, $fields, $domain, $limit);

            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status' => true,
                    'source' => 'odoo',
                    'model'  => $model,
                    'data'   => $result
                ]);

        } catch (Exception $e) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setJsonContent([
                    'status'  => false,
                    'error'   => $e->getMessage(),
                    'source'  => 'odoo'
                ]);
        }
    }

    /**
     * Create record di Odoo
     * POST /api/odoo-create
     * Body: { "model": "res.partner", "values": { "name": "John", "email": "john@example.com" } }
     */
    public function odooCreateAction()
    {
        $this->view->disable();

        try {
            $this->initOdooService();
            
            if (!$this->odooService) {
                throw new Exception('Odoo service not initialized');
            }

            $data = $this->request->getJsonRawBody(true);
            
            $model = $data['model'] ?? null;
            $values = $data['values'] ?? [];

            if (!$model) {
                throw new Exception('Model name is required');
            }

            $result = $this->odooService->create($model, $values);

            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status'  => true,
                    'source'  => 'odoo',
                    'model'   => $model,
                    'id'      => $result,
                    'message' => "Record created successfully with ID: $result"
                ]);

        } catch (Exception $e) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setJsonContent([
                    'status'  => false,
                    'error'   => $e->getMessage(),
                    'source'  => 'odoo'
                ]);
        }
    }

    /**
     * Test connection ke Odoo
     */
    public function odooTestAction()
    {
        $this->view->disable();

        try {
            $this->initOdooService();
            
            if (!$this->odooService) {
                throw new Exception('Odoo service not initialized');
            }

            $result = $this->odooService->testConnection();

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent($result);

        } catch (Exception $e) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setJsonContent([
                    'status'  => false,
                    'error'   => $e->getMessage(),
                    'source'  => 'odoo'
                ]);
        }
    }

    /**
     * Get Sales Orders dari Odoo
     * GET /api/odoo/sales-orders
     */
    public function salesOrdersAction()
    {
        try {
            $this->initOdooService();
            
            $orders = $this->odooService->read('sale.order', 
                ['name', 'partner_id', 'date_order', 'amount_total', 'state'], 
                [], 
                50
            );

            if (isset($orders['error'])) {
                throw new Exception($orders['error']);
            }

            // If empty, it's normal - just return empty data
            $data = is_array($orders) ? $orders : [];

            $this->response->setJsonContent([
                'status'  => true,
                'source'  => 'odoo',
                'module'  => 'sales',
                'message' => count($data) > 0 ? 'Sales orders found' : 'No sales orders yet',
                'count'   => count($data),
                'data'    => $data
            ]);
            return $this->response;
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => false,
                'error'  => $e->getMessage(),
                'source' => 'odoo'
            ]);
        }
    }

    /**
     * Get Purchase Orders dari Odoo
     * GET /api/odoo/purchase-orders
     */
    public function purchaseOrdersAction()
    {
        try {
            $this->initOdooService();
            
            $orders = $this->odooService->read('purchase.order', 
                ['name', 'partner_id', 'date_order', 'amount_total', 'state'], 
                [], 
                50
            );

            if (isset($orders['error'])) {
                throw new Exception($orders['error']);
            }

            $data = is_array($orders) ? $orders : [];

            $this->response->setJsonContent([
                'status'  => true,
                'source'  => 'odoo',
                'module'  => 'purchase',
                'message' => count($data) > 0 ? 'Purchase orders found' : 'No purchase orders yet',
                'count'   => count($data),
                'data'    => $data
            ]);
            return $this->response;
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => false,
                'error'  => $e->getMessage(),
                'source' => 'odoo'
            ]);
        }
    }

    /**
     * Get Invoices dari Odoo (Invoicing Module)
     * GET /api/odoo/invoices
     */
    public function invoicesAction()
    {
        try {
            $this->initOdooService();
            
            $invoices = $this->odooService->read('account.move', 
                ['name', 'partner_id', 'invoice_date', 'amount_total', 'state', 'move_type'], 
                [['move_type', 'in', ['out_invoice', 'in_invoice']]], 
                50
            );

            if (isset($invoices['error'])) {
                throw new Exception($invoices['error']);
            }

            return $this->response->setJsonContent([
                'status' => true,
                'source' => 'odoo',
                'module' => 'invoicing',
                'count'  => count($invoices),
                'data'   => $invoices
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => false,
                'error'  => $e->getMessage(),
                'source' => 'odoo'
            ]);
        }
    }

    /**
     * Get Stock/Inventory dari Odoo
     * GET /api/odoo/inventory
     */
    public function inventoryAction()
    {
        try {
            $this->initOdooService();
            
            $inventory = $this->odooService->read('stock.quant', 
                ['product_id', 'location_id', 'quantity', 'reserved_quantity'], 
                [['quantity', '!=', 0]], 
                50
            );

            if (isset($inventory['error'])) {
                throw new Exception($inventory['error']);
            }

            return $this->response->setJsonContent([
                'status' => true,
                'source' => 'odoo',
                'module' => 'inventory',
                'count'  => count($inventory),
                'data'   => $inventory
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => false,
                'error'  => $e->getMessage(),
                'source' => 'odoo'
            ]);
        }
    }

    /**
     * Get Products dari Odoo
     * GET /api/odoo/products
     */
    public function productsAction()
    {
        try {
            $this->initOdooService();
            
            $products = $this->odooService->read('product.product', 
                ['name', 'default_code', 'list_price', 'standard_price', 'qty_available'], 
                [], 
                50
            );

            if (isset($products['error'])) {
                throw new Exception($products['error']);
            }

            return $this->response->setJsonContent([
                'status' => true,
                'source' => 'odoo',
                'module' => 'product',
                'count'  => count($products),
                'data'   => $products
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => false,
                'error'  => $e->getMessage(),
                'source' => 'odoo'
            ]);
        }
    }

    /**
     * Get Dashboard Summary - Ringkasan semua module
     * GET /api/odoo/dashboard
     */
    public function dashboardAction()
    {
        try {
            $this->initOdooService();
            
            $summary = [
                'sales' => [
                    'orders' => $this->odooService->read('sale.order', ['id'], [], 1),
                    'count'  => 0
                ],
                'purchase' => [
                    'orders' => $this->odooService->read('purchase.order', ['id'], [], 1),
                    'count'  => 0
                ],
                'invoicing' => [
                    'invoices' => $this->odooService->read('account.move', ['id'], [['move_type', 'in', ['out_invoice', 'in_invoice']]], 1),
                    'count'    => 0
                ],
                'inventory' => [
                    'stock' => $this->odooService->read('stock.quant', ['id'], [['quantity', '!=', 0]], 1),
                    'count' => 0
                ]
            ];

            // Count records
            foreach ($summary as $module => &$data) {
                $data['count'] = count($data[key($data)]);
            }

            return $this->response->setJsonContent([
                'status' => true,
                'source' => 'odoo',
                'modules' => ['sales', 'purchase', 'invoicing', 'inventory'],
                'summary' => $summary
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => false,
                'error'  => $e->getMessage(),
                'modules' => ['sales', 'purchase', 'invoicing', 'inventory'],
                'source' => 'odoo'
            ]);
        }
    }

    /**
     * PRODUCT CRUD ENDPOINTS
     * Mengelola produk dari Odoo dan menyimpan ke database lokal
     */

    /**
     * Get all products
     * GET /api/inventory/products
     */
    public function inventoryProductsAction()
    {
        try {
            $products = Product::find([
                'order' => 'id DESC'
            ]);

            $data = [];
            foreach ($products as $product) {
                $data[] = [
                    'id' => $product->id,
                    'odoo_product_id' => $product->odoo_product_id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'category' => $product->category,
                    'description' => $product->description,
                    'list_price' => (float)$product->list_price,
                    'cost_price' => (float)$product->cost_price,
                    'quantity_on_hand' => (float)$product->quantity_on_hand,
                    'uom' => $product->uom,
                    'status' => $product->status,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ];
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'source' => 'local',
                'data' => $data
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get single product
     * GET /api/inventory/products/{id}
     */
    public function inventoryProductsGetAction($id)
    {
        try {
            $product = Product::findFirst($id);
            
            if (!$product) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Product not found'
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'source' => 'local',
                'data' => [
                    'id' => $product->id,
                    'odoo_product_id' => $product->odoo_product_id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'category' => $product->category,
                    'description' => $product->description,
                    'list_price' => (float)$product->list_price,
                    'cost_price' => (float)$product->cost_price,
                    'quantity_on_hand' => (float)$product->quantity_on_hand,
                    'uom' => $product->uom,
                    'status' => $product->status,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ]
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create product
     * POST /api/inventory/products/create
     */
    public function inventoryProductsCreateAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'POST method required'
            ]);
        }

        try {
            // Get JSON body
            $json = json_decode($this->request->getRawBody(), true);
            if (!$json) {
                $json = [];
                $json['name'] = $this->request->getPost('name');
                $json['code'] = $this->request->getPost('code');
                $json['category'] = $this->request->getPost('category');
                $json['description'] = $this->request->getPost('description');
                $json['list_price'] = $this->request->getPost('list_price');
                $json['cost_price'] = $this->request->getPost('cost_price');
                $json['quantity_on_hand'] = $this->request->getPost('quantity_on_hand');
                $json['uom'] = $this->request->getPost('uom');
                $json['status'] = $this->request->getPost('status') ?: 'active';
            }

            $product = new Product();
            $product->odoo_product_id = $json['odoo_product_id'] ?? null;
            $product->name = $json['name'] ?? '';
            $product->code = $json['code'] ?? '';
            $product->category = $json['category'] ?? '';
            $product->description = $json['description'] ?? '';
            $product->list_price = (float)($json['list_price'] ?? 0);
            $product->cost_price = (float)($json['cost_price'] ?? 0);
            $product->quantity_on_hand = (float)($json['quantity_on_hand'] ?? 0);
            $product->uom = $json['uom'] ?? 'Unit';
            $product->status = $json['status'] ?? 'active';

            if (!$product->save()) {
                $errors = [];
                foreach ($product->getMessages() as $message) {
                    $errors[] = (string)$message;
                }
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Failed to save product',
                    'errors' => $errors
                ]);
            }

            // Setelah save ke local DB, push ke Odoo jika belum ada odoo_product_id
            if (empty($product->odoo_product_id)) {
                try {
                    $this->initOdooService();
                    
                    // Map field nama Odoo
                    $odooData = [
                        'name' => $product->name,
                        'default_code' => $product->code,
                        'description' => $product->description,
                        'list_price' => (float)$product->list_price,
                        'standard_price' => (float)$product->cost_price,
                        'qty_available' => (float)$product->quantity_on_hand,
                        'type' => 'product'
                    ];
                    
                    $odooId = $this->odooService->create('product.product', $odooData);
                    
                    if (is_numeric($odooId) && $odooId > 0) {
                        // Update product dengan odoo_product_id
                        $product->odoo_product_id = $odooId;
                        $product->save();
                    }
                } catch (Exception $e) {
                    // Log error tapi tetap return success (product sudah tersimpan di local)
                    error_log('[Product Create] Failed to sync to Odoo: ' . $e->getMessage());
                }
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => [
                    'id' => $product->id,
                    'odoo_product_id' => $product->odoo_product_id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'category' => $product->category,
                    'description' => $product->description,
                    'list_price' => (float)$product->list_price,
                    'cost_price' => (float)$product->cost_price,
                    'quantity_on_hand' => (float)$product->quantity_on_hand,
                    'uom' => $product->uom,
                    'status' => $product->status
                ]
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update product
     * POST /api/inventory/products/update/{id}
     */
    public function inventoryProductsUpdateAction($id)
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'POST method required'
            ]);
        }

        try {
            $product = Product::findFirst($id);
            if (!$product) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Product not found'
                ]);
            }

            // Get JSON body
            $json = json_decode($this->request->getRawBody(), true);
            if (!$json) {
                $json = [];
                $json['name'] = $this->request->getPost('name');
                $json['code'] = $this->request->getPost('code');
                $json['category'] = $this->request->getPost('category');
                $json['description'] = $this->request->getPost('description');
                $json['list_price'] = $this->request->getPost('list_price');
                $json['cost_price'] = $this->request->getPost('cost_price');
                $json['quantity_on_hand'] = $this->request->getPost('quantity_on_hand');
                $json['uom'] = $this->request->getPost('uom');
                $json['status'] = $this->request->getPost('status');
            }

            // Update fields
            if (isset($json['name'])) $product->name = $json['name'];
            if (isset($json['code'])) $product->code = $json['code'];
            if (isset($json['category'])) $product->category = $json['category'];
            if (isset($json['description'])) $product->description = $json['description'];
            if (isset($json['list_price'])) $product->list_price = (float)$json['list_price'];
            if (isset($json['cost_price'])) $product->cost_price = (float)$json['cost_price'];
            if (isset($json['quantity_on_hand'])) $product->quantity_on_hand = (float)$json['quantity_on_hand'];
            if (isset($json['uom'])) $product->uom = $json['uom'];
            if (isset($json['status'])) $product->status = $json['status'];

            if (!$product->save()) {
                $errors = [];
                foreach ($product->getMessages() as $message) {
                    $errors[] = (string)$message;
                }
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Failed to update product',
                    'errors' => $errors
                ]);
            }

            // Setelah update local DB, sinkronisasi ke Odoo jika ada odoo_product_id
            if ($product->odoo_product_id) {
                try {
                    $this->initOdooService();
                    
                    $odooData = [
                        'name' => $product->name,
                        'default_code' => $product->code,
                        'description' => $product->description,
                        'list_price' => (float)$product->list_price,
                        'standard_price' => (float)$product->cost_price,
                        'qty_available' => (float)$product->quantity_on_hand
                    ];
                    
                    $this->odooService->write('product.product', [$product->odoo_product_id], $odooData);
                } catch (Exception $e) {
                    // Log error tapi tetap return success (product sudah terupdate di local)
                    error_log('[Product Update] Failed to sync to Odoo: ' . $e->getMessage());
                }
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => [
                    'id' => $product->id,
                    'odoo_product_id' => $product->odoo_product_id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'category' => $product->category,
                    'description' => $product->description,
                    'list_price' => (float)$product->list_price,
                    'cost_price' => (float)$product->cost_price,
                    'quantity_on_hand' => (float)$product->quantity_on_hand,
                    'uom' => $product->uom,
                    'status' => $product->status
                ]
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete product
     * GET /api/inventory/products/delete/{id}
     */
    public function inventoryProductsDeleteAction($id)
    {
        try {
            $product = Product::findFirst($id);
            if (!$product) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Product not found'
                ]);
            }

            // Delete related inventory_movements first
            $db = $this->di->get('db');
            $db->execute("DELETE FROM inventory_movements WHERE product_id = ?", [$id]);
            
            // Delete related sales_order_items
            $db->execute("DELETE FROM sales_order_items WHERE product_id = ?", [$id]);
            
            // Delete related purchase_order_items
            $db->execute("DELETE FROM purchase_order_items WHERE product_id = ?", [$id]);

            if (!$product->delete()) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Failed to delete product'
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync products from Odoo
     * GET /api/inventory/products/sync-odoo
     */
    public function inventoryProductsSyncOdooAction()
    {
        try {
            $this->initOdooService();
            
            // Get products from Odoo
            // Default fields untuk product.product model
            $fields = ['id', 'name', 'default_code', 'categ_id', 'description', 'list_price', 'standard_price', 'qty_available', 'uom_id'];
            $odooProducts = $this->odooService->read('product.product', $fields, [['active', '=', true]], 100);

            if (isset($odooProducts['error'])) {
                throw new Exception($odooProducts['error']);
            }

            $syncResult = [
                'created' => 0,
                'updated' => 0,
                'errors' => [],
                'products' => []
            ];

            foreach ($odooProducts as $odooProduct) {
                try {
                    $existingProduct = Product::findFirst([
                        'odoo_product_id = :odoo_id:',
                        'bind' => ['odoo_id' => $odooProduct['id']]
                    ]);

                    if ($existingProduct) {
                        // Update
                        $existingProduct->name = $odooProduct['name'] ?? '';
                        $existingProduct->code = $odooProduct['default_code'] ?? '';
                        $existingProduct->category = is_array($odooProduct['categ_id']) ? $odooProduct['categ_id'][1] : '';
                        $existingProduct->description = $odooProduct['description'] ?? '';
                        $existingProduct->list_price = (float)($odooProduct['list_price'] ?? 0);
                        $existingProduct->cost_price = (float)($odooProduct['standard_price'] ?? 0);
                        $existingProduct->quantity_on_hand = (float)($odooProduct['qty_available'] ?? 0);
                        $existingProduct->uom = is_array($odooProduct['uom_id']) ? $odooProduct['uom_id'][1] : 'Unit';

                        if ($existingProduct->save()) {
                            $syncResult['updated']++;
                            $syncResult['products'][] = ['id' => $existingProduct->id, 'name' => $existingProduct->name, 'action' => 'updated'];
                        } else {
                            $syncResult['errors'][] = "Failed to update product {$odooProduct['name']}";
                        }
                    } else {
                        // Create
                        $newProduct = new Product();
                        $newProduct->odoo_product_id = $odooProduct['id'];
                        $newProduct->name = $odooProduct['name'] ?? '';
                        $newProduct->code = $odooProduct['default_code'] ?? '';
                        $newProduct->category = is_array($odooProduct['categ_id']) ? $odooProduct['categ_id'][1] : '';
                        $newProduct->description = $odooProduct['description'] ?? '';
                        $newProduct->list_price = (float)($odooProduct['list_price'] ?? 0);
                        $newProduct->cost_price = (float)($odooProduct['standard_price'] ?? 0);
                        $newProduct->quantity_on_hand = (float)($odooProduct['qty_available'] ?? 0);
                        $newProduct->uom = is_array($odooProduct['uom_id']) ? $odooProduct['uom_id'][1] : 'Unit';
                        $newProduct->status = 'active';

                        if ($newProduct->save()) {
                            $syncResult['created']++;
                            $syncResult['products'][] = ['id' => $newProduct->id, 'name' => $newProduct->name, 'action' => 'created'];
                        } else {
                            $syncResult['errors'][] = "Failed to create product {$odooProduct['name']}";
                        }
                    }
                } catch (Exception $e) {
                    $syncResult['errors'][] = "Error processing {$odooProduct['name']}: " . $e->getMessage();
                }
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Sync from Odoo completed',
                'source' => 'odoo',
                'summary' => [
                    'created' => $syncResult['created'],
                    'updated' => $syncResult['updated'],
                    'total_processed' => count($odooProducts),
                    'errors_count' => count($syncResult['errors'])
                ],
                'errors' => $syncResult['errors'],
                'products' => $syncResult['products']
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }

    // ===== COMMERCE ENDPOINTS =====

    private function initCommerceService()
    {
        $this->commerceService = new CommerceService($this->odooService ?? null);
    }

    public function commerceSalesListAction()
    {
        try {
            $this->initCommerceService();
            
            $sales = SalesOrder::find(['order' => 'created_at DESC', 'limit' => 100]);
            
            $data = [];
            if ($sales) {
                foreach ($sales as $order) {
                    $data[] = [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'order_date' => $order->order_date,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status
                    ];
                }
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Exception $e) {
            error_log("commerceSalesListAction ERROR: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage(),
                'debug' => $e->getTraceAsString()
            ]);
        }
    }

    public function commerceSalesCreateAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['status' => 'error', 'message' => 'POST required']);
        }

        try {
            $this->initCommerceService();
            $json = json_decode($this->request->getRawBody(), true);
            
            $result = $this->commerceService->createSalesOrder($json);
            
            if (is_array($result) && isset($result['error'])) {
                return $this->response->setStatusCode(400)->setJsonContent($result);
            }

            // Convert object to array if needed
            $data = is_object($result) ? $result->toArray() : $result;

            // Get related invoice
            $invoice = Invoice::findFirst(['sales_order_id = :id:', 'bind' => ['id' => $data['id']]]);
            if ($invoice) {
                $data['invoice'] = $invoice->toArray();
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Sales order created with invoice',
                'data' => $data
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function commercePurchaseListAction()
    {
        try {
            $this->initCommerceService();
            
            $purchases = PurchaseOrder::find(['order' => 'created_at DESC', 'limit' => 100]);
            
            $data = [];
            if ($purchases) {
                foreach ($purchases as $order) {
                    $data[] = [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'supplier_name' => $order->supplier_name,
                        'order_date' => $order->order_date,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status
                    ];
                }
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Exception $e) {
            error_log("commercePurchaseListAction ERROR: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage(),
                'debug' => $e->getTraceAsString()
            ]);
        }
    }

    public function commercePurchaseCreateAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['status' => 'error', 'message' => 'POST required']);
        }

        try {
            $this->initCommerceService();
            $json = json_decode($this->request->getRawBody(), true);
            
            $result = $this->commerceService->createPurchaseOrder($json);
            
            if (is_array($result) && isset($result['error'])) {
                return $this->response->setStatusCode(400)->setJsonContent($result);
            }

            // Convert object to array if needed
            $data = is_object($result) ? $result->toArray() : $result;

            // Get related invoice
            $invoice = Invoice::findFirst(['purchase_order_id = :id:', 'bind' => ['id' => $data['id']]]);
            if ($invoice) {
                $data['invoice'] = $invoice->toArray();
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Purchase order created with invoice',
                'data' => $data
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function commercePurchaseConfirmAction($id)
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['status' => 'error', 'message' => 'POST required']);
        }

        try {
            $this->initCommerceService();
            $result = $this->commerceService->confirmPurchaseOrder($id);
            
            if (is_array($result) && isset($result['error'])) {
                return $this->response->setStatusCode(400)->setJsonContent($result);
            }

            // Convert object to array if needed
            $data = is_object($result) ? $result->toArray() : $result;

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Purchase order confirmed and inventory updated',
                'data' => $data
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function commerceInvoicesListAction()
    {
        try {
            $this->initCommerceService();
            
            $invoices = Invoice::find(['order' => 'created_at DESC', 'limit' => 100]);
            
            $data = [];
            if ($invoices) {
                foreach ($invoices as $invoice) {
                    $invoiceData = [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'invoice_date' => $invoice->invoice_date,
                        'total_amount' => $invoice->total_amount,
                        'tax_amount' => $invoice->tax_amount,
                        'status' => $invoice->status,
                        'sales_order_id' => $invoice->sales_order_id,
                        'purchase_order_id' => $invoice->purchase_order_id,
                        'notes' => $invoice->notes,
                        'reference' => null,
                        'reference_type' => null
                    ];

                    // Get related Sales Order info
                    if ($invoice->sales_order_id) {
                        $salesOrder = SalesOrder::findFirst($invoice->sales_order_id);
                        if ($salesOrder) {
                            $invoiceData['reference'] = $salesOrder->order_number;
                            $invoiceData['reference_type'] = 'Sales Order';
                            $invoiceData['customer_name'] = $salesOrder->customer_name;
                            $invoiceData['partner_name'] = $salesOrder->customer_name;
                        }
                    }

                    // Get related Purchase Order info
                    if ($invoice->purchase_order_id) {
                        $purchaseOrder = PurchaseOrder::findFirst($invoice->purchase_order_id);
                        if ($purchaseOrder) {
                            $invoiceData['reference'] = $purchaseOrder->order_number;
                            $invoiceData['reference_type'] = 'Purchase Order';
                            $invoiceData['supplier_name'] = $purchaseOrder->supplier_name;
                            $invoiceData['partner_name'] = $purchaseOrder->supplier_name;
                        }
                    }

                    $data[] = $invoiceData;
                }
            }
            
            return $this->response->setJsonContent([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Exception $e) {
            error_log("commerceInvoicesListAction ERROR: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage(),
                'debug' => $e->getTraceAsString()
            ]);
        }
    }

    public function commerceInvoicesCreateAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['status' => 'error', 'message' => 'POST required']);
        }

        try {
            $this->initCommerceService();
            $json = json_decode($this->request->getRawBody(), true);
            
            $result = $this->commerceService->createInvoice($json);
            
            if (is_array($result) && isset($result['error'])) {
                return $this->response->setStatusCode(400)->setJsonContent($result);
            }

            // Convert object to array if needed
            $data = is_object($result) ? $result->toArray() : $result;

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Invoice created',
                'data' => $data
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function commerceInventoryMovementsAction()
    {
        try {
            $this->initCommerceService();
            $movements = $this->commerceService->getInventoryMovements();
            
            return $this->response->setJsonContent([
                'status' => 'success',
                'data' => $movements
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get single sales order
     * GET /api/commerce/sales/{id}
     */
    public function commerceSalesGetAction($id)
    {
        try {
            $order = SalesOrder::findFirst($id);
            
            if (!$order) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Sales order not found'
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'order_date' => $order->order_date,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'notes' => $order->notes
                ]
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update sales order
     * POST /api/commerce/sales/update/{id}
     */
    public function commerceSalesUpdateAction($id)
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['status' => 'error', 'message' => 'POST required']);
        }

        try {
            $order = SalesOrder::findFirst($id);
            
            if (!$order) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Sales order not found'
                ]);
            }

            $json = json_decode($this->request->getRawBody(), true);
            
            if (isset($json['customer_name'])) $order->customer_name = $json['customer_name'];
            if (isset($json['order_date'])) $order->order_date = $json['order_date'];
            if (isset($json['total_amount'])) $order->total_amount = $json['total_amount'];
            if (isset($json['status'])) $order->status = $json['status'];
            if (isset($json['notes'])) $order->notes = $json['notes'];

            if (!$order->save()) {
                return $this->response->setStatusCode(400)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Failed to update sales order'
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Sales order updated',
                'data' => $order->toArray()
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get single purchase order
     * GET /api/commerce/purchase/{id}
     */
    public function commercePurchaseGetAction($id)
    {
        try {
            $order = PurchaseOrder::findFirst($id);
            
            if (!$order) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Purchase order not found'
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'supplier_name' => $order->supplier_name,
                    'order_date' => $order->order_date,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'notes' => $order->notes
                ]
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update purchase order
     * POST /api/commerce/purchase/update/{id}
     */
    public function commercePurchaseUpdateAction($id)
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['status' => 'error', 'message' => 'POST required']);
        }

        try {
            $order = PurchaseOrder::findFirst($id);
            
            if (!$order) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Purchase order not found'
                ]);
            }

            $json = json_decode($this->request->getRawBody(), true);
            
            if (isset($json['supplier_name'])) $order->supplier_name = $json['supplier_name'];
            if (isset($json['order_date'])) $order->order_date = $json['order_date'];
            if (isset($json['total_amount'])) $order->total_amount = $json['total_amount'];
            if (isset($json['status'])) $order->status = $json['status'];
            if (isset($json['notes'])) $order->notes = $json['notes'];

            if (!$order->save()) {
                return $this->response->setStatusCode(400)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Failed to update purchase order'
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Purchase order updated',
                'data' => $order->toArray()
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get single invoice
     * GET /api/commerce/invoices/{id}
     */
    public function commerceInvoicesGetAction($id)
    {
        try {
            $invoice = Invoice::findFirst($id);
            
            if (!$invoice) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Invoice not found'
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'data' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'sales_order_id' => $invoice->sales_order_id,
                    'purchase_order_id' => $invoice->purchase_order_id,
                    'invoice_date' => $invoice->invoice_date,
                    'total_amount' => $invoice->total_amount,
                    'tax_amount' => $invoice->tax_amount,
                    'status' => $invoice->status,
                    'notes' => $invoice->notes
                ]
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update invoice
     * POST /api/commerce/invoices/update/{id}
     */
    public function commerceInvoicesUpdateAction($id)
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['status' => 'error', 'message' => 'POST required']);
        }

        try {
            $invoice = Invoice::findFirst($id);
            
            if (!$invoice) {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Invoice not found'
                ]);
            }

            $json = json_decode($this->request->getRawBody(), true);
            
            if (isset($json['invoice_date'])) $invoice->invoice_date = $json['invoice_date'];
            if (isset($json['total_amount'])) $invoice->total_amount = $json['total_amount'];
            if (isset($json['tax_amount'])) $invoice->tax_amount = $json['tax_amount'];
            if (isset($json['status'])) $invoice->status = $json['status'];
            if (isset($json['notes'])) $invoice->notes = $json['notes'];

            if (!$invoice->save()) {
                return $this->response->setStatusCode(400)->setJsonContent([
                    'status' => 'error',
                    'message' => 'Failed to update invoice'
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Invoice updated',
                'data' => $invoice->toArray()
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get customers from Odoo
     */
    public function customersAction()
    {
        try {
            $this->initOdooService();
            
            $customers = $this->odooService->getCustomers(50);
            
            if (isset($customers['error'])) {
                return $this->response->setStatusCode(400)->setJsonContent([
                    'status' => 'error',
                    'message' => $customers['error']
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'data' => $customers
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add customer to Odoo
     */
    public function customersAddAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'POST method required'
            ]);
        }

        try {
            $this->initOdooService();

            $data = [
                'name'   => $this->request->getPost('name'),
                'email'  => $this->request->getPost('email'),
                'phone'  => $this->request->getPost('phone'),
                'city'   => $this->request->getPost('city'),
            ];

            if (empty($data['name'])) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Customer name is required'
                ]);
            }

            $result = $this->odooService->addCustomer($data);

            if (isset($result['error'])) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => $result['error']
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Customer added successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete customer from Odoo
     */
    public function customersDeleteAction()
    {
        if (!$this->request->isDelete() && !$this->request->isPost()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'DELETE or POST method required'
            ]);
        }

        try {
            $this->initOdooService();

            // Get ID from URL parameter or POST data
            $customerId = $this->dispatcher->getParam('id');
            if (!$customerId) {
                $customerId = $this->request->getPost('id');
            }

            if (!$customerId) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Customer ID is required'
                ]);
            }

            $result = $this->odooService->deleteCustomer($customerId);

            if (isset($result['error'])) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => $result['error']
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/odoo/vendors - Get all vendors
     */
    public function vendorsAction()
    {
        try {
            $this->initOdooService();

            if ($this->request->isGet()) {
                $result = $this->odooService->getVendors(50);
                return $this->response->setJsonContent($result);
            }

            return $this->response->setStatusCode(405)->setJsonContent([
                'status' => 'error',
                'message' => 'GET method required'
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * POST /api/odoo/vendors - Create new vendor
     */
    public function vendorsCreateAction()
    {
        try {
            $this->initOdooService();

            if ($this->request->isPost()) {
                $data = $this->request->getJsonRawBody(true);
                
                if (empty($data['name'])) {
                    return $this->response->setStatusCode(400)->setJsonContent([
                        'status' => 'error',
                        'success' => false,
                        'message' => 'Vendor name is required'
                    ]);
                }

                $result = $this->odooService->createVendor($data);
                
                if (!$result['success']) {
                    return $this->response->setStatusCode(400)->setJsonContent([
                        'status' => 'error',
                        'success' => false,
                        'message' => $result['error']
                    ]);
                }

                return $this->response->setJsonContent([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Vendor created successfully',
                    'data' => $result
                ]);
            }

            return $this->response->setStatusCode(405)->setJsonContent([
                'status' => 'error',
                'message' => 'POST method required'
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * DELETE /api/odoo/vendors/{id} - Delete vendor
     */
    public function vendorsDeleteAction()
    {
        if (!$this->request->isDelete() && !$this->request->isPost()) {
            return $this->response->setStatusCode(405)->setJsonContent([
                'status' => 'error',
                'success' => false,
                'message' => 'DELETE or POST method required'
            ]);
        }

        try {
            $this->initOdooService();

            // Get ID from URL parameter or POST data
            $vendorId = $this->dispatcher->getParam('id');
            if (!$vendorId) {
                $vendorId = $this->request->getPost('id');
            }

            if (!$vendorId) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Vendor ID is required'
                ]);
            }

            $result = $this->odooService->deleteVendor($vendorId);

            if (!$result['success']) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'success' => false,
                    'message' => $result['error']
                ]);
            }

            return $this->response->setJsonContent([
                'status' => 'success',
                'success' => true,
                'message' => 'Vendor deleted successfully'
            ]);
        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJsonContent([
                'status' => 'error',
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

}