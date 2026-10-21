<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Planning — {{ $monitorName }}</title>
    <style>
        @page { margin: 24px 28px; }
        * { box-sizing: border-box; }
        body { color: #182638; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.4; }
        .header { border-bottom: 3px solid #5b7f28; padding-bottom: 15px; margin-bottom: 22px; }
        .school { color: #5b7f28; font-size: 10px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        h1 { color: #14233a; font-size: 23px; margin: 6px 0 4px; }
        .meta { color: #5d6b7c; font-size: 10px; }
        .summary { background: #f3f7ef; border: 1px solid #dce8cf; margin-bottom: 18px; padding: 11px 13px; }
        .summary-table { border-collapse: collapse; width: 100%; }
        .summary-table td { width: 50%; padding: 2px 0; vertical-align: top; }
        .summary-label { color: #637285; font-size: 9px; text-transform: uppercase; }
        .summary-value { color: #14233a; font-size: 11px; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; }
        thead { background: #14233a; color: #ffffff; }
        th { font-size: 8px; font-weight: bold; letter-spacing: .35px; padding: 9px 7px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #dfe5ea; padding: 9px 7px; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f8fafb; }
        .time { font-weight: bold; white-space: nowrap; }
        .muted { color: #687687; }
        .status { color: #38591d; font-weight: bold; }
        .empty { border: 1px dashed #bec9d3; color: #5d6b7c; margin-top: 18px; padding: 26px; text-align: center; }
        .footer { border-top: 1px solid #dfe5ea; color: #728093; font-size: 8px; margin-top: 22px; padding-top: 9px; text-align: center; }
    </style>
</head>
<body>
    <section class="header">
        <div class="school">{{ $schoolName }}</div>
        <h1>Planning du moniteur</h1>
        <div class="meta">Document préparé pour impression et archivage.</div>
    </section>

    <section class="summary">
        <table class="summary-table">
            <tr>
                <td>
                    <div class="summary-label">Moniteur</div>
                    <div class="summary-value">{{ $monitorName }}</div>
                </td>
                <td>
                    <div class="summary-label">Période</div>
                    <div class="summary-value">{{ $periodLabel }}</div>
                </td>
            </tr>
        </table>
    </section>

    @if ($rows->isEmpty())
        <div class="empty">Aucune disponibilité ou leçon n’est planifiée pour cette période.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 23%">Date</th>
                    <th style="width: 13%">Horaire</th>
                    <th style="width: 22%">Élève</th>
                    <th style="width: 24%">Lieu</th>
                    <th style="width: 18%">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ ucfirst($row['date']) }}</td>
                        <td class="time">{{ $row['start'] }} – {{ $row['end'] }}</td>
                        <td>{{ $row['student'] }}</td>
                        <td class="muted">{{ $row['location'] }}</td>
                        <td class="status">{{ $row['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Généré le {{ $generatedAt->translatedFormat('d/m/Y à H:i') }} · {{ $schoolName }}</div>
</body>
</html>
