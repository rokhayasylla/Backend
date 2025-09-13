<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // Générer automatiquement le PDF
        $this->generateInvoicePDF($invoice);

        return $invoice->load('order.user');
    }

    public function show(string $id)
    {
        return Invoice::with(['order.user', 'order.orderItems.product'])->findOrFail($id);
    }

    public function sendByEmail(string $id)
    {
        $invoice = $this->show($id);
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

        // Générer le PDF
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

            // S'assurer que le PDF existe
            $this->generateOrGetPDF($invoice);

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
     * Générer ou récupérer le PDF d'une facture
     */
    public function generateOrGetPDF(Invoice $invoice)
    {
        // Si le PDF existe déjà, le retourner
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return $invoice->pdf_path;
        }

        // Sinon le générer
        return $this->generateInvoicePDF($invoice);
    }

    /**
     * Générer le PDF de la facture
     */
    public function generateInvoicePDF(Invoice $invoice, $forceRegenerate = false)
    {
        try {
            // Si le PDF existe et qu'on ne force pas la régénération
            if (!$forceRegenerate && $invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
                return $invoice->pdf_path;
            }

            // Charger les relations nécessaires
            if (!$invoice->relationLoaded('order')) {
                $invoice->load(['order.user', 'order.orderItems.product', 'order.orderItems.pack']);
            }

            // Générer le PDF avec DomPDF
            $pdf = Pdf::loadView('emails.pdf', [
                'invoice' => $invoice,
                'order' => $invoice->order,
                'user' => $invoice->order->user
            ]);
            // Configuration du PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

            // Nom du fichier
            $filename = 'invoices/' . $invoice->invoice_number . '.pdf';

            // Sauvegarder le PDF
            Storage::put($filename, $pdf->output());

            // Mettre à jour le modèle avec le chemin
            $invoice->update(['pdf_path' => $filename]);

            return $filename;

        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF facture: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer le PDF d'une facture
     */
    public function deletePDF(Invoice $invoice)
    {
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            Storage::delete($invoice->pdf_path);
            $invoice->update(['pdf_path' => null]);
        }
    }

    /**
     * Obtenir la taille du fichier PDF
     */
    public function getPDFSize(Invoice $invoice)
    {
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return Storage::size($invoice->pdf_path);
        }
        return 0;
    }

    /**
     * Vérifier si le PDF existe
     */
    public function pdfExists(Invoice $invoice)
    {
        return $invoice->pdf_path && Storage::exists($invoice->pdf_path);
    }
}
