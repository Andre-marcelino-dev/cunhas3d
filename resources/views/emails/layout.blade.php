{{-- Layout simples para e-mail: estilos inline, funciona no Outlook, Gmail e celular --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
            <tr><td style="background:#0a0a12;padding:20px 28px;">
                <span style="font-size:20px;font-weight:bold;color:#ffffff;">Cunhas <span style="color:#22d3ee;">3D</span></span>
            </td></tr>
            <tr><td style="padding:28px;font-size:15px;line-height:1.55;">
                @yield('content')
            </td></tr>
            <tr><td style="padding:18px 28px;background:#f9fafb;font-size:12px;color:#6b7280;">
                @yield('footer')
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
