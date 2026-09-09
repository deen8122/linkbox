<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Код для входа в LinkBox</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="360" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; padding:30px; box-shadow:0 10px 40px rgba(0,0,0,.08);">
                    <tr>
                        <td style="text-align:center;">
                            <h1 style="margin:0 0 10px; font-size:22px;">LinkBox</h1>
                            <p style="margin:0 0 20px; color:#666; font-size:14px;">
                                Ваш код для входа
                            </p>
                            <div style="font-size:32px; font-weight:bold; letter-spacing:6px; color:#222; margin-bottom:20px;">
                                {{ $code }}
                            </div>
                            <p style="margin:0; color:#999; font-size:12px;">
                                Код действителен 10 минут. Если вы не запрашивали вход, просто проигнорируйте это письмо.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
