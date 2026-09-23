<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contrat d'Enseignement à la Conduite - {{ $student->user->name ?? '...' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
          font-family: Arial, Helvetica, sans-serif;
          font-size: 9pt;
          color: #000;
          background: #fff;
          padding: 30px 40px;
          max-width: 800px;
          margin: 0 auto;
          line-height: 1.5;
        }

        h1 {
          font-size: 18px;
          font-weight: bold;
          letter-spacing: 0.5px;
          line-height: 1.5;
          margin-bottom: 10px;
        }

        h2 {
          font-size: 15px;
          font-weight: bold;
          margin-bottom: 10px;
          margin-top: 28px;
        }

        h3 {
          font-size: 13px;
          font-weight: bold;
          margin-bottom: 8px;
          margin-top: 20px;
        }

        p {
          margin-bottom: 8px;
          text-align: justify;
        }

        strong {
          font-weight: bold;
        }

        .text-uppercase {
          text-transform: uppercase;
          margin-bottom: 6px;
          line-height: 1.5;
        }

        .text-highlight {
          color: #FF0000;
          font-weight: bold;
        }

        .editable-input {
          border: none;
          background: transparent;
          font-weight: bold;
          font-family: inherit;
          font-size: inherit;
          padding: 0;
          margin: 0;
          width: 100%;
          max-width: 100%;
        }

        .montant-input {
          text-align: right;
        }

        .editable-input:focus {
          outline: 1px dashed rgba(0, 0, 0, 0.6);
        }

        @media print {
          .editable-input {
            border: none;
            background: transparent;
          }
        }

        .header-section {
          border: 4px double #000;
          text-align: center;
          padding: 14px 20px;
          margin-bottom: 24px;
        }

        .content-section {
          margin-bottom: 22px;
        }

        .subsection {
          margin-bottom: 4px;
        }

        .preamble {
          font-size: 12px;
          margin-bottom: 16px;
        }

        .section-gap {
          margin-top: 30px;
        }

        .parties-table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 20px;
        }

        .parties-table td {
          border: 1px solid #000;
          padding: 5px 8px;
          vertical-align: top;
          font-size: 11px;
        }

        .parties-table .col-left {
          width: 58%;
        }

        .parties-table .col-right {
          width: 42%;
        }

        .parties-table .header-row td {
          font-weight: bold;
          font-size: 12px;
          padding: 5px 8px;
        }

        .parties-table .footer-row td {
          font-weight: bold;
          font-size: 11px;
          padding: 5px 8px;
        }

        .pricing-table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 32px;
        }

        .pricing-table td {
          border: 2px solid #bbb;
          padding: 5px 8px;
          vertical-align: middle;
          line-height: 1.5;
        }

        .pricing-table .header-row td {
          font-weight: bold;
          background: #fff;
          font-size: 12px;
          padding: 6px 8px;
        }

        .pricing-table .section-label td {
          font-weight: bold;
          background: #fff;
          padding: 5px 8px;
        }

        .pricing-table .empty-row td {
          height: 22px;
        }

        .pricing-table td:last-child {
          width: 100px;
          text-align: right;
        }

        .price {
          font-weight: bold;
        }

        .total-row td {
          background: #c1bfbf;
          border: 2px solid #bbb;
          padding: 8px 12px;
          font-weight: bold;
          font-size: 18px;
          color: rgb(255, 255, 255);
          text-align: center;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .total-row td:last-child {
          color: #c00;
          font-weight: bold;
          font-size: 13px;
          text-align: center;
        }

        .payment-table-wrapper {
          margin: 14px 0 18px 0;
        }

        .payment-table {
          width: 100%;
          border-collapse: collapse;
          border: 1.5px solid #000;
        }

        .payment-table td {
          border: 3px solid #000;
          padding: 10px 20px;
          text-align: center;
          font-size: 11pt;
          font-family: 'Times New Roman', Times, serif;
          width: 33.33%;
        }

        .sig-table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 4px;
        }

        .sig-table th {
          border: 2px solid #000;
          font-size: 8pt;
          font-weight: normal;
          text-align: left;
          vertical-align: top;
        }

        .sig-table td {
          border: 2px solid #000;
          padding: 0;
          height: 90px;
          vertical-align: bottom;
          text-align: center;
        }

        .checkbox-label {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
        }

        .checkbox-box {
          display: inline-block;
          width: 13px;
          height: 13px;
          border: 3px solid #000;
          background: #fff;
          flex-shrink: 0;
          position: relative;
          vertical-align: middle;
        }

        .checkbox-box.checked::after {
          content: '✕';
          position: absolute;
          top: -2px;
          left: -1px;
          font-size: 13px;
          font-weight: bold;
          line-height: 1;
        }

        .signature-section {
          margin: 28px 0 16px 0;
          font-size: 11pt;
          font-weight: bold;
          display: flex;
          align-items: center;
          gap: 6px;
          flex-wrap: wrap;
        }

        .date-placeholder {
          color: #ea0b0b;
          font-weight: bold;
        }

        .stamp-cell {
          position: relative;
          display: flex;
          align-items: flex-end;
          justify-content: center;
          height: 90px;
          padding-bottom: 6px;
        }

        .stamp {
          display: inline-block;
          text-align: center;
          font-size: 8.5pt;
          line-height: 1.4;
          padding: 6px 10px;
          position: relative;
        }

        .stamp-text {
          font-weight: bold;
          letter-spacing: 0.3px;
        }
        .no-print {
          display: inline-block;
        }
        @media print {
          .no-print {
            display: none !important;
          }
        }
    </style>
