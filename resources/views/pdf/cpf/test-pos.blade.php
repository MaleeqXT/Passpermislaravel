@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test de positionnement en ligne CPF PPF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
        }
        h1 {
            font-size: 14px;

            margin-bottom: 20px;
        }
        h2 {
            font-size: 13px;
            margin-top: 15px;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        h3 {
            font-size: 12px;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .underline {
            text-decoration: underline;
        }
        .bold {
            font-weight: bold;
        }
        .center {
            text-align: center;
        }
        .info-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 15px;
        }
        .checkbox {
            margin-right: 5px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            display: inline-block;
            margin-top: 30px;
        }
        .page-break {
            page-break-after: always;
        }
        ul {
            margin-top: 5px;
            margin-bottom: 5px;
            padding-left: 20px;
        }
        li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
<!-- Page 1 -->
<p class=" "><span class="bold">Le test de positionnement en ligne </span>, appelé aussi évaluation de niveau, est une étape clé pour<br>estimer le volume d'heures de conduite nécessaire avant d'accepter un dossier CPF.</p>

<div style="border-top: 1px solid #000; margin: 15px 0;"></div>

<h1 class="bold">Évaluation de Niveau – Permis de Conduire CPF</h1>

<p class=" ">Auto-École PASSPERMISFACILE</p>

<h2>Informations de l'élève</h2>

<ul>
    <li>Nom et Prénom : <b>{{$data['test_pro']['name']}}</b>.</li>
    <li>Date de naissance : <b>{{$data['test_pro']['date_naissance']}}</b>.</li>
    <li>Numéro de téléphone : <b>{{$data['test_pro']['phone']}}</b></li>
    <li>Adresse e-mail : <b>{{$data['test_pro']['email']}}</b></li>
    <li>Catégorie de permis visée : <b>{{$data['test_pro']['type']}}</b></li>
</ul>

<div style="border-top: 1px solid #000; margin: 15px 0;"></div>

<h2>1. Expérience de conduite</h2>

<p>Avez-vous déjà conduit un véhicule (auto/moto) ?</p>
<ul>
    <li><span class="checkbox"> @if($data['test_pro']['first']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Oui, régulièrement</li>
    <li><span class="checkbox">@if($data['test_pro']['first']['id']==2) <b style="color: green"> x </b>@else o @endif</span> Oui, occasionnellement</li>
    <li><span class="checkbox">@if($data['test_pro']['first']['id']==3) <b style="color: green"> x </b> @else o @endif</span> Non, jamais</li>
</ul>

<p>Si oui, dans quel cadre ?</p>
<ul>
    <li><span class="checkbox">@if($data['test_pro']['two']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Accompagné par un proche</li>
    <li><span class="checkbox">@if($data['test_pro']['two']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Conduite supervisée</li>
    <li><span class="checkbox">@if($data['test_pro']['two']['id']==3) <b style="color: green"> x </b> @else o  @endif</span> Auto-école (cours interrompus)</li>
    <li><span class="checkbox">@if($data['test_pro']['first']['id']==4) <b style="color: green"> x </b> @else o  @endif</span> Autre : {{$data['test_pro']['two_feedback']}}</li>
</ul>

<div style="border-top: 1px solid #000; margin: 15px 0;"></div>

<h2>2. Connaissances du Code de la Route</h2>

<p>Avez-vous déjà obtenu l'examen du Code ?</p>
<ul>
    <li><span class="checkbox">@if($data['test_pro']['three']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Oui, encore valide</li>
    <li><span class="checkbox">@if($data['test_pro']['three']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Oui, mais expiré</li>
    <li><span class="checkbox">@if($data['test_pro']['three']['id']==3) <b style="color: green"> x </b> @else o  @endif</span> Non, jamais</li>
</ul>

<p>Votre connaissance des panneaux de signalisation est-elle :</p>
<ul>
    <li><span class="checkbox">@if($data['test_pro']['four']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Très bonne</li>
    <li><span class="checkbox">@if($data['test_pro']['four']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Moyenne</li>
    <li><span class="checkbox">@if($data['test_pro']['four']['id']==3) <b style="color: green"> x </b> @else o  @endif</span> Faible</li>
</ul>

<!-- Page Break -->


<!-- Page 2 -->
<h3>5 Savez-vous ce que signifie un panneau de priorité ?</h3>
<ul>
    <li><span class="checkbox">@if($data['test_pro']['five']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Oui</li>
    <li><span class="checkbox">@if($data['test_pro']['five']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Non</li>
</ul>

<h3>6 Que signifie un marquage au sol en zigzag devant un arrêt de bus ?</h3>
<ul>
    <li><span class="checkbox">@if($data['test_pro']['sex']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Interdiction de stationner</li>
    <li><span class="checkbox">@if($data['test_pro']['sex']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Zone d'arrêt temporaire</li>
    <li><span class="checkbox">@if($data['test_pro']['sex']['id']==3) <b style="color: green"> x </b> @else o  @endif</span> Priorité piétonne</li>
</ul>

<div style="border-top: 1px solid #000; margin: 15px 0;"></div>

<h2>3. Maîtrise technique d'un véhicule (auto-évaluation)</h2>

<h3>7 Êtes-vous à l'aise avec :</h3>
<table>
    <tr>
        <td>Le démarrage et l'arrêt du véhicule ?</td>
        <td width="50"><span class="checkbox">@if($data['test_pro']['seven']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Oui</td>
        <td width="50"><span class="checkbox">@if($data['test_pro']['seven']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Non</td>
    </tr>
    <tr>
        <td>Les changements de vitesse ?</td>
        <td><span class="checkbox">@if($data['test_pro']['eight']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Oui</td>
        <td><span class="checkbox">@if($data['test_pro']['eight']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Non</td>
    </tr>
    <tr>
        <td>Les manœuvres (créneau, marche arrière) ?</td>
        <td><span class="checkbox">@if($data['test_pro']['nine']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Oui</td>
        <td><span class="checkbox">@if($data['test_pro']['nine']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Non</td>
    </tr>
    <tr>
        <td>La gestion des distances de sécurité ?</td>
        <td><span class="checkbox">@if($data['test_pro']['ten']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Oui</td>
        <td><span class="checkbox">@if($data['test_pro']['ten']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Non</td>
    </tr>
</table>

<h3>8 Avez-vous des difficultés avec :</h3>
<ul>
    <li><span class="checkbox">@if($data['test_pro']['eleven']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> La coordination des pédales (embrayage, frein, accélérateur)</li>
    <li><span class="checkbox">@if($data['test_pro']['eleven']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> L'anticipation des dangers</li>
    <li><span class="checkbox">@if($data['test_pro']['eleven']['id']==3) <b style="color: green"> x </b> @else o  @endif</span> L'adaptation à la circulation</li>
</ul>

<div style="border-top: 1px solid #000; margin: 15px 0;"></div>

<h2>4. Résultat de l'évaluation</h2>

<p> Niveau estimé :</p>
<ul>
    <li><span class="checkbox">@if($data['test_pro']['twelve']['id']==1) <b style="color: green"> x </b> @else o  @endif</span> Débutant (30h et plus recommandées)</li>
    <li><span class="checkbox">@if($data['test_pro']['twelve']['id']==2) <b style="color: green"> x </b> @else o  @endif</span> Intermédiaire (20-25h recommandées)</li>
    <li><span class="checkbox">@if($data['test_pro']['twelve']['id']==3) <b style="color: green"> x </b> @else o  @endif</span> Expérimenté (10-15h recommandées)</li>
</ul>

<div style="border-top: 1px solid #000; margin: 15px 0;"></div>

<p class="bold">Commentaire du formateur :</p>
<p>{{$data['test_pro']['commentaire_formateur']}}..</p>

<p class="bold">Nombre d'heures préconisées : {{$data['test_pro']['nb_heur']}} h</p>

<!-- Page Break -->


<!-- Page 3 -->
<p> Évaluation réalisée le :<b> {{Carbon::parse($data['test_pro']['date_evaluation'])->format('d/m/Y')}}</b></p>

<div style="margin-top: 30px;">
    <p> Signature du formateur : <span class="">
          <b>ETIENNE FLORIAN gérant de l’auto-école PASSPERMISFACILE</b>
              <img style="margin-top: -10px;z-index: 10000"  src="{{public_path('assets/signature.png')}}" width="120" height="40">
        </span></p>
    <p> Signature de l'élève : <span class="">
                                <b>{{$data['test_pro']['name']}}</b>
                          <img style="margin-left: -80px;margin-top: 70px;z-index: 10000"  src="{{$data['test_pro']['signature']}}" width="120" height="40">

        </span></p>
</div>



</body>
</html>
