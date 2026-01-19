<?php

use Phalcon\Mvc\Controller;

class NotesController extends Controller
{
    /**
     * Tampilkan semua catatan
     */
    public function indexAction()
    {
        $this->view->setLayout('main');

        $this->view->notes = Notes::find([
            'order' => 'id DESC'
        ]);
    }

    /**
     * Simpan catatan baru
     */
    public function createAction()
    {
        if (!$this->request->isPost()) {
            return $this->response->redirect('/notes');
        }

        $note = new Notes();
        $note->judul   = $this->request->getPost('judul', 'string');
        $note->isi     = $this->request->getPost('isi', 'string');
        $note->tanggal = date('Y-m-d');

        if (!$note->save()) {
            echo "<pre>";
            foreach ($note->getMessages() as $message) {
                echo $message . PHP_EOL;
            }
            exit;
        }

        return $this->response->redirect('/notes');
    }

    /**
     * Hapus catatan
     */
    public function deleteAction($id)
    {
        $note = Notes::findFirst($id);

        if ($note) {
            $note->delete();
        }

        return $this->response->redirect('/notes');
    }

    /**
     * Form edit catatan
     */
    public function editAction($id)
    {
        $this->view->setLayout('main');

        $note = Notes::findFirst($id);
        if (!$note) {
            return $this->response->redirect('/notes');
        }

        $this->view->note = $note;
    }

    /**
     * Update catatan
     */
    public function updateAction($id)
    {
        if (!$this->request->isPost()) {
            return $this->response->redirect('/notes');
        }

        $note = Notes::findFirst($id);
        if (!$note) {
            return $this->response->redirect('/notes');
        }

        $note->judul = $this->request->getPost('judul', 'string');
        $note->isi   = $this->request->getPost('isi', 'string');

        if (!$note->save()) {
            echo "<pre>";
            foreach ($note->getMessages() as $message) {
                echo $message . PHP_EOL;
            }
            exit;
        }

        return $this->response->redirect('/notes');
    }
}
