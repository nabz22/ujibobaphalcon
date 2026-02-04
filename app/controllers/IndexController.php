<?php

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        // Redirect ke notes.html sebagai halaman utama
        return $this->response->redirect('/notes.html');
    }

    public function logoutAction()
    {
        // Kembalikan ke halaman utama
        return $this->response->redirect('/notes.html');
    }
}
