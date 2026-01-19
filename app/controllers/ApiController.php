<?php

use Phalcon\Mvc\Controller;

class ApiController extends Controller
{
    private $odooService;

    public function initialize()
    {
        // Load OdooService
        require_once APP_PATH . '/library/OdooService.php';
        
        // Initialize Odoo Service
        $this->odooService = new OdooService([
            'url'      => 'http://host.docker.internal:8069',  // Akses Odoo di host machine
            'database' => 'odoo',
            'username' => 'admin',
            'password' => 'admin'
        ]);
    }

    /**
     * Get notes dari database lokal Phalcon
     */
    public function notesAction()
    {
        $this->view->disable();

        $notes = Notes::find([
            'order' => 'created_at DESC'
        ]);

        $data = [];

        foreach ($notes as $note) {
            $data[] = [
                'id'        => $note->id,
                'title'     => $note->judul,
                'content'   => $note->isi,
                'note_date' => $note->tanggal,
                'created_at'=> $note->created_at
            ];
        }

        return $this->response
            ->setContentType('application/json')
            ->setJsonContent([
                'status' => true,
                'data'   => $data
            ]);
    }

    /**
     * Get data dari Odoo API
     */
    public function odooNotesAction()
    {
        $this->view->disable();

        try {
            // Get notes dari Odoo model (adjust sesuai model Odoo Anda)
            $odooData = $this->odooService->read(
                'sale.order',  // Ganti dengan model Odoo yang sesuai
                ['name', 'state', 'create_date'],
                [],
                20
            );

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status'    => true,
                    'source'    => 'odoo_api',
                    'data'      => $odooData,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
        } catch (Exception $e) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setJsonContent([
                    'status'  => false,
                    'error'   => $e->getMessage(),
                    'source'  => 'odoo_api'
                ]);
        }
    }

    /**
     * Sync data dari Phalcon ke Odoo
     */
    public function syncToOdooAction()
    {
        $this->view->disable();

        try {
            $notes = Notes::find();
            $syncedCount = 0;

            foreach ($notes as $note) {
                $odooData = [
                    'name'        => $note->judul,
                    'description' => $note->isi,
                    'date_start'  => $note->tanggal
                ];

                // Create atau update di Odoo
                $result = $this->odooService->create('sale.order', $odooData);
                
                if (!isset($result['error'])) {
                    $syncedCount++;
                }
            }

            return $this->response
                ->setContentType('application/json')
                ->setJsonContent([
                    'status'       => true,
                    'synced_count' => $syncedCount,
                    'message'      => "Synced $syncedCount notes to Odoo"
                ]);
        } catch (Exception $e) {
            return $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setJsonContent([
                    'status' => false,
                    'error'  => $e->getMessage()
                ]);
        }
    }

    /**
     * Health check endpoint
     */
    public function healthAction()
    {
        $this->view->disable();

        return $this->response
            ->setContentType('application/json')
            ->setJsonContent([
                'status'  => true,
                'message' => 'Phalcon API is running',
                'services' => [
                    'phalcon_db' => 'connected',
                    'odoo_api'   => 'configured'
                ]
            ]);
    }
}