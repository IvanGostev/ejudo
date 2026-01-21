<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Код подтверждения eJydo</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8f9fa;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table width="600" border="0" cellspacing="0" cellpadding="0"
                    style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #212529; padding: 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: bold;">eJydo</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px; text-align: center;">
                            <h2 style="color: #333333; margin-top: 0; margin-bottom: 20px;">Вход в систему</h2>
                            <p style="color: #666666; font-size: 16px; line-height: 1.5; margin-bottom: 30px;">
                                Вы запросили код для входа в сервис <strong>eJydo</strong>.<br>
                                Используйте код ниже для завершения авторизации.
                            </p>

                            <div
                                style="background-color: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; display: inline-block; margin-bottom: 30px;">
                                <span
                                    style="color: #198754; font-size: 32px; font-weight: bold; letter-spacing: 5px;">{{ $code }}</span>
                            </div>

                            <p style="color: #999999; font-size: 14px; margin-bottom: 0;">
                                Если вы не запрашивали этот код, просто проигнорируйте письмо.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="background-color: #f8f9fa; padding: 20px; border-top: 1px solid #dee2e6;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} eJydo. Все права защищены.<br>
                                <a href="https://ejydo.ru" style="color: #198754; text-decoration: none;">ejydo.ru</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>