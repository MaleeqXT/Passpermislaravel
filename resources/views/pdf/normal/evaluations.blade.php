@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'évaluation initiale</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #00496b;
            font-size: 13px;
        }
        h2 {
            margin-bottom: 16px;
            color: #222;
        }
        .section {
            border: 1px solid #00496b;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 4px;
        }
        .section h3 {
            display: block ;
            padding: 6px 4px;
            margin:  0px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        td, th {
            vertical-align: top;
            padding: 4px;
        }
        .label {
            font-weight: bold;
        }
   
        .signature-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 150px;
            margin-left: 10px;
        }
        .range{
            height:4px;
            width:200px;
            background: #222;
        }
        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #333;
            vertical-align: middle;
            line-height: 0.85;
            border-radius: 4px;
            /* padding-left: 2px; */
            font-size: 14px;
            margin-bottom: -6px;
        }
        .filled {
            background: #4a90e2;
            color: #fff;
            width: 13px;
            height: 14px;
            border-radius: 3px;
            display: inline-block;
            text-align: center;
            /* line-height: 14px; */
            padding-left: 1px;
        }
        .space-y-2 > div {
            padding-top: 3px;
            padding-bottom: 3px;
            display: block;
        }
        .sign{
            height: 100px;
            object-fit: contain;
        }
        .mt-10{
            display: inline;
            margin-top: 20px;
        }
        .mb-10{
            margin-bottom: 20px;
        }
        .-mb-10{
            margin-bottom: -40px;
        }
    </style>
