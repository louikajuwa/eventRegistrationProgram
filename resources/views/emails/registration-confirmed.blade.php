<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f6f4f0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f4f0;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#ffffff;border:1px solid #e7e1d7;border-radius:14px;overflow:hidden;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">

                    {{-- Ticket banner --}}
                    <tr>
                        <td style="background:#d9482a;background:linear-gradient(120deg,#d9482a 0%,#b93a1e 100%);padding:30px 32px 26px;">
                            <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#ffffff;opacity:.82;font-weight:600;">You're registered</div>
                            <div style="font-size:26px;line-height:1.2;color:#ffffff;font-weight:700;margin-top:8px;">{{ $vars['event_title'] }}</div>
                            <div style="margin-top:14px;font-size:14px;color:#ffffff;opacity:.95;">
                                @if (! empty($vars['event_date']) && $vars['event_date'] !== 'TBD')
                                    <span style="display:inline-block;margin-right:18px;">&#128197;&nbsp; {{ $vars['event_date'] }}</span>
                                @endif
                                @if (! empty($vars['event_location']) && $vars['event_location'] !== 'TBD')
                                    <span style="display:inline-block;">&#128205;&nbsp; {{ $vars['event_location'] }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- Perforated divider --}}
                    <tr>
                        <td style="padding:0;line-height:0;font-size:0;">
                            <div style="border-top:2px dashed #e7e1d7;"></div>
                        </td>
                    </tr>

                    {{-- Body (organizer's rendered plain-text message) --}}
                    <tr>
                        <td style="padding:26px 32px 8px;font-size:15px;line-height:1.65;color:#3c3833;">
                            {!! nl2br(e($body)) !!}
                        </td>
                    </tr>

                    {{-- Reference box --}}
                    <tr>
                        <td style="padding:16px 32px 4px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:#f4f0ea;border:1px dashed #d8d1c4;border-radius:9px;padding:14px 16px;text-align:center;">
                                        <div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#8a857d;font-weight:600;">Your reference</div>
                                        <div style="margin-top:5px;font-family:'Cascadia Code',Consolas,'Courier New',monospace;font-size:16px;color:#1b1a1f;font-weight:700;letter-spacing:1px;">{{ $vars['reference'] }}</div>
                                        <div style="margin-top:4px;font-size:12px;color:#8a857d;">Please have this ready at check-in.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:22px 32px 28px;">
                            <div style="border-top:1px solid #e7e1d7;padding-top:16px;font-size:12px;color:#8a857d;text-align:center;">
                                This confirmation was sent to {{ $vars['email'] }}.<br>
                                Powered by <span style="color:#5f5b55;font-weight:600;">Event Registration</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
