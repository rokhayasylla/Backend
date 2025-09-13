<?php

namespace App\Http\Controllers;

use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

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
        try {
            $invoice = $this->invoiceService->sendByEmail($id);
            return response()->json(['message' => 'Facture envoyée par email'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'envoi de l\'email',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function myInvoices()
    {
        $invoices = $this->invoiceService->getUserInvoices(auth()->id());
        return response()->json($invoices, 200);
    }

    /**
     * Télécharger la facture en PDF
     */
    public function download(string $id)
    {
        try {
            $invoice = $this->invoiceService->show($id);

            // Vérifier l'autorisation : seul le propriétaire peut télécharger sa facture
            if (auth()->id() !== $invoice->order->user_id && !auth()->user()->hasRole('admin')) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            // Générer ou récupérer le PDF
            $pdfPath = $this->invoiceService->generateOrGetPDF($invoice);

            if (!Storage::exists($pdfPath)) {
                return response()->json(['error' => 'Fichier PDF non trouvé'], 404);
            }

            $pdfContent = Storage::get($pdfPath);
            $fileName = 'facture-' . $invoice->invoice_number . '.pdf';

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du téléchargement',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser la facture en PDF dans le navigateur
     */
    public function preview(string $id)
    {
        try {
            $invoice = $this->invoiceService->show($id);

            // Vérifier l'autorisation
            if (auth()->id() !== $invoice->order->user_id && !auth()->user()->hasRole('admin')) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            // Générer ou récupérer le PDF
            $pdfPath = $this->invoiceService->generateOrGetPDF($invoice);

            if (!Storage::exists($pdfPath)) {
                return response()->json(['error' => 'Fichier PDF non trouvé'], 404);
            }

            $pdfContent = Storage::get($pdfPath);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="facture-' . $invoice->invoice_number . '.pdf"'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la prévisualisation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer manuellement le PDF d'une facture
     */
    public function generatePDF(string $id)
    {
        try {
            $invoice = $this->invoiceService->show($id);

            // Vérifier l'autorisation (admin seulement)
            if (!auth()->user()->hasRole('admin')) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            $pdfPath = $this->invoiceService->generateInvoicePDF($invoice, true); // Force regeneration

            return response()->json([
                'message' => 'PDF généré avec succès',
                'pdf_path' => $pdfPath
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération du PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
