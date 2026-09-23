<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PermiFacile</title>
</head>
<body style="margin:0; padding:0; background:#edf3f8; color:#435160; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; background:#edf3f8; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="570" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:570px;">
                    <tr>
                        <td style="background:#ffffff; padding:38px 40px; border-radius:3px;">
                            <p style="margin:0 0 28px; color:#162b4d; font-size:24px; line-height:30px; font-weight:700;">Passpermisfacile</p>
                            <h1 style="margin:0 0 20px; color:#435160; font-size:24px; line-height:32px; font-weight:700;">{{ $heading }}</h1>
                            @foreach ($lines as $line)
                                <p style="margin:0 0 20px; color:#71839e; font-size:18px; line-height:30px;">{{ $line }}</p>
                            @endforeach
                            <p style="margin:0; color:#71839e; font-size:18px; line-height:30px;">Bien cordialement,</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:28px 12px 0; color:#8a9ab2; font-size:14px; line-height:20px;">
                            © {{ date('Y') }} PermiFacile. Tous droits réservés.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