</head>
<body>
    <div class="no-print" style="position:fixed; top:12px; right:12px; z-index:9999;">
        <button type="button" onclick="window.print()" style="padding:8px 12px; font-size:12px; cursor:pointer;border-radius:10px;">Imprimer</button>
    </div>
    <div class="header-section">
        <h1>CONTRAT D'ENSEIGNEMENT À LA CONDUITE<br>CATÉGORIE B DU PERMIS DE CONDUIRE</h1>
    </div>

    <p class="preamble">En application des dispositions de l'article R.246.2 du Code de la route :</p>

    <div class="content-section">
        <h2>1. LES PARTIES CONTRACTANTES :</h2>
        <table class="parties-table">
            <tr class="header-row">
                <td class="col-left">Entre :</td>
                <td class="col-right">Et :</td>
            </tr>
            <tr>
                <td>Raison ou dénomination sociale de l'établissement : SAS &nbsp;PASSPERMISFACILE</td>
                <td>Mme : <input id="field-name" class="editable-input" value="{{ $student->user->name ?? 'NOM PRÉNOM' }}" @if(! $isAdmin) readonly @endif /></td>
            </tr>
            <tr>
                <td>Forme juridique et montant du capital social (pour les sociétés commerciales) :<br>SA au capital de 2000€</td>
                <td>Né(e) le : <input id="field-dob" class="editable-input" value="{{ optional($student->user)->date_naissance ? \Carbon\Carbon::parse($student->user->date_naissance)->format('d/m/Y') : 'XXXX' }}" @if(! $isAdmin) readonly @endif /></td>
            </tr>
            <tr>
                <td>Siret Toulouse 979 143 29400036 / Creil 979 143 29400044</td>
                <td>Adresse : <input id="field-address" class="editable-input" value="{{ $student->user->adresse ?? 'XXXX' }}" @if(! $isAdmin) readonly @endif /></td>
            </tr>
            <tr>
                <td>N° tél : 0970701616</td>
                <td></td>
            </tr>
            <tr>
                <td>Courriel : PASSPERMISFACILE@GMAIL.COM</td>
                <td>Mail : <input id="field-email" class="editable-input" value="{{ $student->user->email ?? 'XXXX@XXXXX.com' }}" @if(! $isAdmin) readonly @endif /></td>
            </tr>
            <tr>
                <td>N° d'identification à la TVA : FR40979143294</td>
                <td>N° tél : <input id="field-phone" class="editable-input" value="{{ $student->user->phone ?? 'XXXXXXXXXX' }}" @if(! $isAdmin) readonly @endif /></td>
            </tr>
            <tr>
                <td>Numéro de déclaration d'activité : 76311313631</td>
                <td>Nom du représentant légal (pour les mineurs) :</td>
            </tr>
            <tr>
                <td>Exploité par Monsieur ETIENNE FLORIAN</td>
                <td></td>
            </tr>
            <tr>
                <td>Agréé(e) sous le numéro E24 06000100 (Creil) / E23 03100100 (Toulouse)</td>
                <td></td>
            </tr>
            <tr>
                <td>délivré par la préfecture de l'Oise &nbsp;le 08/10/2024</td>
                <td>Adresse du représentant légal (si différent):</td>
            </tr>
            <tr>
                <td>délivré par la préfecture de la Haute Garonne le 19/12/2023</td>
                <td></td>
            </tr>
            <tr class="footer-row">
                <td>Ci-après désigné(e) l'école de conduite : PASSPERMISFACILE</td>
                <td>Ci-après désigné l'élève</td>
            </tr>
        </table>
    </div>

    <div class="content-section">
        <h2>2. ÉVALUATION PRÉALABLE DE L'ÉLÈVE</h2>
        <p>L'évaluation du niveau du candidat avant l'entrée en formation est obligatoire. En application de l'article L. 213-2 du code de la route, le présent contrat</p>
        <p>est conclu après une évaluation préalable du candidat, afin de déterminer le nombre prévisionnel d'heures de formation pratique à la conduite nécessaires</p>
        @php
            $firstTraining = $student->trainings()
                ->whereNotNull('reservation_id')
                ->with('reservation')
                ->orderBy('created_at')
                ->first();

            $evaluationDate = optional(optional($firstTraining)->reservation)->date;
            $contractEndDate = $evaluationDate ? \Carbon\Carbon::parse($evaluationDate)->addYear()->format('d/m/Y') : null;
        @endphp
        <p><strong>L'évaluation de l'élève a été réalisée le: <input id="field-evaluationDate" class="editable-input text-highlight" style="display:inline-block; width:auto; min-width:90px;" value="{{ $evaluationDate ? \Carbon\Carbon::parse($evaluationDate)->format('d/m/Y') : 'XX/XX/XXXX' }}" @if(! $isAdmin) readonly @endif /></strong></p>
        @php
            $estimatedTrainingHours = optional($student->reviewMonitor)->estimation;
            $reservedTrainingHours = (int) $student->trainings()
                ->join('reservations', 'trainings.reservation_id', '=', 'reservations.id')
                ->sum('reservations.hour');
            $currentStudentBalance = (int) ($student->balance ?? 0);
            $totalPurchasedTrainingHours = $currentStudentBalance + $reservedTrainingHours;
            $totalTrainingHours = $totalPurchasedTrainingHours ?: ($estimatedTrainingHours ?? $reservedTrainingHours);
        @endphp
        <p>À l'issue de cette évaluation, le nombre d'heures prévisionnel de formation pratique est de : <input id="field-totalTrainingHours" class="editable-input text-highlight" style="display:inline; width:auto; min-width:0; max-width:40px; margin:0; padding:0; border:none;" value="{{ $totalTrainingHours }}" @if(! $isAdmin) readonly @endif /><strong style="margin:0;">heures.</strong></p>
    </div>

    <div class="content-section">
        <h2>3. L'OBJET DU CONTRAT</h2>
        <p>Conformément aux articles L. 213-2 et R. 213-3 du code de la route et à l'arrêté du 22 décembre 2009 relatif à l'apprentissage de la conduite des véhicules à moteur de la catégorie B, le présent contrat a pour objet d'établir les conditions et les modalités de l'enseignement, théorique et ou pratique, de la conduite des véhicules à moteur de la catégorie B et de la sécurité routière.</p>
    </div>

    <div class="content-section">
        <h2>4. DATE DE PRISE D'EFFET ET DURÉE DU CONTRAT</h2>
        <p>Le présent contrat entre en vigueur entre les parties au jour de sa signature pour une durée de <strong>12 mois</strong>,</p>
        <p><strong>soit jusqu'au : <input id="field-contractEndDate" class="editable-input text-highlight" style="display:inline-block; width:auto; min-width:90px;" value="{{ $contractEndDate ?? 'XX/XX/XXXX' }}" @if(! $isAdmin) readonly @endif /></strong></p>
        <p>Les tarifs, les prix détaillés et les termes du contrat ne sont pas révisables pendant toute la durée du contrat sauf modification législative ou réglementaire.</p>
        <p>Le contrat peut faire l'objet d'une prolongation par voie d'avenant à l'initiative de l'une ou l'autre des parties.</p>
        <p>Le présent contrat porte sur une durée de un an</p>
    </div>

    <h2>5. TARIFS DES PRESTATIONS ET PRIX DE LA FORMATION (EN TTC)</h2>

    @php
        $formatPrice = fn($price) => number_format((float) $price, 2, ',', ' ') . ' €';

        $getOfferPrice = function ($offer, $selectedPriceType = null, $customPrice = null) {
            if ($customPrice !== null) {
                return $customPrice;
            }

            $selected = $selectedPriceType;

            return match ($selected) {
                'original' => $offer->original_price,
                'second' => $offer->second_price,
                'installment' => $offer->final_price ?? $offer->price_ht,
                'final' => $offer->final_price,
                default => $offer->final_price ?? $offer->discounted_price ?? $offer->price_ht ?? 0,
            };
        };

        $cartDetails = $student->sales()
            ->with('cart.cartDetails.offer')
            ->get()
            ->flatMap(fn($sale) => $sale->cart?->cartDetails ?? collect());

        $purchases = [];
        foreach ($cartDetails as $detail) {
            $offer = $detail->offer;
            if (! $offer) {
                continue;
            }

            $unitPrice = $getOfferPrice($offer, $detail->selected_price_type ?? null, $detail->price ?? null);
            $quantity = $detail->quantity ?? 1;

            if (! isset($purchases[$offer->id])) {
                $purchases[$offer->id] = [
                    'label' => $offer->name,
                    'qty' => 0,
                    'unit' => $unitPrice,
                    'total' => 0,
                ];
            }

            $purchases[$offer->id]['qty'] += $quantity;
            $purchases[$offer->id]['total'] += $unitPrice * $quantity;
        }

        $grandTotal = array_sum(array_column($purchases, 'total'));

        $offers = \App\Models\Roles\Admin\Offer\Offer::orderBy('name')->get();
    @endphp

   <table class="pricing-table">
    <colgroup>
        <col style="width: auto;">
        <col style="width: 110px;">
    </colgroup>

    <tr class="header-row">
        <td>Forfaits</td>
        <td>Montant</td>
    </tr>

