<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $guarded = ['invoice_number'];

    protected $casts = [
        'amount' => 'decimal:2',
        'sent_by_email' => 'boolean'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            // Générer un numéro unique avec format : INV-YYYYMMDD-XXXXX
            $date = now()->format('Ymd');
            $lastInvoice = static::whereDate('created_at', today())
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastInvoice ?
                intval(substr($lastInvoice->invoice_number, -5)) + 1 : 1;

            $invoice->invoice_number = 'FAC-' . $date . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
        });
    }
}
