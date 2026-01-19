<?php

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        // Tampilkan landing page utama
        $this->view->setLayout('main');
    }

    public function logoutAction()
    {
        // Kalau nanti ada session/login, bisa dibersihkan di sini
        // $this->session->destroy();

        // Kembalikan ke halaman utama
        return $this->response->redirect('/');
    }
}
