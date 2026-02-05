<?php

/**
 * OdooService - Menghubungkan Phalcon dengan Odoo API
 * Menggunakan JSON-RPC protocol untuk komunikasi
 */
class OdooService
{
    private $odooUrl;
    private $odooDatabase;
    private $odooUsername;
    private $odooPassword;
    private $uid;
    private $models;

    public function __construct($config = [])
    {
        // Default configuration - bisa override dari config file
        // Inside Docker: gunakan 'odoo:8069' (service name dalam docker-compose network)
        // Outside Docker: gunakan 'http://localhost:8077'
        $this->odooUrl = $config['url'] ?? 'http://odoo:8069';
        $this->odooDatabase = $config['database'] ?? 'odoo';
        $this->odooUsername = $config['username'] ?? 'admin';
        $this->odooPassword = $config['password'] ?? 'admin';
        
        // Authenticate dengan Odoo
        $this->authenticate();
    }

    /**
     * Authenticate dengan Odoo menggunakan JSON-RPC
     * @throws Exception
     */
    private function authenticate()
    {
        $url = $this->odooUrl . '/jsonrpc';
        
        $payload = [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'params'  => [
                'service' => 'common',
                'method'  => 'authenticate',
                'args'    => [
                    $this->odooDatabase,
                    $this->odooUsername,
                    $this->odooPassword,
                    []
                ]
            ],
            'id'      => mt_rand()
        ];
        
        $response = $this->callJsonRpc($url, $payload);
        
        if (isset($response['result']) && is_numeric($response['result'])) {
            $this->uid = $response['result'];
            return true;
        }
        
        throw new Exception('Gagal authenticate dengan Odoo: ' . json_encode($response));
    }