@php
    $studentCityRaw = $student->user->ville ?? '';
    $studentCity = strtolower(trim($studentCityRaw));

    // Normalize (é → e)
    $studentCityAscii = @iconv('UTF-8', 'ASCII//TRANSLIT', $studentCity) ?: $studentCity;
    $studentCityNormalized = preg_replace('/[^a-z0-9]/', '', $studentCityAscii);

    // Transmission
    $studentBoiteType = $student->boite_type ?? null;

    if (!is_null($studentBoiteType)) {
        $studentTransmission = $studentBoiteType == 1 ? 'auto' : 'manual';
    } else {
        $studentTransmission = strtolower($student->transmission ?? '');
    }

    $studentTransmission = in_array($studentTransmission, ['auto', 'manual'], true)
        ? $studentTransmission
        : 'manual';

    // Detect city
    $cityType = null;

    if (str_contains($studentCityNormalized, 'creil') || str_contains($studentCityNormalized, 'criel')) {
        $cityType = 'creil';
    } elseif (str_contains($studentCityNormalized, 'toulouse')) {
        $cityType = 'toulouse';
    }

    // Determine the offer price for the student's city if agency_pricing is available
    $getAgencyPrice = function ($offer) use ($cityType) {
        if (! $cityType || empty($offer->agency_pricing)) {
            return null;
        }

        $pricingList = is_string($offer->agency_pricing)
            ? json_decode($offer->agency_pricing, true)
            : $offer->agency_pricing;

        if (! is_array($pricingList)) {
            return null;
        }

        foreach ($pricingList as $entry) {
            $entryAgency = strtolower(trim($entry['agency'] ?? ''));
            $entryAgencyNormalized = preg_replace('/[^a-z0-9]/', '', $entryAgency);

            if (
                $entryAgencyNormalized === $cityType ||
                ($cityType === 'creil' && $entryAgencyNormalized === 'criel')
            ) {
                if (isset($entry['price_ht'])) {
                    return (float) $entry['price_ht'];
                }
                if (isset($entry['total_payment'])) {
                    return (float) $entry['total_payment'];
                }
                if (isset($entry['original_price'])) {
                    return (float) $entry['original_price'];
                }
                if (isset($entry['amount'])) {
                    return (float) $entry['amount'];
                }
            }
        }

        return null;
    };

    // FILTER (STRICT)
    $filteredOffers = $offers->filter(function ($offer) use ($cityType, $studentTransmission) {

        // 1. Transmission MUST match
        if ($studentTransmission === 'auto' && !$offer->is_auto) {
            return false;
        }

        if ($studentTransmission === 'manual' && $offer->is_auto) {
            return false;
        }

        // 2. City MUST match (Creil / Toulouse only)
        if (! $cityType) {
            return false;
        }

        $agencyRaw = $offer->agency_name ?? '';
        $agencyNormalized = preg_replace('/[^a-z0-9]/', '', strtolower(trim($agencyRaw)));

        // --- Match if offer is explicitly tagged for this city ---
        if (
            $agencyNormalized && (
                str_contains($agencyNormalized, $cityType) ||
                str_contains($cityType, $agencyNormalized) ||
                ($cityType === 'creil' && $agencyNormalized === 'criel')
            )
        ) {
            return true;
        }

        // --- If the offer has no city restriction, include it for all cities ---
        if (empty($agencyNormalized) && empty($offer->agency_pricing)) {
            return true;
        }

        // Check JSON agency_pricing
        $pricing = $offer->agency_pricing;

        if (is_string($pricing)) {
            $pricing = json_decode($pricing, true);
        }

        if (is_array($pricing)) {
            foreach ($pricing as $entry) {
                $entryAgency = strtolower(trim($entry['agency'] ?? ''));
                $entryAgencyNormalized = preg_replace('/[^a-z0-9]/', '', $entryAgency);

                if (
                    $entryAgencyNormalized === $cityType ||
                    ($cityType === 'creil' && $entryAgencyNormalized === 'criel')
                ) {
                    return true;
                }
            }
        }

        return false;
    });

    $offersManual = $filteredOffers->where('is_auto', false);
    $offersAuto = $filteredOffers->where('is_auto', true);
@endphp

{{-- MANUAL --}}
@if($studentTransmission === 'manual' && $offersManual->isNotEmpty())
    <tr class="section-label">
        <td>boite manuelle</td>
        <td></td>
    </tr>

    @foreach ($offersManual as $offer)
        @php
            $purchase = $purchases[$offer->id] ?? null;
            $agencyPrice = $getAgencyPrice($offer);
            $basePrice = $purchase['unit'] ?? $agencyPrice ?? $getOfferPrice($offer);
        @endphp

        <tr>
            <td>
                {{ $offer->name }} - {{ $formatPrice($basePrice) }}
                @if ($purchase && $purchase['qty'] > 1)
                    (x{{ $purchase['qty'] }})
                @endif
            </td>
            <td class="price">
                <input
                    type="text"
                    class="editable-input montant-input"
                    data-offer-id="{{ $offer->id }}"
                    value="{{ $purchase ? $formatPrice($purchase['total']) : '-' }}"
                    @if(! $isAdmin) readonly @endif
                />
            </td>
        </tr>
    @endforeach
