<?php

use Phalcon\Mvc\Model;

class Customer extends Model
{
    public $id;
    public $odoo_customer_id;    // ID dari Odoo customer
    public $name;                // Nama customer
    public $reference;           // Reference
    public $email;               // Email
    public $phone;               // Nomor telepon
    public $city;                // Kota
    public $country;             // Negara
    public $street;              // Jalan
    public $status;              // Status (active/inactive)
    public $created_at;
    public $updated_at;

    public function initialize()
    {
        $this->setSource('customers');
    }

    /**
     * Validate required fields
     */
    public function validation()
    {
        if (empty($this->name)) {
            $this->appendMessage(new \Phalcon\Messages\Message('Nama customer harus diisi'));
            return false;
        }
        return true;
    }
}