    /**
     * Call Odoo JSON-RPC
     */
    private function callJsonRpc($url, $payload)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("CURL Error: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode");
        }

        return json_decode($response, true);
    }

    /**
     * Get data dari Odoo model
     * @param string $model - Nama model Odoo (e.g. 'res.partner', 'sale.order')
     * @param array $fields - Fields yang diambil
     * @param array $domain - Filter criteria
     * @param int $limit - Limit jumlah records
     * @return array
     */
    public function read($model, $fields = [], $domain = [], $limit = 10)
    {
        try {
            $url = $this->odooUrl . '/jsonrpc';
            
            // Search untuk get IDs
            $searchPayload = [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $this->odooDatabase,
                        $this->uid,
                        $this->odooPassword,
                        $model,
                        'search',
                        [$domain],
                        ['limit' => $limit]
                    ]
                ],
                'id'      => mt_rand()
            ];

            $searchResponse = $this->callJsonRpc($url, $searchPayload);
            
            if (!isset($searchResponse['result'])) {
                return ['error' => 'Search failed: ' . json_encode($searchResponse)];
            }

            $ids = $searchResponse['result'];
            
            if (empty($ids)) {
                return [];
            }

            // Read data dari IDs yang ketemu
            $readPayload = [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $this->odooDatabase,
                        $this->uid,
                        $this->odooPassword,
                        $model,
                        'read',
                        [$ids],
                        ['fields' => $fields]
                    ]
                ],
                'id'      => mt_rand()
            ];
            
            $readResponse = $this->callJsonRpc($url, $readPayload);
            return $readResponse['result'] ?? [];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create record di Odoo
     * @param string $model - Model name
     * @param array $values - Data untuk create
     * @return int|array - ID record yang dibuat atau error
     */
    public function create($model, $values)
    {
        try {
            $url = $this->odooUrl . '/jsonrpc';
            
            $payload = [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $this->odooDatabase,
                        $this->uid,
                        $this->odooPassword,
                        $model,
                        'create',
                        [$values]
                    ]
                ],
                'id'      => mt_rand()
            ];

            error_log('[OdooService CREATE] Payload: ' . json_encode($payload));
            $response = $this->callJsonRpc($url, $payload);
            error_log('[OdooService CREATE] Response: ' . json_encode($response));
            
            // Check for error in response
            if (isset($response['error'])) {
                $errorMsg = $response['error']['message'] ?? 'Unknown error';
                error_log('[OdooService CREATE ERROR] ' . $errorMsg);
                return ['error' => $errorMsg];
            }
            
            // Return result or null
            $result = $response['result'] ?? null;
            error_log('[OdooService CREATE] Result: ' . json_encode($result));
            
            return $result;
            
        } catch (Exception $e) {
            error_log('[OdooService CREATE EXCEPTION] ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update record di Odoo
     * @param string $model - Model name
     * @param array $ids - Record IDs
     * @param array $values - Data untuk update
     * @return bool|array
     */
    public function write($model, $ids, $values)
    {
        try {
            $url = $this->odooUrl . '/jsonrpc';
            
            $payload = [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $this->odooDatabase,
                        $this->uid,
                        $this->odooPassword,
                        $model,
                        'write',
                        [$ids, $values]
                    ]
                ],
                'id'      => mt_rand()
            ];

            $response = $this->callJsonRpc($url, $payload);
            return true;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete record di Odoo
     */
    public function delete($model, $ids)
    {
        try {
            $url = $this->odooUrl . '/jsonrpc';
            
            $payload = [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $this->odooDatabase,
                        $this->uid,
                        $this->odooPassword,
                        $model,
                        'unlink',
                        [$ids]
                    ]
                ],
                'id'      => mt_rand()
            ];

            $response = $this->callJsonRpc($url, $payload);
            return true;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get list of partners dari Odoo
     */
    public function getPartners($limit = 20)
    {
        return $this->read('res.partner', ['name', 'email', 'phone'], [], $limit);
    }

    /**
     * Get list of customers dari Odoo (res.partner dengan is_company=false)
     */
    public function getCustomers($limit = 50)
    {
        try {
            error_log('[OdooService] Getting customers with limit: ' . $limit);
            
            // res.partner dengan is_company=false adalah customers
            $fields = ['id', 'name', 'ref', 'email', 'phone', 'city', 'street', 'country_id', 'is_company'];
            // Get all partners without is_company filter for now
            $domain = [];
            
            $customers = $this->search_read('res.partner', $domain, $fields, $limit);
            
            error_log('[OdooService] Found customers: ' . count($customers));
            
            return [
                'success' => true,
                'data' => $customers,
                'count' => count($customers)
            ];
        } catch (Exception $e) {
            error_log('[OdooService ERROR] Getting customers: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Add customer to Odoo
     */
    public function addCustomer($data)
    {
        $values = [
            'name' => $data['name'],
            'is_company' => $data['is_company'] ?? false,
            'customer_rank' => 1  // Mark as customer
        ];

        if (!empty($data['email'])) {
            $values['email'] = $data['email'];
        }
        if (!empty($data['phone'])) {
            $values['phone'] = $data['phone'];
        }
        if (!empty($data['city'])) {
            $values['city'] = $data['city'];
        }
        if (!empty($data['address'])) {
            $values['street'] = $data['address'];
        }
        if (!empty($data['street'])) {
            $values['street'] = $data['street'];
        }

        return $this->create('res.partner', $values);
    }

    /**
     * Delete customer from Odoo
     */
    public function deleteCustomer($customerId)
    {
        return $this->delete('res.partner', [$customerId]);
    }

    /**
     * Get list of sales orders dari Odoo
     */
    public function getSalesOrders($limit = 20)
    {
        return $this->read('sale.order', 
            ['name', 'partner_id', 'amount_total', 'state', 'date_order'], 
            [], 
            $limit
        );
    }

    /**
     * Get UID yang authenticated
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Call action/method on Odoo record (e.g., action_post untuk confirm invoice)
     * @param string $model - Model name
     * @param array $ids - Record IDs
     * @param string $method - Method/action name to call
     * @return mixed
     */
    public function callAction($model, $ids, $method)
    {
        try {
            $url = $this->odooUrl . '/jsonrpc';
            
            $payload = [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $this->odooDatabase,
                        $this->uid,
                        $this->odooPassword,
                        $model,
                        $method,
                        [$ids]
                    ]
                ],
                'id'      => mt_rand()
            ];

            error_log('[OdooService CALL ACTION] Model: ' . $model . ', Method: ' . $method . ', IDs: ' . json_encode($ids));
            $response = $this->callJsonRpc($url, $payload);
            error_log('[OdooService CALL ACTION] Response: ' . json_encode($response));
            
            if (isset($response['error'])) {
                return ['error' => $response['error']['message'] ?? 'Action failed'];
            }
            
            return $response['result'] ?? true;
            
        } catch (Exception $e) {
            error_log('[OdooService CALL ACTION ERROR] ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Search and Read from Odoo
     * @param string $model
     * @param array $domain
     * @param array $fields
     * @param int $limit
     * @return array
     */
    public function search_read($model, $domain = [], $fields = [], $limit = 10)
    {
        try {
            error_log('[OdooService] search_read: ' . $model . ', domain: ' . json_encode($domain));
            
            $url = $this->odooUrl . '/jsonrpc';
            
            // First search for IDs
            $searchPayload = [
                'jsonrpc'   => '2.0',
                'method'    => 'call',
                'params'    => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $this->odooDatabase,
                        $this->uid,
                        $this->odooPassword,
                        $model,
                        'search',
                        [$domain],
                        ['limit' => $limit]
                    ]
                ],
                'id'      => mt_rand()
            ];

            $searchResponse = $this->callJsonRpc($url, $searchPayload);
            
            if (!isset($searchResponse['result'])) {
                error_log('[OdooService] search_read search failed: ' . json_encode($searchResponse));
                return [];
            }

            $ids = $searchResponse['result'];
            
            if (empty($ids)) {
                return [];
            }

            // Then read the data
            $readPayload = [
                'jsonrpc'   => '2.0',
                'method'    => 'call',
                'params'    => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $this->odooDatabase,
                        $this->uid,
                        $this->odooPassword,
                        $model,
                        'read',
                        [$ids],
                        ['fields' => $fields]
                    ]
                ],
                'id'      => mt_rand()
            ];

            $readResponse = $this->callJsonRpc($url, $readPayload);
            
            return $readResponse['result'] ?? [];
        } catch (Exception $e) {
            error_log('[OdooService search_read ERROR] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Vendors from Odoo
     * @param int $limit
     * @return array
     */
    public function getVendors($limit = 50)
    {
        try {
            error_log('[OdooService] Getting vendors with limit: ' . $limit);
            
            // res.partner dengan supplier_rank > 0 adalah vendor, atau coba dengan is_company
            $fields = ['id', 'name', 'ref', 'email', 'phone', 'city', 'street', 'country_id', 'is_company'];
            // Filter untuk partner yang bisa menjadi supplier (supplier_rank >= 1 atau baru ditambah)
            $domain = [];  // Get semua partners untuk debugging
            
            $vendors = $this->search_read('res.partner', $domain, $fields, $limit);
            
            error_log('[OdooService] Found vendors: ' . count($vendors));
            
            return [
                'success' => true,
                'data' => $vendors,
                'count' => count($vendors)
            ];
        } catch (Exception $e) {
            error_log('[OdooService ERROR] Getting vendors: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Create Vendor in Odoo
     * @param array $vendorData
     * @return array
     */
    public function createVendor($vendorData)
    {
        try {
            error_log('[OdooService] Creating vendor: ' . json_encode($vendorData));
            
            $data = [
                'name' => $vendorData['name'] ?? '',
                'email' => $vendorData['email'] ?? '',
                'phone' => $vendorData['phone'] ?? '',
                'street' => $vendorData['street'] ?? $vendorData['address'] ?? '',
                'city' => $vendorData['city'] ?? '',
                'is_company' => $vendorData['is_company'] ?? false,
                'supplier_rank' => 1  // Mark as supplier
            ];
            
            $vendorId = $this->create('res.partner', $data);
            
            error_log('[OdooService] Vendor created with ID: ' . $vendorId);
            
            return [
                'success' => true,
                'id' => $vendorId,
                'message' => 'Vendor created successfully'
            ];
        } catch (Exception $e) {
            error_log('[OdooService ERROR] Creating vendor: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete Vendor from Odoo
     * @param int $vendorId
     * @return array
     */
    public function deleteVendor($vendorId)
    {
        try {
            error_log('[OdooService] Deleting vendor: ' . $vendorId);
            
            $this->delete('res.partner', [$vendorId]);
            
            error_log('[OdooService] Vendor deleted successfully');
            
            return [
                'success' => true,
                'message' => 'Vendor deleted successfully'
            ];
        } catch (Exception $e) {
            error_log('[OdooService ERROR] Deleting vendor: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test connection ke Odoo
     */
    public function testConnection()
    {
        try {
            $partners = $this->read('res.partner', ['id', 'name'], [], 1);
            return [
                'status' => 'success',
                'message' => 'Connected to Odoo successfully',
                'uid' => $this->uid,
                'database' => $this->odooDatabase
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
