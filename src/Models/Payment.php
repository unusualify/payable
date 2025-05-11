<?php

namespace Unusualify\Payable\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Unusualify\Payable\Models\Enums\PaymentStatus;
use Unusualify\Payable\Payable;

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
        'currency',
        'status',
        'email',
        'installment',
        'parameters',
        'response'
    ];

    public function __construct(array $attributes = [])
    {
        $this->mergeFillable(config('payable.additional_fillable', []));
        $this->mergeCasts([
            'parameters' => 'object',
            'response' => 'object',
            'status' => PaymentStatus::class,
        ]);
        $this->append([
            'is_refundable',
            'is_cancelable',
        ]);

        parent::__construct($attributes);
    }

    protected function serviceClass(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Payable::getServiceClass($this->payment_gateway),
        );
    }

    protected function isRefundable(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->status === PaymentStatus::COMPLETED && $this->serviceClass::hasRefund($this->response),
        );
    }

    protected function isCancelable(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->status === PaymentStatus::COMPLETED && $this->serviceClass::hasCancel($this->response),
        );
    }

    public function getTable()
    {
        return config('payable.table', parent::getTable());
    }

}