@endif

{{-- AUTO --}}
@if($studentTransmission === 'auto' && $offersAuto->isNotEmpty())
    <tr class="section-label">
        <td>boite automatique</td>
        <td></td>
    </tr>

    @foreach ($offersAuto as $offer)
        @php
            $purchase = $purchases[$offer->id] ?? null;
            $agencyPrice = $getAgencyPrice($offer);
            $basePrice = $purchase['unit'] ?? $agencyPrice ?? $getOfferPrice($offer);
        @endphp

        <tr>
            <td>
                {{ $offer->name }} - {{ $formatPrice($basePrice) }}
                @if ($purchase && $purchase['qty'] > 1)
                    (x{{ $purchase['qty'] }})
                @endif
            </td>
            <td class="price">
                <input
                    type="text"
                    class="editable-input montant-input"
                    data-offer-id="{{ $offer->id }}"
                    value="{{ $purchase ? $formatPrice($purchase['total']) : '-' }}"
                    @if(! $isAdmin) readonly @endif
                />
            </td>
        </tr>
    @endforeach
@endif

{{-- EMPTY --}}
@if($filteredOffers->isEmpty())
    <tr>
        <td colspan="2" style="text-align:center;font-style:italic;">
            Aucune offre disponible pour cette ville / transmission.
        </td>
    </tr>
@endif

<tr class="total-row">
    <td>TOTAL TTC EN EUROS</td>
    <td>
        <input
            id="field-total"
            type="text"
            class="editable-input montant-input"
            value="{{ $formatPrice($grandTotal) }}"
            @if(! $isAdmin) readonly @endif
        />
    </td>
