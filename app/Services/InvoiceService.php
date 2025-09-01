<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

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

//        $invoice->update(['sent_by_email' => true]);

        //return $invoice;
        return $this->sendInvoiceByEmail($invoice);
    }

    public function getUserInvoices(string $userId)
    {
        return Invoice::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with('order')->latest()->get();
    }

    /**
     * Créer une facture pour une commande donnée
     */
    public function createInvoiceForOrder(Order $order)
    {
        // Vérifier si une facture n'existe pas déjà pour cette commande
        $existingInvoice = Invoice::where('order_id', $order->id)->first();

        if ($existingInvoice) {
            return $existingInvoice->load('order.user');
        }

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount
        ]);

        // Générer le PDF (optionnel)
        $this->generateInvoicePDF($invoice);

        return $invoice->load('order.user');
    }

    /**
     * Envoyer une facture par email
     */
    public function sendInvoiceByEmail(Invoice $invoice)
    {
        try {
            // Charger les relations nécessaires si pas déjà chargées
            if (!$invoice->relationLoaded('order')) {
                $invoice->load(['order.user', 'order.orderItems.product', 'order.orderItems.pack']);
            }

            // Envoyer l'email
            Mail::to($invoice->order->user->email)->send(new InvoiceMail($invoice));

            // Marquer comme envoyée
            $invoice->update(['sent_by_email' => true]);

            return $invoice;
        } catch (\Exception $e) {
            // Log l'erreur
            \Log::error('Erreur envoi email facture: ' . $e->getMessage());
            throw new \Exception('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }

    /**
     * Générer le PDF de la facture (optionnel)
     * Vous pouvez utiliser une bibliothèque comme DomPDF ou mPDF
     */
    private function generateInvoicePDF(Invoice $invoice)
    {
        try {
            // Exemple avec DomPDF (vous devez installer le package)
            // $pdf = PDF::loadView('pdf.invoice', compact('invoice'));
            // $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
            // Storage::put($filename, $pdf->output());
            // $invoice->update(['pdf_path' => $filename]);

            // Pour l'instant, on simule juste le chemin
            $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
            $invoice->update(['pdf_path' => $filename]);

        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF facture: ' . $e->getMessage());
            // Ne pas faire échouer si la génération PDF échoue
        }
    }
}
