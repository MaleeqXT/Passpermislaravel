@php
use Carbon\Carbon;
use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - Auto-école PASSPERMISFACILE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            /* line-height: 1.1; */
        }
        .header {
            margin-bottom: 0px;
        }
        .logo {
            position: absolute;
            right: 0px;
            top: 0px;
        }
        .info-company {
            margin-bottom: 20px;
            line-height: 0.8;

        }
        .info-client {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        .blue {
            color: #4fb3e5;
            font-weight: bold;
        }
        .total {
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        .footer {
            margin-top: 10px;
            font-size: 10px;
            line-height: 1.4;
        }
        .page-break {
            page-break-after: always;
        }
        .mb-20{
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="logo">
        <img src="{{ public_path('assets/logo-permis-facile.webp') }}" alt="logo permis facile" style="width: 250px; height: auto;">
    </div>

    <div class="info-company">
        <p class="header blue">Facture - Auto-école PASSPERMISFACILE</p>
        <p><strong>Nom de l'auto-école :</strong> PASSPERMISFACILE</p>
        <p><strong>Adresse :</strong> 139 boulevard Déddat de Séverac, 31300 TOULOUSE</p>
        <p><strong>Téléphone :</strong> 09 70 70 16 16</p>
        <p><strong>E-mail :</strong> contact@passpermisfacile.fr</p>
        <p><strong>Site web :</strong> www.passpermisfacile.fr</p>
        <p>SAS PASSPERMISFACILE</p>
        <p><strong>Capital social :</strong> 2 000 €</p>
        <p><strong>SIREN :</strong> 979 143 294</p>
        <p><strong>RCS :</strong> COMPIÈGNE B 979 143 294</p>
        <p><strong>Code APE :</strong> 85.532</p>
        <p><strong>TVA intra-communautaire :</strong> FR40979143294</p>
        <p ><strong class="blue">FACTURE N° :</strong> {{ $reference }}</p>
        <p><strong>Date de facturation :</strong> {{ Carbon::parse($created_at)->format('y-m-d') }}</p>
    </div>

    <div class="info-client">
        <p class="blue">Client</p>
        <p><strong>Nom & Prénom :</strong> {{ $student['user']['name'] }}</p>
        <p><strong>Téléphone :</strong> {{ $student['user']['phone'] }}</p>
        <p><strong>E-mail :</strong> {{ $student['user']['email'] }}</p>
        <p><strong>Adresse :</strong> {{ $student['user']['adresse'] }} </p>
    </div>

    <div class="details">
        <p class="blue">Détail des prestations/Forfait :</p>
        @php
            // Use related sales (all sales with same payment_id) instead of cart sales
            $salesToShow = $related_sales ?? [];
        @endphp
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>TVA (%)</th>
                    <th width="150px">Total TTC (€)</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($salesToShow))
                    {{-- Iterate through all related sales (same payment_id) --}}
                    @foreach($salesToShow as $index => $sale_item)
                    @php
                        // Get all cart_details from the first sale's cart (they all share the same cart)
                        $allCartDetails = $salesToShow[0]['cart']['cart_details'] ?? [];
                        // Match sale with cart_detail by index (sales created in same order as cart_details)
                        $cartDetail = $allCartDetails[$index] ?? null;
                        $offerName = $cartDetail['offer']['name'] ?? 'Service';
                    @endphp
                    <tr>
                        <td>{{ $offerName }}</td>
                        <td>1</td>
                        <td>20%</td>
                        {{-- Show amount from this sale record --}}
                        <td>{{ number_format($sale_item['amount'], 2, ',', ' ') }} €</td>
                    </tr>
                    @endforeach
                @else
                    {{-- Fallback to cart details if no related sales --}}
                    @foreach($cart['cart_details'] ?? [] as $item)
                    <tr>
                        <td>{{ $item['offer']['name'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>20%</td>
                        <td>{{ number_format($item['price'] ?? $item['offer']['final_price'], 2, ',', ' ') }} €</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>

        </table>
        <br>

        <table style="width: 300px;margin-left:auto;" >
            <tr>
                <td ><strong>TVA (%)</strong></td>
                <td width="150px">20%</td>
            </tr>
            <tr class="total">
                <td ><strong>Total Facturé</strong></td>
                {{-- Total from this sale --}}
                <td>{{ number_format($amount, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td><strong>Heures Crédit</strong></td>
                {{-- Balance from this sale --}}
                <td>{{ $balance ?? 0 }} h</td>
            </tr>
            <tr>
                <td><strong>Mode de paiement</strong></td>
                <td>
                    {{ SalePaymentMethodEnum::tryFrom($payment_method)?->label() ?? 'Méthode inconnue' }}
                </td>
            </tr>
            <tr>
                <td><strong>Date du paiement</strong></td>
                <td>{{ Carbon::parse($created_at)->format('d-m-Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p class="blue ">Médiation de la consommation</p>
        <p class="mb-20">Conformément à l'article L.616-1 du Code de la consommation, en cas de litige avec notre auto-école PASSPERMISFACILE, vous avez la possibilité de recourir gratuitement au médiateur de la consommation auquel nous avons adhéré : Médiateur désigné : ANM Corso. Adresse postale : 2, rue de Colmar 94300 VINCENNES. Email : contact@anm-corso.com. Téléphone : 01 58 64 00 05. Site web : www.anm-corso.com. Le client peut saisir le médiateur après une tentative préalable de résolution amiable directement auprès de l'auto-école.</p>

        <p class="blue">Mentions obligatoires supplémentaires</p>
        <p class="mb-20">Conditions générales de vente (CGV) : disponibles sur demande et sur notre site web. Délai de rétractation : Le consommateur dispose d'un droit de rétractation de 14 jours s'il a souscrit en ligne (sauf si la prestation a débuté avant la fin de ce délai avec son accord). Clause de réserve de propriété : L'auto-école conserve la propriété des services jusqu'au paiement intégral de la facture. Tribunal compétent : En cas de litige non résolu, le tribunal de TOULOUSE sera compétent.</p>

        <p>Auto-école <span class="blue">PASSPERMISFACILE</span> – Merci pour votre confiance !</p>
    </div>


</body>
</html>