</tr>
</table>

    <h2>6. PROGRAMME ET DÉROULEMENT DE LA FORMATION</h2>

    <p>L'école de conduite s'engage à délivrer à l'élève une formation théorique et pratique conforme aux dispositions législatives et réglementaires en vigueur.</p>
    <p>Les objectifs de la formation sont précisés dans les quatre compétences de formation du livret d'apprentissage remis à l'élève le jour de la signature du contrat. Ces compétences sont les suivantes : <strong>MAÎTRISER LE MANIEMENT</strong> du véhicule dans un trafic faible ou nul ; <strong>APPRÉHENDER</strong> la route et circuler dans des conditions normales ; <strong>CIRCULER</strong> dans des conditions difficiles et partager la route avec les autres usagers ; <strong>PRATIQUER</strong> une conduite autonome, sûre et économique.</p>

    <h2>7. FORMATION THÉORIQUE GÉNÉRALE (CODE DE LA ROUTE)</h2>

    <h3>A. Formation théorique générale (code de la route)</h3>

    <div class="content-section">
      <h3>A.1. Programme de formation en vigueur</h3>
      <p>La formation théorique générale dispensée par l'école de conduite correspond au programme de l'épreuve théorique générale (ETG).</p>
      <p>Elle porte notamment sur la connaissance des règlements relatifs à la circulation et la conduite d'un véhicule, ainsi que sur celle des bons comportements du conducteur. Seront également dispensés les règles de sécurité routière à appliquer dans les tunnels, les précautions à prendre en quittant le véhicule, les facteurs de sécurité concernant le chargement du véhicule et les personnes transportées, les règles de conduite respectueuses de l'environnement, ainsi que la réglementation relative à l'obligation d'assurance et aux documents administratifs liés à l'utilisation du véhicule.</p>
    </div>

    <div class="content-section">
      <h3>A.2. Déroulement de la formation</h3>
      <p>L'enseignement théorique se déroule soit sur place, soit à distance, soit les deux, soit en cours individuel ou en cours collectif (si code intensif)</p>
    </div>

    <div class="content-section">
      <h3>A.3. Moyens pédagogiques et techniques</h3>
      <p>Si enseignement à distance : depuis smartphone, ordinateur ou tablette. Les offres sont valables 12 mois. Pour plus d'informations relatives aux outils informatiques voir les CGU sur le site internet : www.passpermisfacile.fr</p>
      <p>Si enseignement en salle de code : depuis une des agences agréées préfectoralement par PASSPERMISFACILE.</p>
    </div>

    <div class="content-section">
      <h3>A.4. Accompagnement à l'épreuve théorique générale (ETG)</h3>
      <p>Lorsque l'élève est convoqué à l'épreuve théorique générale de l'examen du permis de conduire, Il s'y rend par ses propres moyens. L'élève devra se munir d'une <strong>pièce d'identité valide</strong>, à défaut il ne pourra être admis à l'examen.</p>
    </div>

    <div class="content-section">
      <h3>A.5. Epreuve théorique générale</h3>
      <p>L'épreuve théorique générale est réglementée par l'Etat. L'organisation de cette épreuve est notamment assurée par des opérateurs privés agréés par l'Etat. Le paiement des frais s'effectue directement par l'élève auprès de l'opérateur via la plateforme Lepermislibre ou par ses propres moyens sur le site de l'opérateur.</p>
    </div>

    <h3>B. Formation pratique (conduite)</h3>

    <div class="content-section">
      <h3>B.1. Programme de formation en vigueur</h3>
      <p>Le programme de formation respecte le référentiel Référentiel pour l'Éducation à une Mobilité Citoyenne</p>
    </div>

    <div class="content-section">
      <h3>B.2. Calendrier</h3>
      <p>Le calendrier de formation pratique est établi par l'école de conduite en concertation avec l'élève, en fonction de leurs disponibilités respectives.</p>
    </div>

    <div class="content-section">
      <h3>B.3. Déroulement de la formation</h3>
      <p>L'enseignement pratique se déroule en cours individuel sur voie ouverte à la circulation, soit en boite manuelle ou soit en boite automatique selon le forfait choisi par l'élève. La durée de chaque leçon en formation pratique comprend le temps nécessaire notamment à l'accueil, la détermination de l'objectif, la leçon, l'évaluation et le bilan de la leçon.</p>
    </div>

    <div class="content-section">
      <h3>B.4. Évaluation des compétences en fin de formation initiale</h3>
      <p>Pendant la formation pratique définie lors de l'évaluation préalable, ou à tout moment à la demande de l'élève, l'enseignant effectue un bilan des compétences acquises par l'élève.</p>
      <p>si l'élève satisfait à ce bilan, l'école de conduite lui délivre une attestation de fin de formation initiale dans le cadre de la conduite accompagnée.</p>
      <p>Dans le cas contraire, en fonction du résultat obtenu par l'élève et de son niveau, l'école de conduite précise les points à approfondir. La poursuite de la formation dans le cadre de la conduite supervisée pourra être envisagée.</p>
      <p>Lorsque le nombre d'heures prévues initialement au contrat, n'a pas suffi à l'élève pour atteindre le niveau lui permettant de se présenter à l'épreuve pratique ou en cas d'échec à cette épreuve, un complément d'heures de formation pourra être proposé par l'école de conduite. L'élève a la possibilité d'accepter ou de refuser. En cas d'accord, un avenant au présent contrat sera signé entre les 2 parties.</p>
    </div>

    <div class="content-section">
      <h3>B.5. Présentation à l'épreuve pratique du permis de conduite</h3>
      <p>L'élève sera présenté à l'épreuve pratique par l'école de conduite, suivant les dates arrêtées et communiquées par l'autorité administrative.</p>
      <p>En cas d'échec, et après accord entre les parties sur les besoins de l'élève, l'école de conduite présentera ce dernier à une nouvelle épreuve pratique, en fonction du calendrier qui lui est communiqué par l'autorité administrative.</p>
    </div>

    <div class="content-section">
      <h3>B.6. Accompagnement à l'épreuve pratique</h3>
      <p>Le jour de l'épreuve pratique, l'école de conduite assure l'accompagnement de l'élève sur le centre de l'examen et met à sa disposition le véhicule de l'école de conduite pendant toute la durée de l'épreuve.</p>
      <p>Les frais d'accompagnement facturés à ce titre par l'école de conduite à l'élève correspondent à une heure de conduite, conformément aux dispositions de l'article R. 213-3-3 du code de la route.</p>
    </div>

    <h2>8. OBLIGATIONS DES PARTIES</h2>

    <p>En cas d'annulation des leçons en formation pratique : Sauf cas de force majeure ou motif légitime dûment justifié à l'école de conduite, toute leçon non décommandée par l'élève au moins <strong>48 heures à l'avance n'est pas remboursée</strong>. Si elle n'a pas été payée à l'avance, elle est considérée comme due. Sauf cas de force majeure ou motif légitime dûment justifié à l'élève, l'école de conduite s'engage à n'annuler aucune leçon moins de 48 heures à l'avance. À défaut la leçon doit être reportée et remboursée.</p>

    <h3>A. Démarches administratives</h3>
    <p>En vertu du présent contrat, l'élève peut choisir de mandater l'école de conduite pour accomplir en son nom et place toutes les démarches et formalités nécessaires auprès de l'administration, en vue de l'enregistrement de son livret et de son dossier d'examen. L'élève est avisé par l'école de conduite de la liste des documents à fournir pour constituer son dossier d'examen. L'élève garde la possibilité de mettre fin au mandat à tout moment conformément à la loi,</p>
    <p>moyennant, le cas échéant, le paiement d'une somme compensant strictement les moyens engagés par l'école de conduite jusqu'à la résiliation.</p>
    <p>L'école s'engage à déposer le dossier, dès lors qu'il est complet et à fournir à l'élève son numéro d'enregistrement préfectoral harmonisé (NEPH). Le mandataire ne saurait être tenu responsable du retard pris par le mandant pour fournir les pièces justificatives ou de celui imputable à l'autorité compétente pour enregistrer ou valider la demande.</p>

    <h3>B. Inscription aux épreuves théorique et pratique du permis de conduire</h3>
    <p>L'inscription à l'épreuve théorique générale du code de la route ou à l'épreuve pratique du permis de conduire peut être réalisée par l'élève ou par l'école de conduite. Dans ce cas, en vertu du présent contrat, l'élève peut choisir de mandater l'école de conduite pour accomplir en son nom et place toutes les démarches et formalités nécessaires auprès des organismes agréés pour l'épreuve théorique générale, et de l'administration, en vue de la réservation des places d'examen. L'élève garde la possibilité de mettre fin au mandat à tout moment conformément à la loi, moyennant, le cas échéant, le paiement d'une somme compensant strictement les moyens engagés par l'école de conduite jusqu'à la résiliation.</p>
    <p>L'école de conduite s'engage à inscrire l'élève aux épreuves théoriques ou pratiques du permis de conduire à une date en accord avec ce dernier si li souscrit au forfait PASSPERMIS DÉCOUVERTE.</p>
    <p>L'inscription à l'épreuve pratique est réalisée par PASSPERMISFACILE.</p>

    <h2>9. OBLIGATIONS DE L'ÉLÈVE</h2>

    <p class="text-uppercase">ÊTRE ÂGÉ DE 16 ANS MINIMUM OU 15 ANS MINIMUM EN CAS D'APPRENTISSAGE ANTICIPÉ DE LA CONDUITE</p>
    <p class="text-uppercase">ÊTRE DÉTENTEUR, NOTAMMENT LORS DES LEÇONS PRATIQUES, DES DOCUMENTS SUIVANTS : LIVRET D'APPRENTISSAGE CONFORME À LA RÉGLEMENTATION ; FORMULAIRE DE LA DEMANDE DE PERMIS DE CONDUIRE VALIDÉE PAR LE PRÉFET DU LIEU DE DÉPARTEMENT DE SON DÉPÔT.</p>

    <h2>10. OBLIGATIONS DE L'ÉCOLE DE CONDUITE</h2>

    <p class="text-uppercase">DÉLIVRER À L'ÉLÈVE UNE FORMATION THÉORIQUE ET PRATIQUE CONFORME AUX PROGRAMMES EN VIGUEUR.</p>
    <p class="text-uppercase">PRÉSENTER LE CANDIDAT À L'ÉPREUVE OU AUX ÉPREUVES EN FOURNISSANT LES MOYENS NÉCESSAIRES SAUF SI LE CANDIDAT SOUHAITE SE PRÉSENTER DIRECTEMENT.</p>

    <h2>11. Modalités de paiement</h2>

    <p>Le paiement des prestations s'effectue par :</p>

    <div class="payment-table-wrapper">
      <table class="payment-table">
        <tr>
          <td>
            <span class="checkbox-label">
              <span class="checkbox-box checked"></span>
              carte bancaire
            </span>
          </td>
          <td>
            <span class="checkbox-label">
              <span class="checkbox-box"></span>
              espèce
            </span>
          </td>
          <td>
            <span class="checkbox-label">
              <span class="checkbox-box"></span>
              virement
            </span>
          </td>
        </tr>
      </table>
    </div>

    <p>Le paiement pourra s'effectuer selon l'une des modalités suivantes : paiement comptant en un seul versement, échelonné en deux ou trois versements sans frais (paiement en ligne en 2 ou 3x sans frais)</p>
    <p>Si la dernière option est retenue, les versements s'effectueront comme tel: premier versement le jour J et à J+30 pour le deuxième versement et à J+60 pour le troisième versement.</p>

    <p style="margin-top:10px;">l'école de conduite délivre une note à l'élève avant le paiement de la prestation. Pour les prestations forfaitaires, la note indique la liste détaillée des prestations comprises dans le forfait. Conformément à l'article 1 de l'arrêté du 3 octobre 1983, toute prestation dont le prix est égal ou supérieur à 25 € TTC fera l'objet de la délivrance d'une note. Elle peut être remise sur simple demande de l'élève pour des prestations dont le prix est inférieur à 25 €.</p>
    <p>En cas de défaillance de l'école de conduite, celle-ci n'a pas souscrit à un dispositif de garantie financière.</p>

    <h2 class="section-gap">12. Conditions de rétractation ou de résiliation</h2>

    <h3>A. Rétractation</h3>

    <p>Dans le cadre d'un contrat conclu à distance tel que défini à l'article L. 221-1 du code de la consommation, l'élève bénéficie, à compter de la date de la signature du présent contrat, d'un droit de rétractation de 14 jours conformément à l'article L. 221-18 du même code. Dans l'hypothèse où l'élève souhaite exercer ce droit, il adresse sa décision de se rétracter à l'école de conduite soit par lettre recommandée ou envoi recommandé électronique avec avis de réception à l'adresse postale de l'école de conduite ou par courriel à l'adresse électronique de l'école de conduite. Le formulaire de rétractation figurant en annexe peut être utilisé par l'élève. Si l'élève a expressément demandé à débuter sa formation avant l'expiration du délai de rétractation, l'école de conduite lui facturera le montant des prestations réalisées jusqu'à la notification par l'élève de sa décision de se rétracter. En cas de prestations déjà réglées par l'élève dans le cadre d'un forfait, le remboursement s'effectue au prorata des prestations déjà réalisées. En cas de prestations non encore facturées à l'élève dans le cadre d'un forfait, la facturation s'effectue au prorata des prestations déjà réalisées.</p>

    <h3>B. Résiliation</h3>

    <p>L'élève peut résilier le présent contrat à tout moment par lettre recommandée ou envoi recommandé électronique avec avis de réception à l'adresse postale de l'école de conduite ou par courriel à l'adresse électronique de l'école de conduite, moyennant paiement des prestations déjà réalisées. La résiliation prend effet 15 jours à compter de la date de première présentation de la lettre recommandée ou de l'envoi recommandé électronique. Ce délai de préavis ne s'applique pas en cas de motif légitime. L'école de conduite peut résilier le présent contrat en cas de violence avérée, de mise en danger d'autrui, d'incivilités ou de manquements répétés à l'une de ses obligations issues du présent contrat (hypothèse : retards de paiement non régularisés), après mise en demeure spécifiant le motif de la résiliation notifiée par lettre recommandée ou de l'envoi recommandé électronique avec avis de réception. La résiliation prend effet 15 jours à compter de la date de première présentation de la lettre recommandée ou de l'envoi recommandé électronique. L'élève peut contester la décision de l'école de conduite. À défaut de solution, il peut recourir à une procédure de médiation. La résiliation du présent contrat avant son terme entraîne l'apurement définitif des comptes. L'école de conduite facturera le montant des prestations réalisées jusqu'à la date de la prise d'effet de la résiliation. En cas de prestations déjà réglées par l'élève dans le cadre d'un forfait, le remboursement s'effectue au prorata des prestations déjà réalisées. En cas de prestations non encore facturées à l'élève dans le cadre d'un forfait, la facturation s'effectue au prorata des prestations déjà réalisées. Le dossier de l'élève lui sera restitué gratuitement à tout moment à sa demande ou à celle d'un tiers dûment mandaté par lui. En cas de résiliation sans motif légitime, en dehors de paiement par arrhes, avant tout commencement de la formation pratique, l'école de conduite pourra retenir une somme correspondant au montant des frais liés exclusivement à la résiliation, dûment prévue au présent contrat (article III) et dûment justifiée. Le présent contrat est résilié de plein droit en cas de retrait de l'agrément de l'école de conduite. L'école de conduite rembourse sans délai l'élève de toutes sommes payées par lui n'ayant donné lieu à prestation.</p>

    <h2 class="section-gap">13. Souscription par l'établissement</h2>

    <p>L'école de conduite est titulaire d'un contrat d'assurance de responsabilité civile garantissant ses véhicules et couvrant les dommages pouvant être causés aux tiers ainsi qu'aux personnes se trouvant à l'intérieur des véhicules pendant la formation ou lors des examens pratiques dans les conditions prévues à l'article L.211-1 du code des assurances, souscrit auprès de AXA ASSURANCE (14 Av. du Général de Gaulle, 78490 Montfort-l'Amaury)</p>

    <h2 class="section-gap">14. Règlement des litiges</h2>

    <p>En cas de désaccord ou litige entre les parties, le présent contrat est soumis au droit français.</p>
    <p>Tout litige découlant de la validité, exécution, résiliation du présent contrat est soumis aux tribunaux compétents dans les conditions de droit commun.</p>
    <p>A défaut de solution amiable, l'élève peut recourir gratuitement, dans les conditions prévues aux articles L. 612-1 et suivants et R. 612-1 et suivants du code de la consommation à un médiateur de la consommation en vue de la résolution amiable de tout litige l'opposant à l'école de conduite, relatif au présent contrat :</p>
    <p>ANM Conso – 2, rue de Colmar 94 300 VINCENNES - contact@anm-conso.com - 01 58 64 00 05 - www.anm-conso.com. Avant de saisir le médiateur, l'élève doit avoir adressé une réclamation écrite à l'école de conduite. Il doit saisir le médiateur dans le délai d'un an maximum à compter de sa réclamation écrite.</p>

    <h2 class="section-gap">15. Protection des données personnelles</h2>

    <p>L'élève est informé que les données personnelles recueillies sur ce contrat font l'objet de traitements automatisés nécessaires à l'exécution de ce dernier. L'école de conduite est responsable du traitement de ces données personnelles qu'elle collecte et traite pour établir le contrat et fournir les services d'enseignement à la conduite qui y sont mentionnés. Seules les données personnelles strictement nécessaires à l'exécution du présent contrat sont traitées par l'école de conduite. Elles sont obligatoires : à défaut la fourniture des services d'apprentissage à la conduite pourrait être suspendue. Elles ne font l'objet d'aucun transfert ni communication à des tiers sauf obligations législatives ou réglementaires. Dans le cas où l'élève a mandaté l'école de conduite pour effectuer les formalités nécessaires à l'inscription à l'épreuve théorique générale (code) ou à l'examen de la conduite, ainsi qu'à l'établissement de son permis de conduire, l'école de conduite transmettra aux opérateurs responsables les données personnelles strictement nécessaires à l'exécution de ces formalités. L'école de conduite s'engage à conclure avec ses sous-traitants un contrat de traitement de données personnelles conforme à l'article 28 du règlement n° 2016/679, dit règlement général sur la protection des données (RGPD). L'école de conduite s'engage à mettre en œuvre les mesures techniques et organisationnelles appropriées afin de garantir un niveau de sécurité optimal des données personnelles qu'il traite. Les données recueillies seront conservées pendant toute la durée du contrat et seront supprimées au bout de 5 ans à compter de son terme. Si l'élève ne souhaite pas que ses données soient utilisées par les partenaires de l'école de conduite à des fins de prospection, il enverra un mail à l'adresse suivante: contact@passpermisfacile.fr</p>
    <p>L'élève bénéficie d'un droit d'accès, de portabilité, de rectification, d'effacement de ses données personnelles, ainsi qu'un droit de limitation ou d'opposition au traitement de celles-ci. Il peut exercer ses droits en s'adressant à passpermisfacile. Il a le droit d'introduire une réclamation auprès de la Commission nationale de l'informatique et des libertés (CNIL).</p>
    <p>Opposition au démarchage téléphonique - En tant que consommateur, si l'élève ne souhaite pas faire l'objet de prospection commerciale par voie téléphonique, il est informé de son droit de s'inscrire gratuitement sur la liste d'opposition au démarchage téléphonique Bloctel sur le site internet :</p>
    <p>http://www.bloctel.gouv.fr ou par courrier Société Opposetel - Service Bloctel, 6, rue Nicolas-Siret, 10000 Troyes.</p>


   @php
    $ville = strtolower(trim($student->user->ville ?? ''));
@endphp

<div class="signature-section">
    <span>Fait à CREIL</span>
    <span class="checkbox-box">{{ $ville == 'creil' ? '✓' : '' }}</span>

    <span>&nbsp;&nbsp;Toulouse</span>
    <span class="checkbox-box">{{ $ville == 'toulouse' ? '✓' : '' }}</span>

    <span>&nbsp;&nbsp;le :</span>
    <span id="signature-date" class="date-placeholder" data-original="XX/XX/XXXX">
        XX/XX/XXXX
    </span>

    <span>en deux exemplaires originaux</span>
</div>
<style>
 .checkbox-box{
    width:14px;
    height:14px;
    border:1px solid #000;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:bold;
}

.checkbox-box.checked {
    background: black;
}
</style>


    <table class="sig-table">
      <thead>
        <tr>
          <th style="width:14%;">Signature de l'élève</th>
          <th style="width:44%;">
            @if($isAdmin)
              Signature du représentant légal de l'élève mineur, le cas échéant<br><br>
            @else
              Signature de l'élève<br><br>
            @endif
            <div style="border:1px solid #000; width:100%; height:90px; position:relative;">
              <canvas id="signature-pad" width="350" height="80" style="width:100%; height:100%; touch-action: none; cursor:crosshair;"></canvas>
              <img id="signature-img" style="display:none; width:100%; height:100%; object-fit:contain;" alt="Signature" />
              <div style="position:absolute; top:4px; right:4px; display:flex; gap:4px; z-index:2;">
                <button type="button" id="signature-clear" style="font-size:9px; padding:2px 6px; position:relative; z-index:2;">Effacer</button>
                <button type="button" id="signature-submit" style="font-size:9px; padding:2px 6px; position:relative; z-index:2;">Valider</button>
              </div>
            </div>
          </th>
          <th style="width:42%;">
            Signature du responsable de l'école de conduite et cachet<br>
    @php
        $signaturePath = base_path('resources/assets/contract.PNG');
        $signatureSrc = file_exists($signaturePath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($signaturePath))
            : asset('assets/signature.png');
    @endphp
    <img src="{{ $signatureSrc }}" alt="Signature" style="max-width:100%; height:auto; margin-top:5px;">
</th>
        </tr>
      </thead>
    </table>

<script>
(function() {
  const isAdmin = @json($isAdmin ?? false);
  const studentId = '{{ $student->id ?? '' }}';
  const modeKey = isAdmin ? 'admin' : 'student';
  const STORAGE_KEY = `contract-signature-${modeKey}-${studentId}`;

  const canvas = document.getElementById('signature-pad');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let drawing = false;
  let lastX = 0;
  let lastY = 0;
  const submitButton = document.getElementById('signature-submit');
  const clearButton = document.getElementById('signature-clear');

  const enableSignaturePad = () => {
    canvas.style.display = 'block';
    canvas.style.pointerEvents = 'auto';
    if (submitButton) submitButton.style.display = 'inline-block';
    if (clearButton) {
      clearButton.style.display = 'inline-block';
    }
  };

  const disableEditableFields = () => {
    if (isAdmin) return;
    document.querySelectorAll('.editable-input, .montant-input').forEach((field) => {
      field.readOnly = true;
      field.style.cursor = 'not-allowed';
      field.style.backgroundColor = 'transparent';
      field.style.border = 'none';
    });
    if (clearButton) {
      clearButton.style.display = 'none';
    }
  };

  const lockSignaturePad = () => {
    canvas.style.pointerEvents = 'none';
    if (submitButton) submitButton.style.display = 'none';
    if (clearButton) clearButton.style.display = 'none';
  };

  const getStoredSignature = () => {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
    } catch {
      return null;
    }
  };

  const setStoredSignature = (img, date) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ img, date }));
    } catch {
      // ignore storage failures
    }
  };

  const clearStoredSignature = () => {
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch {
      // ignore
    }
  };

  const FIELDS_KEY = `contract-fields-${modeKey}-${studentId}`;
  const MONTANTS_KEY = `contract-montants-${modeKey}-${studentId}`;

  const getStoredFields = () => {
    try {
      return JSON.parse(localStorage.getItem(FIELDS_KEY) || 'null') || {};
    } catch {
      return {};
    }
  };

  const setStoredFields = (fields) => {
    try {
      localStorage.setItem(FIELDS_KEY, JSON.stringify(fields));
    } catch {
      // ignore storage failures
    }
  };

  const getStoredMontants = () => {
    try {
      return JSON.parse(localStorage.getItem(MONTANTS_KEY) || 'null') || {};
    } catch {
      return {};
    }
  };

  const setStoredMontants = (montants) => {
    try {
      localStorage.setItem(MONTANTS_KEY, JSON.stringify(montants));
    } catch {
      // ignore storage failures
    }
  };

  const applyStoredFields = (fields) => {
    if (!fields || typeof fields !== 'object') return;
    Object.entries(fields).forEach(([key, value]) => {
      const input = document.getElementById(`field-${key}`);
      if (input) input.value = value;
    });
  };

  const applyStoredMontants = (montants) => {
    if (!montants || typeof montants !== 'object') return;
    const inputs = document.querySelectorAll('.montant-input');
    inputs.forEach((input) => {
      const offerId = input.dataset.offerId;
      if (offerId && montants[`offer_${offerId}`] !== undefined) {
        input.value = montants[`offer_${offerId}`];
      }
      if (input.id === 'field-total' && montants.total !== undefined) {
        input.value = montants.total;
      }
    });
  };

  const readMontants = () => {
    const montants = {};
    const inputs = document.querySelectorAll('.montant-input');
    inputs.forEach((input) => {
      const offerId = input.dataset.offerId;
      if (offerId) {
        montants[`offer_${offerId}`] = input.value;
      }
      if (input.id === 'field-total') {
        montants.total = input.value;
      }
    });
    return montants;
  };

  const readFields = () => {
    return {
      name: document.getElementById('field-name')?.value ?? '',
      dob: document.getElementById('field-dob')?.value ?? '',
      address: document.getElementById('field-address')?.value ?? '',
      email: document.getElementById('field-email')?.value ?? '',
      phone: document.getElementById('field-phone')?.value ?? '',
      evaluationDate: document.getElementById('field-evaluationDate')?.value ?? '',
      totalTrainingHours: document.getElementById('field-totalTrainingHours')?.value ?? '',
      contractEndDate: document.getElementById('field-contractEndDate')?.value ?? '',
    };
  };

  const showStoredSignature = (stored) => {
    if (!stored?.img) {
      clearStoredSignature();
      return false;
    }

    const img = document.getElementById('signature-img');
    if (!img) return false;

    img.src = stored.img;
    img.style.display = 'block';
    canvas.style.display = 'none';
    if (submitButton) submitButton.style.display = 'none';
    updateSignatureDate(stored.date ?? formatDate(new Date()));
    if (!isAdmin) {
      lockSignaturePad();
    }
    return true;
  };

  const storedSignature = getStoredSignature();
  const hasStoredSignature = storedSignature && showStoredSignature(storedSignature);
  if (!hasStoredSignature) {
    enableSignaturePad();
  }

  // Restore editable fields values
  applyStoredFields(getStoredFields());
  applyStoredMontants(getStoredMontants());
  disableEditableFields();

  const updateStoredFields = () => setStoredFields(readFields());
  ['name', 'dob', 'address', 'email', 'phone', 'evaluationDate', 'totalTrainingHours', 'contractEndDate'].forEach((key) => {
    const el = document.getElementById(`field-${key}`);
    if (!el) return;
    el.addEventListener('input', updateStoredFields);
  });

  const updateStoredMontants = () => setStoredMontants(readMontants());
  document.querySelectorAll('.montant-input').forEach((input) => {
    input.addEventListener('input', updateStoredMontants);
  });

  const resizeCanvas = () => {
    const data = canvas.toDataURL();
    const ratio = window.devicePixelRatio || 1;
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    const img = new Image();
    img.onload = () => ctx.drawImage(img, 0, 0);
    img.src = data;
  };

  const getPointerPos = (evt) => {
    const bounds = canvas.getBoundingClientRect();
    const x = (evt.clientX - bounds.left);
    const y = (evt.clientY - bounds.top);
    return { x, y };
  };

  function formatDate(date) {
    const d = String(date.getDate()).padStart(2, '0');
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const y = date.getFullYear();
    return `${d}/${m}/${y}`;
  }

  function updateSignatureDate(value) {
    const dateEl = document.getElementById('signature-date');
    if (!dateEl) return;
    dateEl.textContent = value;
  }

  const start = (evt) => {
    evt.preventDefault();
    drawing = true;
    const pos = getPointerPos(evt);
    lastX = pos.x;
    lastY = pos.y;

    // When user starts signing, set the date to today
    updateSignatureDate(formatDate(new Date()));
  };

  const draw = (evt) => {
    if (!drawing) return;
    evt.preventDefault();
    const pos = getPointerPos(evt);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#000';
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    lastX = pos.x;
    lastY = pos.y;
  };

  const stop = () => {
    drawing = false;
  };

  canvas.addEventListener('pointerdown', start, { passive: false });
  canvas.addEventListener('pointermove', draw, { passive: false });
  canvas.addEventListener('pointerup', stop);
  canvas.addEventListener('pointerleave', stop);
  canvas.addEventListener('pointercancel', stop);

  resizeCanvas();

  document.getElementById('signature-clear')?.addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById('signature-img').style.display = 'none';
    canvas.style.display = 'block';
    document.getElementById('signature-submit').style.display = 'inline-block';

    // Reset date to original (before signature)
    const dateEl = document.getElementById('signature-date');
    if (dateEl) {
      dateEl.textContent = dateEl.dataset.original || dateEl.textContent;
    }

    clearStoredSignature();
  });

  document.getElementById('signature-submit')?.addEventListener('click', () => {
    const img = document.getElementById('signature-img');
    const dataUrl = canvas.toDataURL('image/png');
    img.src = dataUrl;
    img.style.display = 'block';
    canvas.style.display = 'none';
    if (submitButton) submitButton.style.display = 'none';

    // Set the date when signature is validated
    const dateValue = formatDate(new Date());
    updateSignatureDate(dateValue);

    // Persist signature so it remains after reload
    setStoredSignature(dataUrl, dateValue);
    if (!isAdmin) {
      lockSignaturePad();
    }
  });

  window.addEventListener('resize', resizeCanvas);
})();
</script>

</body>
</html>
