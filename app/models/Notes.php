<?php

use Phalcon\Mvc\Model;

class Notes extends Model
{
    public $id;
    public $judul;
    public $isi;
    public $tanggal;

    public function initialize()
    {
        $this->setSource('notes');
    }
}
