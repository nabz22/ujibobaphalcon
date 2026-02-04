<?php

use Phalcon\Mvc\Model;

class Product extends Model
{
    public $id;
    public $odoo_product_id;    // ID dari Odoo product
    public $name;               // Nama produk
    public $code;               // Code/SKU
    public $category;           // Kategori produk
    public $description;        // Deskripsi
    public $list_price;         // Harga jual
    public $cost_price;         // Harga beli
    public $quantity_on_hand;   // Stok tersedia
    public $uom;                // Unit of Measurement
    public $status;             // Status produk (active/inactive)
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('products');
    }

    /**
     * Validate required fields
     */
    public function validation()
    {
        if (empty($this->name)) {
            $this->appendMessage(new \Phalcon\Messages\Message('Nama produk harus diisi'));
            return false;
        }
        return true;
    }
}
