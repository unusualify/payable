<?php

namespace Unusualify\Payable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
  use HasFactory, SoftDeletes;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = config('payable.table');

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'payment_gateway',
    'order_id',
    'price',
    'currency_id',
    'status',
    'email', 
    'installment',
    'parameters',
    'response',
  ];

}