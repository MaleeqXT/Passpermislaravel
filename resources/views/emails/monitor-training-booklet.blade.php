<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livret de formation</title>
</head>
<body style="margin:0; padding:0; background:#f2f6f3; color:#173229; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; background:#f2f6f3; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px;">
                    <tr>
                        <td align="center" style="padding:0 0 18px;">
                            <img src="cid:passpermis-booklet-logo@passpermisfacile" alt="PassPermisFacile" width="180" style="display:block; width:180px; max-width:100%; height:auto; border:0; outline:none; text-decoration:none;">
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff; padding:34px 38px; border:1px solid #d9e6dd; border-radius:10px;">
                            <h1 style="margin:0 0 20px; color:#163d2d; font-size:24px; line-height:32px; font-weight:700;">Livret de formation généré</h1>
                            <p style="margin:0 0 18px; color:#334a40; font-size:16px; line-height:26px;">Bonjour {{ $monitorName }},</p>
                            <p style="margin:0 0 18px; color:#334a40; font-size:16px; line-height:26px;">Le livret de formation de <strong>{{ $studentName }}</strong> a été généré le {{ $generatedAt }}.</p>
                            <p style="margin:0; color:#334a40; font-size:16px; line-height:26px;">Vous trouverez le livret PDF complet en pièce jointe.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:22px 12px 0; color:#667a70; font-size:13px; line-height:20px;">© {{ date('Y') }} PassPermisFacile</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>