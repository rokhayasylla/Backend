<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Envoyer une notification de confirmation de commande
     */
    public function sendOrderConfirmation(Order $order)
    {
        try {
            // Charger les relations nécessaires
            $order->load(['orderItems.product', 'orderItems.pack', 'user']);

            // Envoyer l'email en utilisant la queue
            Mail::to($order->user->email)
                ->send(new OrderConfirmationMail($order));

            \Log::info('Email de confirmation de commande envoyé: ' . $order->user->email);

            return true;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoie de l\'email de confirmation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer une facture par email
     */
    public function sendInvoice(Invoice $invoice)
    {
        try {
            // Charger les relations nécessaires si pas déjà chargées
            if (!$invoice->relationLoaded('order')) {
                $invoice->load(['order.user', 'order.orderItems.product', 'order.orderItems.pack']);
            }
            // Envoyer l'email en utilisant la queue
            Mail::to($invoice->order->user->email)
                ->send(new InvoiceMail($invoice));

            // Marquer comme envoyée
            $invoice->update(['sent_by_email' => true]);

            \Log::info('Email de facture envoyé pour: ' . $invoice->order->user->email);

            return true;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise en queue de l\'email de facture: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer une notification de changement de statut de commande
     */
    public function sendOrderStatusUpdate(Order $order, string $oldStatus, string $newStatus)
    {
        try {
            // Vous pouvez créer différents types de notifications selon le statut
            switch ($newStatus) {
                case 'preparing':
                    // Mail pour "en préparation"
                    break;
                case 'ready':
                    // Mail pour "prêt"
                    break;
                case 'delivering':
                    // Mail pour "en livraison"
                    break;
                case 'delivered':
                    // Pour le statut "livré", on envoie la facture via InvoiceService
                    break;
                case 'cancelled':
                    // Mail pour "annulé"
                    break;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Erreur notification changement statut: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer une notification simple de changement de statut
     */
    private function sendStatusNotification(Order $order, string $message)
    {
        try {
            Mail::raw($message . "\n\nCommande: " . $order->order_number, function ($mail) use ($order) {
                $mail->to($order->user->email)
                    ->subject('Mise à jour de votre commande #' . $order->order_number);
            });

            \Log::info('✅ Notification de statut envoyée à: ' . $order->user->email);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur envoi notification statut: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer des rappels ou notifications générales
     */
    public function sendGeneralNotification(string $email, string $subject, string $message)
    {
        // Implémentation pour des notifications générales
    }
}
