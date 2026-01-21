<?php

class OdooService
{
    private $odooUrl;
    private $odooDatabase;
    private $odooUsername;
    private $odooPassword;
    private $uid;
    private $models;
    private $connected = false;
    private $errorMessage = '';

    public function __construct($config = [])
    {
        $this->odooUrl = $config['url'] ?? 'http://host.docker.internal:8069';
        $this->odooDatabase = $config['database'] ?? 'odoo';
        $this->odooUsername = $config['username'] ?? 'admin';
        $this->odooPassword = $config['password'] ?? 'admin';
        
        // Try to authenticate with Odoo (don't fail if offline)
        try {
            $this->authenticate();
            $this->connected = true;
        } catch (Exception $e) {
            $this->connected = false;
            $this->errorMessage = $e->getMessage();
            // Log tapi jangan throw exception
            error_log("Odoo Connection Warning: " . $e->getMessage());
        }
    }

    /**
     * Check if Odoo is connected
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Get last error message
     */
    public function getError()
    {
        return $this->errorMessage;
    }

    /**
     * Authenticate dengan Odoo menggunakan JSON-RPC
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
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        
        if (isset($data['result']) && is_numeric($data['result'])) {
            $this->uid = $data['result'];
            return true;
        }
        
        // Jika gagal auth, throw exception dengan detail response
        throw new Exception('Failed to authenticate with Odoo. Response: ' . json_encode($data));
    }

    /**
     * Call Odoo JSON-RPC API
     */
    private function callRpc($endpoint, $params = [], $method = 'call')
    {
        $url = $this->odooUrl . '/jsonrpc';
        
        $payload = [
            'jsonrpc' => '2.0',
            'method'  => $method,
            'params'  => [
                'service' => 'object',
                'method'  => 'execute_kw',
                'args'    => [$this->odooDatabase, $this->uid, $this->odooPassword, ...(array)$params]
            ],
            'id'      => mt_rand()
        ];

        if ($endpoint === '/web/session/authenticate') {
            $payload = [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => $params,
                'id'      => mt_rand()
            ];
            $url = $this->odooUrl . $endpoint;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Odoo API Error: HTTP $httpCode");
        }

        return json_decode($response, true);
    }

    /**
     * Get data dari Odoo model
     */
    public function read($model, $fields = [], $domain = [], $limit = 10)
    {
        if (!$this->connected) {
            return [
                'error' => 'Odoo service is not available',
                'message' => $this->errorMessage,
                'data' => []
            ];
        }

        try {
            $rpcParams = [
                $model,
                'search',
                [$domain],
                ['limit' => $limit]
            ];

            $response = $this->callRpc('', $rpcParams);
            
            if (isset($response['result'])) {
                $ids = $response['result'];
                
                $readParams = [
                    $model,
                    'read',
                    [$ids],
                    ['fields' => $fields]
                ];
                
                $readResponse = $this->callRpc('', $readParams);
                return $readResponse['result'] ?? [];
            }
            
            return [];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create data di Odoo
     */
    public function create($model, $values)
    {
        if (!$this->connected) {
            return ['error' => 'Odoo service is not available', 'message' => $this->errorMessage];
        }

        try {
            $rpcParams = [
                $model,
                'create',
                [$values]
            ];

            $response = $this->callRpc('', $rpcParams);
            return $response['result'] ?? null;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update data di Odoo
     */
    public function write($model, $ids, $values)
    {
        if (!$this->connected) {
            return ['error' => 'Odoo service is not available', 'message' => $this->errorMessage];
        }

        try {
            $rpcParams = [
                $model,
                'write',
                [$ids, $values]
            ];

            $response = $this->callRpc('', $rpcParams);
            return $response['result'] ?? true;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete data di Odoo
     */
    public function delete($model, $ids)
    {
        if (!$this->connected) {
            return ['error' => 'Odoo service is not available', 'message' => $this->errorMessage];
        }

        try {
            $rpcParams = [
                $model,
                'unlink',
                [$ids]
            ];

            $response = $this->callRpc('', $rpcParams);
            return $response['result'] ?? true;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
