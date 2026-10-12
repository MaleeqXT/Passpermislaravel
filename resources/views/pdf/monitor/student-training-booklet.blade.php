<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; color: #17233d; font-size: 10px; line-height: 1.45; margin: 0; }
    .header { padding: 22px 25px 18px; background: #426f2d; color: #fff; }
    .header h1 { font-size: 23px; margin: 0 0 4px; }
    .header p { font-size: 11px; margin: 0; color: #edf7eb; }
    .summary { margin: 17px 25px; padding: 11px 13px; border: 1px solid #dcead9; background: #f4fbf2; border-radius: 6px; }
    .summary strong { color: #315c27; }
    .meta { margin: 0 25px 14px; color: #526078; }
    table { width: calc(100% - 50px); margin: 0 25px; border-collapse: collapse; table-layout: fixed; }
    thead { background: #edf6eb; color: #315c27; }
    th { text-align: left; padding: 8px 6px; font-size: 9px; border: 1px solid #d8e5d4; }
    td { padding: 8px 6px; vertical-align: top; border: 1px solid #e3e9f1; overflow-wrap: break-word; }
    tbody tr:nth-child(even) { background: #fafcfd; }
    .col-num { width: 5%; }.col-date { width: 10%; }.col-time { width: 12%; }.col-duration { width: 8%; }.col-instructor { width: 14%; }.col-offer { width: 17%; }.col-location { width: 20%; }.col-type { width: 14%; }
    .footer { margin: 18px 25px 0; color: #718096; font-size: 9px; }
  </style>
</head>
<body>
  <header class="header">
    <h1>Livret de formation</h1>
    <p>Élève : {{ $student->user?->name ?? 'Non renseigné' }}</p>
  </header>
  <section class="summary">
    <strong>{{ $rows->count() }}</strong> leçon(s) de formation · <strong>{{ intdiv($totalMinutes, 60) }} h{{ $totalMinutes % 60 ? ' '.str_pad((string) ($totalMinutes % 60), 2, '0', STR_PAD_LEFT) : '' }}</strong> au total
  </section>
  <p class="meta">Document généré le {{ $generatedAt->format('d/m/Y à H:i') }}. Il contient toutes les leçons du candidat.</p>
  <table>
    <thead><tr><th class="col-num">#</th><th class="col-date">Date</th><th class="col-time">Horaire</th><th class="col-duration">Durée</th><th class="col-instructor">Enseignant</th><th class="col-offer">Forfait</th><th class="col-location">Lieu</th><th class="col-type">Type</th></tr></thead>
    <tbody>
      @forelse ($rows as $row)
        <tr><td>{{ $row['number'] }}</td><td>{{ $row['date'] }}</td><td>{{ $row['start'] }} – {{ $row['end'] }}</td><td>{{ $row['duration'] }}</td><td>{{ $row['instructor'] }}</td><td>{{ $row['offer'] }}</td><td>{{ $row['location'] }}</td><td>{{ $row['type'] }}</td></tr>
      @empty
        <tr><td colspan="8">Aucune leçon de formation enregistrée.</td></tr>
      @endforelse
    </tbody>
  </table>
  <p class="footer">PassPermisFacile · Livret de formation de l’élève</p>
</body>
</html>