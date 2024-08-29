<?php

namespace Unusualify\Payable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class Payment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_gateway',
        'order_id',
        'amount',
        'currency_id',
        'status',
        'email',
        'installment',
        'parameters',
        'response',
        'payment_service_id',
        'price_id'
    ];

    public function getTable()
    {
        return config('payable.table', parent::getTable());
    }

}
