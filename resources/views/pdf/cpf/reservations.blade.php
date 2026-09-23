<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>DOCS ANNEXES CPF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .signature-line {
            height: 20px;
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 100px;
        }
    </style>
</head>
<body>
<h1>DOCS ANNEXES CPF</h1>
<h2> <b>{{$data['test_pro']['name']}}</b></h2>
<h3> numero de dossier cpf : <b>{{$data['numero_cpf'] ?? null}}</b></h3>

<table>
    <thead>
    <tr>
        <th>DATE</th>
        <th>DURÉE</th>
        <th>HORAIRES</th>
        <th>SIGNATURE</th>
    </tr>
    </thead>
    <tbody>
    @foreach($data['reservations']  as $index => $value)
    <tr>
        <td>{{$value['date']}}</td>
        <td>{{$value['houre']}}H</td>
        <td>{{ $value['start'] }}/{{ $value['end'] }}</td>

        <td>  <img style="margin-top: -10px;z-index: 10000" src="{{$data['test_pro']['signature']}}" width="120" height="40"></td>
    </tr>
    @endforeach

    </tbody>
</table>
</body>
</html>
