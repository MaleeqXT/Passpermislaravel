@php use Carbon\Carbon; @endphp
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
        <p ><strong class="blue">FACTURE N° :</strong> {{ $invoice['num_facture'] }}</p>
        <p><strong>Date de facturation :</strong> {{ $invoice['from'] }} à {{ $invoice['to'] }}</p>
    </div>

    <div class="info-client">
        <p class="blue">Client</p>
        <p><strong>Nom & Prénom :</strong> {{ $user['name'] }}</p>
        <p><strong>Téléphone :</strong> {{ $user['phone'] }}</p>
        <p><strong>E-mail :</strong> {{ $user['email'] }}</p>
        <p><strong>Adresse :</strong> {{$user['adresse']}} </p>
    </div>

    <div class="details">
        <p class="blue">Détail des prestations/Forfait :</p>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Prix unitaire</th>
                    <th>Quantity</th>
                    <th>Total TTC (€)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Prestation de service</td>
                    <td>{{ $invoice['details']['num_heures_f'] }}</td>
                    <td>{{ number_format( $invoice['details']['prix_heure'], 2, ',', ' ') }}</td>
                    <td>{{ number_format($invoice['details']['total'], 2, ',', ' ') }} €</td>
                </tr>
            </tbody>
     
        </table>

  <br>

        <table style="width: 300px;margin-left:auto;" >
            <tr>
                <td ><strong>TVA (%)</strong></td>
                <td width="150px">20%</td>
            </tr>
            <tr class="total">
                <td ><strong>Total</strong></td>
                <td>{{ number_format($invoice['montant'], 2, ',', ' ') }} €</td>
            </tr>
            {{-- <tr>
                <td><strong>Mode de paiement</strong></td>
                <td>{{ $payment_method }}</td>
            </tr> --}}
            <tr>
                <td><strong>Date du paiement</strong></td>
                <td>
                    {{ $invoice['date_paiement'] ? Carbon::parse($invoice['date_paiement'])->format('d-m-Y') : 'Non payé' }}
                </td>
            </tr>
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