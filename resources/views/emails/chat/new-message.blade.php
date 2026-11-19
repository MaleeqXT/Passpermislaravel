<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4f6f7;color:#1f2c3a;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px;"><tr><td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e0e6e9;border-radius:14px;overflow:hidden;">
      <tr><td style="background:#17233d;padding:22px 28px;color:#fff;font-size:21px;font-weight:700;">PassPermisFacile</td></tr>
      <tr><td style="padding:30px 28px;">
        <p style="margin:0 0 16px;font-size:16px;">Bonjour,</p>
        <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">Vous avez reçu un nouveau message sur votre espace PassPermisFacile.</p>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;background:#f5f8f6;border-left:4px solid #2f7654;border-radius:6px;"><tr><td style="padding:14px 16px;font-size:14px;line-height:1.5;"><strong>Expéditeur :</strong> {{ $senderName }}<br><strong>Rôle :</strong> {{ \App\Mail\NewChatMessageMail::roleLabel($sender) }}<br><span style="color:#637078;">Un nouveau message vous attend dans votre messagerie.</span></td></tr></table>
        <p style="margin:0 0 24px;"><a href="{{ $conversationUrl }}" style="display:inline-block;background:#2f7654;border-radius:7px;padding:12px 18px;color:#fff;font-size:14px;font-weight:700;text-decoration:none;">Consulter le message</a></p>
        <p style="margin:0;color:#637078;font-size:12px;line-height:1.5;">Pour votre sécurité, connectez-vous à votre espace avant de consulter la conversation.</p>
      </td></tr>
      <tr><td style="padding:18px 28px;background:#f4f6f7;color:#66727b;font-size:11px;line-height:1.5;">Cordialement,<br>L’équipe PassPermisFacile</td></tr>
    </table>
  </td></tr></table>
</body></html>
