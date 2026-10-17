<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\UpdateSettingsRequest;
use App\Services\MailConfigService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    /** Plain (non-encrypted) setting keys editable on the form. */
    private const PLAIN = [
        'default_email_subject', 'default_email_body',
        'mail_host', 'mail_port', 'mail_username', 'mail_encryption',
        'mail_from_address', 'mail_from_name',
    ];

    public function __construct(private readonly SettingService $settings) {}

    /** Settings are per-organization; a SuperAdmin has none and manages organizations instead. */
    private function orgId(Request $request): int
    {
        $orgId = $request->user()->organization_id;
        abort_unless($orgId, 403, 'Settings are managed per organization.');

        return $orgId;
    }

    public function index(Request $request): mixed
    {
        $orgId = $this->orgId($request);
        $all = $this->settings->all($orgId);
        unset($all['mail_password']); // never expose the stored (encrypted) password

        return view('admin.settings.index', ['settings' => $all]);
    }

    public function update(UpdateSettingsRequest $request): mixed
    {
        $data = $request->validated();

        $pairs = [];
        foreach (self::PLAIN as $key) {
            if (array_key_exists($key, $data)) {
                $pairs[$key] = ['value' => $data[$key]];
            }
        }
        if (! empty($data['mail_password'])) {
            $pairs['mail_password'] = ['value' => $data['mail_password'], 'encrypt' => true];
        }

        $this->settings->setMany($this->orgId($request), $pairs);

        return $this->ok(null, 'Settings saved.');
    }

    public function testEmail(Request $request, MailConfigService $mail): mixed
    {
        $mail->applyForOrganization($this->orgId($request));

        try {
            Mail::raw('This is a test email from '.config('app.name').'.', function ($m) use ($request) {
                $m->to($request->user()->email)->subject(config('app.name').' — test email');
            });
        } catch (\Throwable $e) {
            return $this->fail('Send failed: '.$e->getMessage(), 422);
        }

        return $this->ok(null, 'Test email sent to '.$request->user()->email.'.');
    }
}
