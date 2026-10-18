<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGroupProductPrice extends Model
{
    protected $fillable =[

        "customer_group_id", "product_id", "price"
    ];
}
