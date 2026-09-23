@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation sur l'honneur CPF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            padding: 20px;
            color: #002060;
        }
        h1 {
            font-size: 11px;
            text-align: center;
            margin-bottom: 20px;
        }
        .center {
            text-align: center;
        }
        h2 {
            font-size: 10px;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .underline {
            text-decoration: underline;
        }
        .bold {
            font-weight: bold;
        }
        .checkbox {
            font-family: Arial;
            margin-right: 5px;
        }
        .signature-line {
            width: 300px;
            border-top: 1px solid #000;
            display: inline-block;
            margin-top: 50px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
        }
        td {
            padding: 5px;
            vertical-align: top;
        }
        .border-top {
            border-top: 1px solid #000;
            margin: 15px 0;
        }
    </style>
</head>
<body>

<img style="margin-top: -10px;z-index: 10000"  src="{{public_path('assets/cpf/logo-cpf.png')}}" width="120" >
<!-- Page 1 -->
<h1>Attestation sur l'honneur<br>pour s'inscrire à une prestation « Permis de conduire »<br>financée par le compte personnel de formation (CPF)</h1>

<p class="center" style="margin-top: -5px">Vous avez choisi de mobiliser vos droits CPF pour obtenir un permis de conduire de la typologie mentionnée à l'article R. 221-4 du code de la route (cocher le permis financé) :</p>

<table>
    <tr>
        <td>Catégorie A</td>
        <td>Catégorie B</td>
        <td>Catégorie C</td>
        <td>Catégorie D</td>
    </tr>
    <tr>
        <td><span class="checkbox">o</span> A</td>
        <td><span class="checkbox">@if(isset($data['boite']['id']) && $data['boite']['id'] =='auto') <b style="color: green"> x </b> @else o  @endif</span> B</td>
        <td><span class="checkbox">o</span> C</td>
        <td><span class="checkbox">o</span> D</td>
    </tr>
    <tr>
        <td><span class="checkbox">o</span> A1</td>
        <td><span class="checkbox">o</span> B1</td>
        <td><span class="checkbox">o</span> C1</td>
        <td><span class="checkbox">o</span> D1</td>
    </tr>
    <tr>
        <td><span class="checkbox">o</span> A2</td>
        <td><span class="checkbox">o</span> BE</td>
        <td><span class="checkbox">o</span> CE</td>
        <td><span class="checkbox">o</span> DE</td>
    </tr>
    <tr>
        <td></td>
        <td><span class="checkbox">o</span> B96</td>
        <td><span class="checkbox">o</span> C1E</td>
        <td><span class="checkbox">o</span> D1E</td>
    </tr>
    <tr>
        <td></td>
        <td><span class="checkbox">@if(isset($data['boite']['id']) && $data['boite']['id'] =='manual') <b style="color: green"> x </b> @else o  @endif</span> B78</td>
        <td></td>
        <td></td>
    </tr>
</table>

<h2>Rappel des trois conditions d'éligibilité du titulaire CPF /de l'apprenant :</h2>

<p><span class="bold">1°</span> l'obtention du permis de conduire contribue à la réalisation de votre projet professionnel ou à favoriser la sécurisation de votre parcours professionnel ;</p>
<p><span class="bold">2°</span> vous ne faites pas l'objet d'une suspension de permis de conduire, d'une interdiction de solliciter un permis de conduire ou d'une récupération de points.</p>
<p><span class="bold">3° la prestation achetée avec vos droits CPF ne doit pas correspondre à :</span></p>

<ul>
    <li>des heures de conduite complémentaires ;</li>
    <li>une remise à niveau ou à une récupération de points ;</li>
    <li>des heures de conduite permettant de passer de la boite manuelle à la boite automatique ou inversement ;</li>
    <li>la formation de 7 heures permettant aux titulaires du permis B de conduire un deux-roues ou trois-roues motorisé ;</li>
    <li>la formation complémentaire de 7 heures pour l'obtention du permis A si vous avez le permis A2 depuis au moins 2 ans ;</li>
    <li>adapter le poste de travail déjà occupé au sein de l'entreprise (le financement revient exclusivement à l'employeur dans ce cas).</li>
</ul>

<p class="bold">Merci de répondre au questionnaire ci-dessous en fonction de votre situation :</p>
<div class="page-break"></div>
<h2>Cas n°1 L'obtention du permis de conduire contribuerait à la réalisation de votre projet professionnel.</h2>

<p><em>Si oui, répondre aux deux questions ci-dessous :</em></p>

<p>Quel est le projet ? </p>
<p style="color: black" class="bold">…{{$data['attestation_honneur']['q1'] ?? null}}</p>

<p>En quoi ce permis contribuerait à la réalisation de votre projet ? </p>
<p style="color: black" class="bold">…{{$data['attestation_honneur']['q2'] ?? null}}</p>

<h2>Cas n°2 L'obtention du permis de conduire contribuerait à la sécurisation de votre parcours professionnel :</h2>

