<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande</title>
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
            background-color: #007bff;
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
        .order-details {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .item:last-child {
            border-bottom: none;
        }
        .total {
            font-weight: bold;
            font-size: 1.2em;
            color: #007bff;
            text-align: right;
            margin-top: 15px;
        }
        .footer {
            background-color: #6c757d;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 0.9em;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: #28a745;
            color: white;
            font-size: 0.8em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>🎉 Commande Confirmée !</h1>
    <p>Merci pour votre commande, {{ $user->full_name }}</p>
</div>

<div class="content">
    <h2>Détails de la commande</h2>

    <div class="order-details">
        <p><strong>Numéro de commande:</strong> {{ $order->order_number }}</p>
        <p><strong>Date de commande:</strong> {{ $order->created_at->format('d/m/Y à H:i') }}</p>
        <p><strong>Statut:</strong> <span class="status">{{ $order->status }}</span></p>
        <p><strong>Adresse de livraison:</strong> {{ $order->delivery_address }}</p>
        <p><strong>Mode de paiement:</strong>
            @if($order->payment_method == 'cash_on_delivery')
                Paiement à la livraison
            @else
                Paiement en ligne
            @endif
        </p>
    </div>

    <h3>Articles commandés</h3>

    <div class="order-details">
        @foreach($orderItems as $item)
            <div class="item">
                <div>
                    <strong>{{ $item->item_name }}</strong>
                    <br>
                    <small>Quantité: {{ $item->quantity }} × {{ number_format($item->unit_price, 2) }} fcfa</small>
                </div>
                <div>
                    <strong>{{ number_format($item->total_price, 2) }} fcfa</strong>
                </div>
            </div>
        @endforeach

        @if($order->discount_amount > 0)
            <div class="item" style="color: #28a745;">
                <div><strong>Remise</strong></div>
                <div><strong>-{{ number_format($order->discount_amount, 2) }} fcfa</strong></div>
            </div>
        @endif

        <div class="total">
            Total: {{ number_format($order->total_amount, 2) }} fcfa
        </div>
    </div>

    <div style="margin-top: 20px; padding: 15px; background-color: #d1ecf1; border-radius: 5px; border-left: 4px solid #007bff;">
        <p><strong>📋 Prochaines étapes:</strong></p>
        <ul>
            <li>Votre commande est en cours de traitement</li>
            <li>Vous recevrez une notification lorsqu'elle sera prête</li>
            <li>Notre équipe prépare votre commande avec soin</li>
        </ul>
    </div>
</div>

<div class="footer">
    <p>Merci de votre confiance !</p>
    <p>Pour toute question, n'hésitez pas à nous contacter.</p>
</div>
</body>
</html>
