<?php

use Phalcon\Mvc\Model;

class Notes extends Model
{
    public $id;
    public $judul;
    public $kategori;
    public $prioritas;
    public $status;
    public $isi;
    public $tanggal;
    public $odoo_model_id;      // ID dari Odoo document
    public $odoo_model_type;    // Tipe model Odoo (e.g., sale.order, purchase.order)
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('notes');
    }
}