<ul>
    <li><span class="checkbox">@if(isset($data['attestation_honneur']['q3']) && $data['attestation_honneur']['q3']['id'] ==1) <b style="color: green"> x </b> @else o  @endif</span>Il facilitera votre recherche d'emploi</li>
    <li><span class="checkbox">@if(isset($data['attestation_honneur']['q3']) && $data['attestation_honneur']['q3']['id'] ==2) <b style="color: green"> x </b> @else o  @endif</span>En application d'une clause de mobilité géographique, votre lieu de travail est maintenant significativement éloigné de votre domicile</li>
    <li><span class="checkbox">@if(isset($data['attestation_honneur']['q3']) && $data['attestation_honneur']['q3']['id'] ==3) <b style="color: green"> x </b> @else o  @endif</span>Vous serez bientôt amené(e) à travailler en horaire décalé (notamment la nuit)</li>
    <li><span class="checkbox">@if(isset($data['attestation_honneur']['q3']) && $data['attestation_honneur']['q3']['id'] ==4) <b style="color: green"> x </b> @else o  @endif</span>Vous êtes amené(e) à exercer des contrats de travail successifs sur des lieux éloignés de votre domicile</li>
    <li><span class="checkbox">@if(isset($data['attestation_honneur']['q3']) && $data['attestation_honneur']['q3']['id'] ==5) <b style="color: green"> x </b> @else o  @endif</span>Autre (préciser) : …{{$data['attestation_honneur']['q3_feedback'] ?? null}}</li>
</ul>



<h2>Cas n°3 - L'obtention du permis de conduire contribuerait à une évolution professionnelle (et non pas adaptation du poste de travail) au sein de votre entreprise.</h2>

<p>Laquelle ? : </p>
<p style="color: black" class="bold">…{{$data['attestation_honneur']['q4'] ?? null}}</p>

<p><span class="checkbox">@if(isset($data['attestation_honneur']['q5']) && $data['attestation_honneur']['q5']['id'] ==1) <b style="color: green"> x </b> @else o  @endif</span> <span class="bold">J'atteste sur l'honneur ne pas être en situation de suspension/retrait de permis ou d'interdiction de passer le permis de conduire.</span></p>

<p><span class="checkbox">@if(isset($data['attestation_honneur']['q5']) && $data['attestation_honneur']['q5']['id'] ==2) <b style="color: green"> x </b> @else o  @endif</span> <span class="bold">Je dispose déjà d'une ou plusieurs catégories de permis de conduire d'un véhicule terrestre à moteur en cours de validité.</span></p>
<p>Préciser laquelle ou lesquelles : …{{$data['attestation_honneur']['q5_feedback'] ?? null}}</p>

<p class="bold">Avez-vous déjà mobilisé votre CPF pour financer un permis de véhicule terrestre à moteur ?</p>

<p><span class="checkbox">@if(isset($data['attestation_honneur']['q6']) && $data['attestation_honneur']['q6']['id']==1) <b style="color: green"> x </b> @else o  @endif</span>
    <span class="bold">OUI</span>
    <span class="checkbox">@if(isset($data['attestation_honneur']['q6']) && $data['attestation_honneur']['q6']['id']==2) <b style="color: green"> x </b> @else o  @endif</span><span class="bold">NON</span></p>

<p>Dans l'affirmative, lequel/lesquels Et quand </p>
<p style="color: black" class="bold">…{{$data['attestation_honneur']['q7'] ?? null}}</p>

<p class="bold">Je soussigné M/ Mme <span style="color: black" class="bold">{{ $data['test_pro']['name']}} </span></p>

<p>domicilié(e) à {{$data['attestation_honneur']['q8'] ?? null}}</p>

<p><span class="checkbox">o</span> <span class="bold">Atteste sur l'honneur que l'utilisation de mon CPF financera une action éligible comme indiquée ci-dessus et que mes déclarations sont sincères.</span></p>

<p><em>Avertissement : « Constitue un faux toute altération frauduleuse de la vérité, de nature à causer un préjudice et accomplie par quelque moyen que ce soit, dans un écrit ou tout autre support d'expression de la pensée qui a pour objet ou qui peut avoir pour effet d'établir la preuve d'un droit ou d'un fait ayant des conséquences juridiques. » article 441-1 du code pénal. Le faux, l'usage de faux ainsi que la tentative sont punis de trois ans d'emprisonnement et de 45 000 € d'amende au-delà de l'obligation de rembourser les droits CPF indûment mobilisés.</em></p>

<p>A…………… <b>Toulouse ……………</b>Le……………<b>{{ Carbon::parse($data['created_at'])->format('d/m/Y') }}</b>………………</p>

<table style="width: 100%; border: none; margin-top: 50px;">
    <tr>
        <td style="width: 50%;">
            <p>Signature du titulaire :</p>
          <div class="center">
              <b>  {{ $data['test_pro']['name']}}</b>
              <img style="margin-left -60px;z-index: 10000" src="{{$data['test_pro']['signature']}}" width="120" height="40">
          </div>
        </td>
        <td style="width: 40%;">
            <p>Nom et signature du responsable de l'organisme de formation :</p>
            <div>
                <b> ETIENNE FLORIAN gérant de l’auto-école PASSPERMISFACILE</b>
                <img style="margin-top: -10px;z-index: 10000"  src="{{public_path('assets/signature.png')}}" width="120" height="40">
            </div>
        </td>
    </tr>
</table>
<p><em>Attention : L'attestation doit être proposée au titulaire par l'organisme de formation qui en assure la bonne complétude et la conserve. Elle pourra être demandée à tout moment par la Caisse des Dépôts.</em></p>


<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>


<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>

<br>
<br>
<br>
<br>
<br>

<img style="margin-top: -10px;z-index: 10000"  src="{{public_path('assets/cpf/footer-cpf.png')}}"  >

</body>
</html>
