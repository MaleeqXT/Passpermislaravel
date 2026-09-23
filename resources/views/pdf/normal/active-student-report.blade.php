@php
use Carbon\Carbon;
use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Étudiant - Auto-école PASSPERMISFACILE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
        <p class="header blue">Rapport Étudiant - Auto-école PASSPERMISFACILE</p>
        <p ><strong class="blue">RAPPORT N° :</strong> {{ $reference }}</p>
        <p><strong>Date :</strong> {{ Carbon::parse($created_at)->format('y-m-d') }}</p>
    </div>

    <div class="info-client">
        <p class="blue">Étudiant</p>
        <p><strong>Nom & Prénom :</strong> {{ $student['user']['name'] }}</p>
        <p><strong>Téléphone :</strong> {{ $student['user']['phone'] }}</p>
        <p><strong>E-mail :</strong> {{ $student['user']['email'] }}</p>
        <p><strong>Adresse :</strong> {{ $student['user']['adresse'] }} </p>
    </div>

    <div class="details">
      
        <br>
        <p class="blue">Offres Achetées :</p>
        <table>
            <thead>
                <tr>
                    <th>Nom de l'Offre</th>
                    <th>Prix HT (€)</th>
                    <th>Montant Total (€)</th>
                    <th>Balance (€)</th>
                    <th>Date d'Achat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($offers as $offer)
                <tr>
                    <td>{{ $offer['name'] }}</td>
                    <td>{{ number_format($offer['price_ht'], 2, ',', ' ') }}</td>
                    <td>{{ number_format($offer['total_payment'], 2, ',', ' ') }}</td>
                    <td>{{ $offer['balance'] ? number_format($offer['balance'], 2, ',', ' ') : '–' }}</td>
                    <td>{{ Carbon::parse($offer['created_at'])->format('d-m-Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Aucune offre</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <br>
        <p class="blue">Paiements :</p>
        <table>
            <thead>
                <tr>
                    <th>Sale ID</th>
                    <th>Montant (€)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $sale)
                <tr>
                    <td>{{ $sale['id'] }}</td>
                    <td>{{ number_format($sale['amount'], 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <br>
        <table style="width: 300px;margin-left:auto;" >
            <tr class="total">
                <td ><strong>Total payé</strong></td>
                <td>{{ number_format($total_paid, 2, ',', ' ') }} €</td>
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