</head>
<body>
    @php
    function renderSlider($label, $value) {
        $value = max(0, min(10, (int) $value)); // Ensure 0–10
        $percentage = $value * 10;
    
        return "
            <div style='margin-bottom: 8px;'>
                <span>{$label}:</span> {$value}
                <div style='position: relative; background: #ccc; height: 6px; width: 200px; border-radius: 5px; margin-top: 8px;'>
                    <div style='background: #4a90e2; height: 6px; width: {$percentage}%; border-radius: 5px;'></div>
                    <div style='background: #4a90e2;position: absolute; top: -3px; left: {$percentage}%; width: 12px; height: 12px; border-radius: 50%; transform: translateX(-50%);'></div>
                </div>
            </div>
        ";
    }
    @endphp
    @php
    function renderCheckbox($label, $value) {
        $res = $value ? "<b class='filled'>✓</b>" : '';
        
        return "<span>{$label} &nbsp; </span><span class='checkbox'> {$res}</span>";
    }
    @endphp
    
       
    <h2>Fiche d'évaluation initiale</h2>
    <div class="section">
        <h3>Informations élève</h3>
        <table>
            
            <tr>
                <td><span>Nom, Prénom :</span> {{$student['user']['name']}}</td>
                <td><span>Date de Naissance :</span>
                    {{ Carbon::parse($student['user']['date_naissance'])->format('d/m/Y') }}
                </td>
                <td><span>Téléphone :</span> 
                    @if ($student['user']['phone'])
                        {{ $student['user']['phone'] }}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    {!! renderCheckbox('Port de correction:',  $data['student']['wears_correction']) !!}
                </td>
                <td><span>Acuité visuelle :</span> {{ $data['student']['visual_acuity'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Formation Choisie</h3>
        <table>
            <tr>
                <td>{!! renderCheckbox('Boîte Automatique', $data['training']['automatic']) !!}</td>
                <td>{!! renderCheckbox('Boîte Mécanique', $data['training']['manual']) !!}</td>
                <td>{!! renderCheckbox('Véhicule aménagé', $data['training']['adapted_vehicle']) !!}</td>
            </tr>
            <tr>
                <td>{!! renderCheckbox('Conduite accompagnée', $data['training']['accompanied_driving']) !!}</td>
                <td colspan="2">{!! renderCheckbox('Conduite supervisée', $data['training']['supervised_driving']) !!}</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h3>Expérience de conduite</h3>
        <table>
            <tr>
                <td><span>Permis:</span></td>
                <td><span>Conduite Auto:</span></td>
                <td><span>Autres véhicules:</span></td>
            </tr>
            <tr>
                <td class="space-y-2">
                    <div>
                        {!! renderCheckbox('AM', $data['experience']['license']['am']) !!}
                    </div>
                    <div>
                        {!! renderCheckbox('B1', $data['experience']['license']['b1']) !!}
                    </div>
                    <div>
                      {!! renderCheckbox('A1', $data['experience']['license']['a1']) !!}
                    </div>
                </td>
                <td class="space-y-2">
                    <div>{!! renderCheckbox('Déjà conduit', $data['experience']['driving']['already_driven']) !!}</div>
                    <div>{!! renderCheckbox('Avec Parents', $data['experience']['driving']['with_parents']) !!}</div>
                    <div>{!! renderCheckbox('En Auto-école', $data['experience']['driving']['with_driving_school']) !!}</div>
                </td>
                <td class="space-y-2">
                    <div>{!! renderCheckbox('Cyclomoteur', $data['experience']['other_vehicles']['moped']) !!}</div>
                    <div>{!! renderCheckbox('Vélo', $data['experience']['other_vehicles']['bike']) !!}</div>
                    <div>{!! renderCheckbox('Tracteur', $data['experience']['other_vehicles']['tractor']) !!}</div>
                    <div>{!! renderCheckbox('Voiturette', $data['experience']['other_vehicles']['cart']) !!}</div>
                    <div>{!! renderCheckbox('Quad', $data['experience']['other_vehicles']['quad']) !!}</div>
                    <div>{!! renderCheckbox('Tondeuse', $data['experience']['other_vehicles']['lawnmower']) !!}</div>
                    <div>{!! renderCheckbox('Caddie', $data['experience']['other_vehicles']['trolley']) !!}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="section">
        <h3>Connaissance du véhicule</h3>
        <table>
            <tr>
                <td>{!! renderCheckbox('Direction', $data['vehicle_knowledge']['steering']) !!}</td>
                <td>{!! renderCheckbox('Embrayage', $data['vehicle_knowledge']['clutch']) !!}</td>
                <td>{!! renderCheckbox('Boîte de vitesse', $data['vehicle_knowledge']['gearbox']) !!}</td>
                <td>{!! renderCheckbox('Freinage', $data['vehicle_knowledge']['braking']) !!}</td>
            </tr>
        </table>
    </div>
    
    
    <div class="section">
        <h3>Attitude vis-à-vis de la formation</h3>
        <table>
            <tr>
                <td>{!! renderCheckbox('Maîtriser la voiture et le code', $data['attitude']['master_car_and_code']) !!}</td>
                <td>{!! renderCheckbox("C'est un passage inévitable", $data['attitude']['inevitable_step']) !!}</td>
            </tr>
            <tr>
                <td>{!! renderCheckbox('Prévoir les difficultés, savoir y faire face', $data['attitude']['anticipate_difficulties']) !!}</td>
                <td>{!! renderCheckbox('Envie / Désir de le faire', $data['attitude']['desire_to_do_it']) !!}</td>
            </tr>
        </table>
    </div>
    

    <div class="section">
        <h3>Habiletés constatées lors de l'évaluation</h3>
        <table>
            <tr>
                <td>Installation dans le véhicule: {{ $data['skills']['vehicle_setup'] }}</td>
                <td>Démarrages: {{ $data['skills']['starting'] }}</td>
                <td>Arrêts: {{ $data['skills']['stopping'] }}</td>
            </tr>
            <tr>
                <td colspan="3">Manipulation du volant: {{ $data['skills']['steering_wheel'] }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Compréhension / Restitution</h3>
        <table>
            <tr>
                <td>
                     {!! renderSlider('Compréhension',  $data['understanding']['comprehension']) !!}</td>
                <td>
                     {!! renderSlider('Restitution',  $data['understanding']['restitution']) !!}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Gestion de l'environnement</h3>
        <table>
            <tr>
                <td>
                    {!! renderSlider('Maîtrise des trajectoires',  $data['environment']['trajectory']) !!}</td>
                <td>
                     {!! renderSlider('Orientation',  $data['environment']['orientation']) !!}</td>
                <td>
                     {!! renderSlider('Observation',  $data['environment']['observation']) !!}
                  </td>
            </tr>
            <tr>
             
                <td>
                    {!! renderSlider('Regard',  $data['environment']['look']) !!}
                </td>
            </tr>
        </table>
    </div>
    <div class="section">
        <h3>Gestion des émotions</h3>
        <table>
            <tr>
                <td>{!! renderSlider('Relationnel',  $data['emotions']['relationship']) !!}</td>
               
                <td>{!! renderSlider('Crispation', $data['emotions']['tension']) !!}</td>
            </tr>
         
        </table>
    </div>

    <div class="section space-y-2">
        <h3>Résultat de l'évaluation</h3>
        <div>
            Score de l'évaluation: {{ $data['results']['score'] ?? 'N/A' }}
        </div>
        <div>
            Nombre de leçons proposées: {{ $data['results']['lessons_proposed'] ?? 'N/A' }}
        </div>
        <div>
            Proposition acceptée: {{ strtoupper($data['results']['proposal_accepted']) }}
        </div>
        <div class="mb-10">Fait le : {{  Carbon::parse($created_at)->format('d/m/Y') }}</div>
        <div class="mb-10">Signature parents (si élève mineur): <span class="signature-line">{{ $data['results']['parent_signature'] }}</span></div>
        <div class="mb-10">Signature élève: <span class="">{{ $data['results']['student_signature'] ?? $student['user']['name'] }}</span></div>
        <div class="mb-10" >Signature moniteur: 
            <span class="">
                <span> {{$monitor['user']['name']}}</span>
            </span>
            <img src="{{ $data['results']['monitor_signature'] }}" class="sign mt-10 -mb-10"  alt="">
    </div>
        <div >
               
                Cachet de l'auto-école:  <b> ETIENNE FLORIAN gérant de l’auto-école   PASSPERMISFACILE</b>

            <img src="{{ public_path('assets/signature.png') }}" class="sign mt-10" alt="Cachet de l'auto école" />
        </div>
      

    </div>

</body>
</html>
