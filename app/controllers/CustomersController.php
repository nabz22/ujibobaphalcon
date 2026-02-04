<?php

use Phalcon\Mvc\Controller;

require_once(APP_PATH . '/models/Customer.php');
require_once(APP_PATH . '/library/OdooService.php');

class CustomersController extends Controller
{
    private $odooService;

    public function initialize()
    {
        // Initialize Odoo Service
        $this->initOdooService();
    }

    private function initOdooService()
    {
        try {
            $this->odooService = new OdooService([
                'url'      => 'http://odoo:8069',
                'database' => 'odoo',
                'username' => 'admin',
                'password' => 'admin'
            ]);
        } catch (Exception $e) {
            echo "Error initializing OdooService: " . $e->getMessage();
        }
    }

    /**
     * Display list of customers from Odoo
     */
    public function indexAction()
    {
        $customers = [];
        
        try {
            // Get customers from Odoo
            $customers = $this->odooService->getCustomers(50);
        } catch (Exception $e) {
            $this->view->error = "Error fetching customers: " . $e->getMessage();
        }

        $this->view->customers = $customers;
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
    }

    /**
     * Delete customer from Odoo
     */
    public function deleteAction()
    {
        if (!$this->request->isPost() && !$this->request->isDelete()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        $customerId = $this->request->getPost('id');

        if (!$customerId) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Customer ID is required'
            ]);
        }

        try {
            // Delete customer from Odoo
            $result = $this->odooService->deleteCustomer($customerId);
            
            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ]);
        } catch (Exception $e) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add customer to Odoo
     */
    public function addAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'POST method required'
            ]);
        }

        $data = [
            'name'  => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'city'  => $this->request->getPost('city'),
        ];

        if (empty($data['name'])) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Customer name is required'
            ]);
        }

        try {
            // Add customer to Odoo
            $result = $this->odooService->addCustomer($data);
            
            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Customer added successfully',
                'data'    => $result
            ]);
        } catch (Exception $e) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
