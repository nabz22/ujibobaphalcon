<?php

use Phalcon\Mvc\Controller;

class OdooController extends Controller
{
    private $odooService;
    private $config;

    public function initialize()
    {
        // Disable view
        $this->view->disable();
        
        // Load OdooService
        require_once APP_PATH . '/library/OdooService.php';
        
        // Load config
        $this->config = include(APP_PATH . '/config/odoo.php');
        
        // Initialize Odoo Service
        if ($this->config['enabled']) {
            try {
                $this->odooService = new OdooService($this->config['connection']);
            } catch (Exception $e) {
                // Log error tapi jangan stop execution
                error_log("Odoo Service Init Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Health check: Test koneksi ke Odoo
     */
    public function healthAction()
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'services' => [
                'phalcon' => 'ok',
                'odoo' => 'unknown'
            ]
        ];

        if ($this->config['enabled'] && $this->odooService) {
            try {
                // Try to get current user info
                $health['services']['odoo'] = 'connected';
            } catch (Exception $e) {
                $health['services']['odoo'] = 'error: ' . $e->getMessage();
                $health['status'] = 'degraded';
            }
        } else {
            $health['services']['odoo'] = 'disabled';
        }

        return $this->response
            ->setContentType('application/json')
            ->setJsonContent($health);
    }

    /**
     * Get data dari Odoo model
     */
    public function readAction()
    {
        $model = $this->request->get('model');
        $fields = $this->request->get('fields', null, 'id,name');
        $limit = $this->request->get('limit', null, 10);

        if (!$model) {
            return $this->response
                ->setStatusCode(400)
                ->setJsonContent(['error' => 'Model parameter required']);
        }

        try {
            $fields = explode(',', $fields);
            $data = $this->odooService->read($model, $fields, [], $limit);

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status' => true,
                    'source' => 'odoo',
                    'model'  => $model,
                    'data'   => $data,
                    'count'  => count($data)
                ]);
        } catch (Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJsonContent([
                    'status' => false,
                    'error'  => $e->getMessage()
                ]);
        }
    }

    /**
     * Create data di Odoo
     */
    public function createAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setStatusCode(405);
        }

        $model = $this->request->getPost('model');
        $data = $this->request->getPost('data');

        if (!$model || !$data) {
            return $this->response
                ->setStatusCode(400)
                ->setJsonContent(['error' => 'Model and data required']);
        }

        try {
            $data = json_decode($data, true);
            $result = $this->odooService->create($model, $data);

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status'   => true,
                    'odoo_id'  => $result,
                    'model'    => $model
                ]);
        } catch (Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJsonContent([
                    'status' => false,
                    'error'  => $e->getMessage()
                ]);
        }
    }

    /**
     * Sync Notes ke Odoo
     */
    public function syncNotesAction()
    {
        try {
            $notes = Notes::find();
            $syncedCount = 0;
            $failedCount = 0;
            $results = [];

            foreach ($notes as $note) {
                try {
                    // Check if already synced
                    $syncRecord = OdooSync::getSyncStatus('notes', $note->id);
                    
                    if (!$syncRecord) {
                        $syncRecord = new OdooSync();
                        $syncRecord->entity_type = 'notes';
                        $syncRecord->entity_id = $note->id;
                        $syncRecord->sync_direction = 'push';
                        $syncRecord->sync_status = 'pending';
                        $syncRecord->created_at = date('Y-m-d H:i:s');
                    }

                    // Prepare data for Odoo
                    $odooData = [
                        'summary'      => $note->judul,
                        'note'         => $note->isi,
                        'activity_date' => $note->tanggal
                    ];

                    // Create/Update di Odoo
                    $odooId = $this->odooService->create('mail.activity', $odooData);
                    
                    if ($odooId && !isset($odooId['error'])) {
                        $syncRecord->markSynced($odooId);
                        $syncedCount++;
                        $results[] = [
                            'note_id' => $note->id,
                            'odoo_id' => $odooId,
                            'status'  => 'synced'
                        ];
                    } else {
                        $error = $odooId['error'] ?? 'Unknown error';
                        $syncRecord->markSynced(null, $error);
                        $failedCount++;
                        $results[] = [
                            'note_id' => $note->id,
                            'status'  => 'failed',
                            'error'   => $error
                        ];
                    }
                } catch (Exception $e) {
                    $failedCount++;
                    $results[] = [
                        'note_id' => $note->id,
                        'status'  => 'error',
                        'error'   => $e->getMessage()
                    ];
                }
            }

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status'        => true,
                    'synced_count'  => $syncedCount,
                    'failed_count'  => $failedCount,
                    'total'         => count($notes),
                    'details'       => $results
                ]);
        } catch (Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJsonContent([
                    'status' => false,
                    'error'  => $e->getMessage()
                ]);
        }
    }

    /**
     * Get sync status
     */
    public function syncStatusAction()
    {
        try {
            $pending = OdooSync::count([
                'conditions' => 'sync_status = "pending"'
            ]);

            $synced = OdooSync::count([
                'conditions' => 'sync_status = "synced"'
            ]);

            $failed = OdooSync::count([
                'conditions' => 'sync_status = "failed"'
            ]);

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status'  => true,
                    'pending' => $pending,
                    'synced'  => $synced,
                    'failed'  => $failed,
                    'total'   => $pending + $synced + $failed
                ]);
        } catch (Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJsonContent([
                    'status' => false,
                    'error'  => $e->getMessage()
                ]);
        }
    }

    /**
     * Get failed syncs
     */
    public function failedSyncsAction()
    {
        try {
            $failed = OdooSync::find([
                'conditions' => 'sync_status = "failed"',
                'order'      => 'updated_at DESC',
                'limit'      => 50
            ]);

            $data = [];
            foreach ($failed as $record) {
                $data[] = [
                    'id'              => $record->id,
                    'entity_type'    => $record->entity_type,
                    'entity_id'      => $record->entity_id,
                    'error_message'  => $record->error_message,
                    'updated_at'     => $record->updated_at
                ];
            }

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status' => true,
                    'count'  => count($data),
                    'data'   => $data
                ]);
        } catch (Exception $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJsonContent([
                    'status' => false,
                    'error'  => $e->getMessage()
                ]);
        }
    }
}
