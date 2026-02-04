<?php

use Phalcon\Mvc\Model;

class SalesOrder extends Model
{
    public $id;
    public $odoo_order_id;      // ID dari Odoo sale.order
    public $order_number;        // Nomor SO
    public $customer_name;       // Nama customer
    public $order_date;          // Tanggal order
    public $total_amount;        // Total harga
    public $status;              // draft/confirmed/done/cancelled
    public $notes;               // Catatan
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('sales_orders');
    }

    public function validation()
    {
        if (empty($this->order_number)) {
            $this->appendMessage(new \Phalcon\Messages\Message('Nomor order harus diisi'));
            return false;
        }
        if (empty($this->customer_name)) {
            $this->appendMessage(new \Phalcon\Messages\Message('Nama customer harus diisi'));
            return false;
        }
        return true;
    }
}
