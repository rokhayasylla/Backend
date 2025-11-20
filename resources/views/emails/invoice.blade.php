<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - Commande livrée</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #28a745;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
        }
        .invoice-details {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .delivered-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 25px;
            background-color: #28a745;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .footer {
            background-color: #6c757d;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 0.9em;
        }
        .highlight-box {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>📦 Commande Livrée !</h1>
    <p>Votre facture est prête, {{ $user->full_name }}</p>
</div>

<div class="content">
    <div class="highlight-box">
        <h2 style="margin-top: 0; color: #155724;">
            ✅ <span class="delivered-badge">Livré</span>
        </h2>
        <p style="margin-bottom: 0; color: #155724;">
            <strong>Votre commande {{ $order->order_number }} a été livrée avec succès !</strong>
        </p>
    </div>

    <h3>Informations de la facture</h3>

    <div class="invoice-details">
        <p><strong>Numéro de facture:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Date de facturation:</strong> {{ $invoice->created_at->format('d/m/Y à H:i') }}</p>
        <p><strong>Commande:</strong> {{ $order->order_number }}</p>
        <p><strong>Date de livraison:</strong> {{ $order->delivered_at->format('d/m/Y à H:i') }}</p>
        <p><strong>Montant:</strong> <strong style="color: #007bff; font-size: 1.2em;">{{ number_format($invoice->amount, 2) }} fcfa</strong></p>
    </div>

    <div class="invoice-details">
        <h4>Détails de la commande</h4>
        <p><strong>Adresse de livraison:</strong> {{ $order->delivery_address }}</p>
        <p><strong>Mode de paiement:</strong>
            @if($order->payment_method == 'cash_on_delivery')
                Paiement à la livraison
            @else
                Paiement en ligne
            @endif
        </p>
        @if($order->payment_status == 'paid')
            <p style="color: #28a745;"><strong>✅ Paiement confirmé</strong></p>
        @endif
    </div>

    @if($invoice->pdf_path)
        <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
            <p><strong>📄 Facture PDF</strong></p>
            <p>Votre facture détaillée est jointe à cet email en format PDF.</p>
        </div>
    @endif

    <div style="margin-top: 20px; padding: 15px; background-color: #d1ecf1; border-radius: 5px; border-left: 4px solid #007bff;">
        <p><strong>🙏 Merci pour votre commande !</strong></p>
        <p>Nous espérons que vous êtes satisfait(e) de votre achat. N'hésitez pas à nous faire part de vos commentaires.</p>
    </div>
</div>

<div class="footer">
    <p>Merci de votre confiance et à bientôt !</p>
    <p>Pour toute question concernant votre facture, contactez-nous.</p>
</div>
</body>
</html>
