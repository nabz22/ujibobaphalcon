<?php

use Phalcon\Mvc\Model;

class Invoice extends Model
{
    public $id;
    public $odoo_invoice_id;     // ID dari Odoo account.invoice
    public $invoice_number;      // Nomor invoice
    public $sales_order_id;      // Link ke sales order
    public $purchase_order_id;   // Link ke purchase order
    public $invoice_date;        // Tanggal invoice
    public $total_amount;        // Total harga
    public $tax_amount;          // Pajak
    public $status;              // draft/posted/paid/cancelled
    public $notes;               // Catatan
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('invoices');
    }

    public function validation()
    {
        if (empty($this->invoice_number)) {
            $this->appendMessage(new \Phalcon\Messages\Message('Nomor invoice harus diisi'));
            return false;
        }
        if ($this->total_amount === null || $this->total_amount < 0) {
            $this->appendMessage(new \Phalcon\Messages\Message('Total harus lebih dari atau sama dengan 0'));
            return false;
        }
        return true;
    }
}
