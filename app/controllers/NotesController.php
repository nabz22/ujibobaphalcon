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
        $note->judul   = $this->request->getPost('judul');
        $note->isi     = $this->request->getPost('isi');
        $note->tanggal = date('Y-m-d');

        if ($note->save()) {
            return $this->response->redirect('/notes');
        }

        // fallback jika gagal
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

        $note->judul = $this->request->getPost('judul');
        $note->isi   = $this->request->getPost('isi');

        if ($note->save()) {
            return $this->response->redirect('/notes');
        }

        return $this->response->redirect('/notes');
    }
}
