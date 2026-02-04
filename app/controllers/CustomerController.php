<?php

use Phalcon\Mvc\Controller;

class CustomerController extends Controller
{
    public function indexAction()
    {
        // Load data dari Odoo via API
        $odooService = $this->getDI()->get('odooService');
        
        try {
            $customers = $odooService->getCustomers(50); // Get top 50 customers
            $this->view->setVar('customers', $customers);
        } catch (Exception $e) {
            $this->view->setVar('error', $e->getMessage());
            $this->view->setVar('customers', []);
        }
    }

    public function addAction()
    {
        if ($this->request->isPost()) {
            $odooService = $this->getDI()->get('odooService');
            
            try {
                $data = [
                    'name' => $this->request->getPost('name'),
                    'email' => $this->request->getPost('email'),
                    'phone' => $this->request->getPost('phone'),
                    'city' => $this->request->getPost('city'),
                ];
                
                $result = $odooService->createCustomer($data);
                $this->response->setJsonContent(['success' => true, 'data' => $result]);
            } catch (Exception $e) {
                $this->response->setStatusCode(400, 'Bad Request');
                $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()]);
            }
            return $this->response;
        }
    }

    public function deleteAction()
    {
        if ($this->request->isPost()) {
            $odooService = $this->getDI()->get('odooService');
            $id = $this->request->getPost('id');
            
            try {
                $result = $odooService->deleteCustomer($id);
                $this->response->setJsonContent(['success' => true, 'data' => $result]);
            } catch (Exception $e) {
                $this->response->setStatusCode(400, 'Bad Request');
                $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()]);
            }
            return $this->response;
        }
    }
}
