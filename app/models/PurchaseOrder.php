<?php

use Phalcon\Mvc\Model;

class PurchaseOrder extends Model
{
    public $id;
    public $odoo_order_id;      // ID dari Odoo purchase.order
    public $order_number;        // Nomor PO
    public $supplier_name;       // Nama supplier
    public $order_date;          // Tanggal order
    public $total_amount;        // Total harga
    public $status;              // draft/confirmed/done/cancelled
    public $notes;               // Catatan
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('purchase_orders');
    }

    public function validation()
    {
        if (empty($this->order_number)) {
            $this->appendMessage(new \Phalcon\Messages\Message('Nomor order harus diisi'));
            return false;
        }
        if (empty($this->supplier_name)) {
            $this->appendMessage(new \Phalcon\Messages\Message('Nama supplier harus diisi'));
            return false;
        }
        return true;
    }
}
