<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;

class InvoiceService
{
    public function index()
    {
        return Invoice::with('order.user')->latest()->get();
    }

    public function store(string $orderId)
    {
        $order = Order::with(['user', 'orderItems.product'])->findOrFail($orderId);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount
        ]);

        // Here you would generate PDF and save path
        // $pdfPath = $this->generateInvoicePDF($invoice);
        // $invoice->update(['pdf_path' => $pdfPath]);

        return $invoice->load('order.user');
    }

    public function show(string $id)
    {
        return Invoice::with(['order.user', 'order.orderItems.product'])->findOrFail($id);
    }

    public function sendByEmail(string $id)
    {
        $invoice = $this->show($id);

        // Here you would implement email sending logic
        // Mail::to($invoice->order->user->email)->send(new InvoiceMail($invoice));

        $invoice->update(['sent_by_email' => true]);

        return $invoice;
    }

    public function getUserInvoices(string $userId)
    {
        return Invoice::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with('order')->latest()->get();
    }
}
