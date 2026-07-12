{{-- Correo de recuperación de contraseña — SGT San José de Chimbo.
     HTML de correo: layout con tablas + estilos en línea (máxima compatibilidad
     con Gmail, Outlook, Apple Mail, etc.). Paleta de marca: verde #00913f + oro. --}}
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Restablece tu contraseña</title>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#eef0f2; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">

    {{-- Preheader: texto de vista previa en la bandeja de entrada (oculto en el cuerpo) --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all; font-size:1px; line-height:1px; color:#eef0f2;">
        Restablece la contraseña de tu cuenta del SGT San José de Chimbo. El enlace vence en {{ $expira }} minutos.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef0f2;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                {{-- Tarjeta principal (600px) --}}
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 6px 24px rgba(2,44,19,0.10);">

                    {{-- Encabezado de marca --}}
                    <tr>
                        <td style="background-color:#00913f; padding:32px 40px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:20px; font-weight:bold; letter-spacing:0.5px;">
                                        SGT · San José de Chimbo
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family:Arial,Helvetica,sans-serif; color:#d6f0df; font-size:13px; padding-top:4px;">
                                        Sistema de Gestión Turística
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Línea de acento en oro --}}
                    <tr>
                        <td style="height:4px; line-height:4px; font-size:0; background-color:#d99a16;">&nbsp;</td>
                    </tr>

                    {{-- Cuerpo --}}
                    <tr>
                        <td style="padding:36px 40px 8px; font-family:Arial,Helvetica,sans-serif; color:#334155;">
                            <h1 style="margin:0 0 16px; font-size:22px; line-height:1.3; color:#0f172a; font-weight:bold;">
                                Restablece tu contraseña
                            </h1>
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.65; color:#475569;">
                                Hola{{ $nombre ? ', ' . e($nombre) : '' }}:
                            </p>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.65; color:#475569;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta en el
                                <strong style="color:#0f172a;">Sistema de Gestión Turística de San José de Chimbo</strong>.
                                Para continuar, haz clic en el siguiente botón:
                            </p>

                            {{-- Botón "bulletproof" (compatible con Outlook) --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding:8px 0 28px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" bgcolor="#00913f" style="border-radius:10px;">
                                                    <a href="{{ $url }}" target="_blank"
                                                       style="display:inline-block; padding:14px 34px; font-family:Arial,Helvetica,sans-serif; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:10px; background-color:#00913f;">
                                                        Restablecer mi contraseña
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Aviso de expiración --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
                                <tr>
                                    <td style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px 18px; font-family:Arial,Helvetica,sans-serif; font-size:13px; line-height:1.5; color:#475569;">
                                        ⏱ Por seguridad, este enlace vence en <strong style="color:#0f172a;">{{ $expira }} minutos</strong> y solo puede usarse una vez.
                                    </td>
                                </tr>
                            </table>

                            {{-- Enlace de respaldo (si el botón no funciona) --}}
                            <p style="margin:0 0 6px; font-size:13px; line-height:1.6; color:#64748b;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>
                            <p style="margin:0 0 24px; font-size:12px; line-height:1.5; word-break:break-all;">
                                <a href="{{ $url }}" target="_blank" style="color:#00913f; text-decoration:underline;">{{ $url }}</a>
                            </p>

                            <hr style="border:none; border-top:1px solid #e2e8f0; margin:8px 0 20px;">

                            <p style="margin:0 0 28px; font-size:13px; line-height:1.6; color:#64748b;">
                                Si no solicitaste este cambio, puedes ignorar este correo con tranquilidad;
                                tu contraseña actual seguirá siendo la misma y tu cuenta permanece segura.
                            </p>
                        </td>
                    </tr>

                    {{-- Pie --}}
                    <tr>
                        <td style="background-color:#f1f5f4; padding:22px 40px; font-family:Arial,Helvetica,sans-serif; border-top:1px solid #e2e8f0;">
                            <p style="margin:0 0 4px; font-size:13px; font-weight:bold; color:#095c28;">
                                SGT — Municipio de San José de Chimbo
                            </p>
                            <p style="margin:0; font-size:12px; line-height:1.5; color:#94a3b8;">
                                Este es un correo automático, por favor no respondas a este mensaje.<br>
                                &copy; {{ date('Y') }} GAD Municipal de San José de Chimbo. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
