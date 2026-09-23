@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de Fin de Formation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        .content {
            margin: 0 auto;
            max-width: 600px;
        }
        .underline {
            font-weight: bold;
        }
        .signature-area {
            margin-top: 60px;
            text-align: right;
        }
        .stamp-area {
            margin-top: 20px;
            text-align: center;
            font-style: italic;
        }
        .info-block {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="content">
    <h1>ATTESTATION DE FIN DE FORMATION</h1>

    <p>Je soussigne(e),</p>

    <div class="info-block">
        <p>ETIENNE Florian, representant legal de l'auto-ecole PASSPERMISFACILE, SIRET 979 143 294 00036,</p>
        <p>Numero de declaration d'activite : 76311313631,</p>
    </div>

    <p>certifie que :</p>

    <div class="info-block">
        <p>Nom du stagiaire : <span class="underline"> {{ $data['test_pro']['name']}}</span></p>
        <p>Date de naissance : <span class="underline">{{ Carbon::parse($data['test_pro']['date_naissance'])->format('m/d/Y')}} </span></p>
        <p>Numero de dossier CPF (si disponible) : <span class="underline">{{ $data['numero_cpf']}}</span></p>
    </div>

    <p>a suivi une formation a la conduite de vehicules de la categorie B au sein de notre stabilissement</p>
    <p>dans le cadre du Compte Personnel de Formation (CPF).</p>

    <div class="info-block">
        <p>Periode de formation : du <span class="underline"> {{$reservation['first']}}</span> au <span class="underline">{{$reservation['end']}}</span></p>
        <p>Nombre d'heures effectuees : <span class="underline">{{$reservation['total_hours']}}</span> heures</p>
    </div>

    <div class="info-block">
        <p>Fait a : <span class="underline">Toulouse</span></p>
        <p>Le : <span class="underline">{{ now()->format('d/m/Y') }}</span></p>
    </div>

    <div class="signature-area">
        <p>Signature du responsable de formation :</p>
        <div>
            <b> ETIENNE FLORIAN gérant de l’auto-école PASSPERMISFACILE</b>
            <img style="margin-top: -10px;z-index: 10000"  src="{{public_path('assets/signature.png')}}" width="120" height="40">
        </div>
    </div>

</div>
</body>
</html>
