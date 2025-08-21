<?php

namespace App\Http\Controllers;

use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index()
    {
        $invoices = $this->invoiceService->index();
        return response()->json($invoices, 200);
    }

    public function store(Request $request)
    {
        $request->validate(['order_id' => 'required|exists:orders,id']);

        $invoice = $this->invoiceService->store($request->order_id);
        return response()->json($invoice, 201);
    }

    public function show(string $id)
    {
        $invoice = $this->invoiceService->show($id);
        return response()->json($invoice, 200);
    }

    public function sendEmail(string $id)
    {
        $invoice = $this->invoiceService->sendByEmail($id);
        return response()->json(['message' => 'Facture envoyée par email'], 200);
    }

    public function myInvoices()
    {
        $invoices = $this->invoiceService->getUserInvoices(auth()->id());
        return response()->json($invoices, 200);
    }
}
