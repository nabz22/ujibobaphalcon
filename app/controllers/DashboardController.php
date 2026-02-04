<?php

use Phalcon\Mvc\Controller;

class DashboardController extends Controller
{
    /**
     * Redirect to notes.html
     */
    public function indexAction()
    {
        return $this->response->redirect('/notes.html');
    }
}
