<?php

use Phalcon\Mvc\Controller;

class ApiController extends Controller
{
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
                    'phalcon_db' => 'connected'
                ]
            ]);
    }
}