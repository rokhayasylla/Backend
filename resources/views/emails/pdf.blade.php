<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            line-height: 1.4;
            color: #333;
            font-size: 12px;
            background-color: #fff;
        }

        /* Dégradé orange principal */
        .gradient-bg {
            background: linear-gradient(90deg, #f97316, #f59e0b);
        }

        .header {
            border-bottom: 3px solid #f97316;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-info {
            text-align: left;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #f97316;
            margin-bottom: 5px;
        }

        .invoice-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 20px 0;
            text-transform: uppercase;
        }

        .invoice-meta {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .invoice-meta .left, .invoice-meta .right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .invoice-details {
            background-color: #fff7ed;
            padding: 15px;
            border: 1px solid #fde68a;
            margin-bottom: 20px;
        }

        .client-info {
            background-color: #fff;
            border: 1px solid #fde68a;
            padding: 15px;
            margin-bottom: 20px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .items-table th {
            background: linear-gradient(90deg, #f97316, #f59e0b);
            color: white;
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .items-table td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #fff7ed;
        }

        .total-section {
            margin-top: 30px;
            text-align: right;
        }

        .total-row {
            margin: 5px 0;
            padding: 5px 0;
        }

        .total-final {
            font-size: 18px;
            font-weight: bold;
            color: #f97316;
            border-top: 2px solid #f97316;
            padding-top: 10px;
            margin-top: 10px;
        }

        .payment-info {
            background-color: #ffedd5;
            border: 1px solid #fdba74;
            border-left: 4px solid #f97316;
            padding: 15px;
            margin: 20px 0;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .status-delivered {
            background: linear-gradient(90deg, #f97316, #f59e0b);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 10px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-orange { color: #f97316; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
<div class="header">
    <div class="company-info">
        <div class="company-name">{{ config('RSTrading') }}</div>
        <div>123 Rue Penitence</div>
        <div>75001 Dakar, Senegal</div>
        <div>Tél: +221 78 287 69 51 </div>
        <div>Email: RSTrading@gmail.com</div>
    </div>

    <div class="invoice-title">Facture</div>
</div>

<div class="invoice-meta">
    <div class="left">
        <div class="client-info">
            <h3 style="margin-top: 0; color: #f97316;">Facturé à :</h3>
            <div class="font-bold">{{ $user->full_name }}</div>
            <div>{{ $user->email }}</div>
            @if($user->phone)
                <div>{{ $user->phone }}</div>
            @endif
            <div style="margin-top: 10px;">
                <strong>Adresse de livraison :</strong><br>
                {{ $order->delivery_address }}
            </div>
        </div>
    </div>

    <div class="right">
        <div class="invoice-details">
            <div><strong>Numéro :</strong> {{ $invoice->invoice_number }}</div>
            <div><strong>Date :</strong> {{ $invoice->created_at->format('d/m/Y') }}</div>
            <div><strong>Commande :</strong> {{ $order->order_number }}</div>
            @if($order->delivered_at)
                <div><strong>Livré le :</strong> {{ $order->delivered_at->format('d/m/Y à H:i') }}</div>
                <div style="margin-top: 10px;">
                    <span class="status-delivered">✓ Livré</span>
                </div>
            @endif
        </div>
    </div>
</div>

@if($order->orderItems && $order->orderItems->count() > 0)
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 50%;">Article</th>
            <th style="width: 15%;">Quantité</th>
            <th style="width: 15%;">Prix unitaire</th>
            <th style="width: 20%;" class="text-right">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->orderItems as $item)
            <tr>
                <td>
                    <div class="font-bold">
                        @if($item->product)
                            {{ $item->product->name }}
                        @elseif($item->pack)
                            {{ $item->pack->name }} (Pack)
                        @else
                            Article supprimé
                        @endif
                    </div>
                    @if($item->product && $item->product->description)
                        <div style="font-size: 10px; color: #666; margin-top: 2px;">
                            {{ Str::limit($item->product->description, 80) }}
                        </div>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }} fcfa</td>
                <td class="text-right font-bold">{{ number_format($item->total_price, 2) }} fcfa</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<div class="total-section">
    <div class="total-row">
        <strong>Sous-total : {{ number_format($order->orderItems->sum('total_price'), 2) }} fcfa</strong>
    </div>

    @if($order->delivery_fee > 0)
        <div class="total-row">
            Frais de livraison : {{ number_format($order->delivery_fee, 2) }} fcfa
        </div>
    @endif

    @if($order->discount_amount > 0)
        <div class="total-row text-green">
            Réduction : -{{ number_format($order->discount_amount, 2) }} fcfa
        </div>
    @endif

    <div class="total-final">
        TOTAL : {{ number_format($invoice->amount, 2) }} fcfa
    </div>
</div>

<div class="payment-info">
    <h4 style="margin-top: 0; color: #155724;">Informations de paiement</h4>
    <div>
        <strong>Mode de paiement :</strong>
        @if($order->payment_method == 'cash_on_delivery')
            Paiement à la livraison
        @elseif($order->payment_method == 'card')
            Carte bancaire
        @elseif($order->payment_method == 'bank_transfer')
            Virement bancaire
        @else
            {{ ucfirst($order->payment_method) }}
        @endif
    </div>

    <div style="margin-top: 10px;">
        <strong>Statut :</strong>
        @if($order->payment_status == 'paid')
            <span class="text-green font-bold">✓ Payé</span>
        @elseif($order->payment_status == 'pending')
            <span style="color: #ffc107;">⏳ En attente</span>
        @else
            <span style="color: #dc3545;">✗ Non payé</span>
        @endif
    </div>
</div>

@if($order->notes)
    <div style="margin: 20px 0; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
        <strong>Notes :</strong><br>
        {{ $order->notes }}
    </div>
@endif

<div class="footer">
    <div style="margin-bottom: 10px;">
        <strong>Merci pour votre commande !</strong>
    </div>
    <div>
        Pour toute question concernant cette facture, n'hésitez pas à nous contacter.
    </div>
    <div style="margin-top: 15px; font-size: 9px;">
        Facture générée automatiquement le {{ now()->format('d/m/Y à H:i') }}
    </div>
</div>
</body>
</html>
