<?php

namespace App\Models;

class Vendor
{
    public $id;
    public $name;
    public $reference;
    public $email;
    public $phone;
    public $city;
    public $address;
    public $country;
    public $is_company;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->name = $data['name'] ?? null;
            $this->reference = $data['ref'] ?? null;
            $this->email = $data['email'] ?? null;
            $this->phone = $data['phone'] ?? null;
            $this->city = $data['city'] ?? null;
            $this->address = $data['street'] ?? null;
            $this->country = $data['country_id'] ?? null;
            $this->is_company = $data['is_company'] ?? false;
        }
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'reference' => $this->reference,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'address' => $this->address,
            'country' => $this->country,
            'is_company' => $this->is_company,
        ];
    }
}
