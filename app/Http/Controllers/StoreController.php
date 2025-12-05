<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreController extends Controller
{
    /**
     * Show setup wizard
     */
    public function setupWizard()
    {
        $user = auth()->user();

        // Redirect if store already exists
        if ($user->store && $user->store->is_setup_complete) {
            return redirect()->route('dashboard');
        }

        // Create store if doesn't exist
        if (! $user->store) {
            Store::create([
                'user_id' => $user->id,
                'name' => $user->name."'s Store",
                'bricklink_store_name' => $user->name."'s Store",
                'is_active' => false,
                'is_setup_complete' => false,
            ]);
        }

        return view('store.setup-wizard', ['store' => $user->fresh()->store]);
    }

    /**
     * Complete setup wizard step
     */
    public function completeSetupStep(Request $request)
    {
        $user = auth()->user();
        $store = $user->store;

        Gate::authorize('update', $store);

        $step = $request->input('step');

        switch ($step) {
            case 'basic':
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'company_name' => 'required|string|max:255',
                    'owner_name' => 'required|string|max:255',
                    'street' => 'required|string|max:255',
                    'postal_code' => 'required|string|max:20',
                    'city' => 'required|string|max:255',
                    'country' => 'required|string|max:255',
                    'tax_number' => 'nullable|string|max:255',
                    'vat_id' => 'nullable|string|max:255',
                    'is_small_business' => 'boolean',
                ]);

                $store->update($validated);
                ActivityLogger::info('store.setup.basic', 'Basic store information updated', $store);

                return response()->json(['success' => true, 'next_step' => 'bank']);

            case 'bank':
                $validated = $request->validate([
                    'bank_name' => 'required|string|max:255',
                    'bank_account_holder' => 'required|string|max:255',
                    'iban' => 'required|string|max:34',
                    'bic' => 'required|string|max:11',
                ]);

                $store->update($validated);
                ActivityLogger::info('store.setup.bank', 'Bank information updated', $store);

                return response()->json(['success' => true, 'next_step' => 'bricklink']);

            case 'bricklink':
                $validated = $request->validate([
                    'bl_consumer_key' => 'required|string',
                    'bl_consumer_secret' => 'required|string',
                    'bl_token' => 'required|string',
                    'bl_token_secret' => 'required|string',
                ]);

                $store->update($validated);
                ActivityLogger::info('store.setup.bricklink', 'BrickLink credentials configured', $store);

                return response()->json(['success' => true, 'next_step' => 'smtp']);

            case 'smtp':
                $validated = $request->validate([
                    'smtp_host' => 'nullable|string|max:255',
                    'smtp_port' => 'nullable|integer|min:1|max:65535',
                    'smtp_username' => 'nullable|string|max:255',
                    'smtp_password' => 'nullable|string|max:255',
                    'smtp_encryption' => 'nullable|in:tls,ssl',
                    'smtp_from_address' => 'nullable|email|max:255',
                    'smtp_from_name' => 'nullable|string|max:255',
                ]);

                $store->update($validated);
                ActivityLogger::info('store.setup.smtp', 'SMTP configuration updated', $store);

                return response()->json(['success' => true, 'next_step' => 'nextcloud']);

            case 'nextcloud':
                $validated = $request->validate([
                    'nextcloud_url' => 'nullable|url|max:255',
                    'nextcloud_username' => 'nullable|string|max:255',
                    'nextcloud_password' => 'nullable|string|max:255',
                    'nextcloud_invoice_path' => 'nullable|string|max:255',
                ]);

                $store->update($validated);
                ActivityLogger::info('store.setup.nextcloud', 'Nextcloud configuration updated', $store);

                return response()->json(['success' => true, 'next_step' => 'complete']);

            case 'complete':
                $store->update([
                    'is_setup_complete' => true,
                    'is_active' => true,
                ]);

                ActivityLogger::info('store.setup.completed', 'Store setup completed', $store);

                return response()->json(['success' => true, 'redirect' => route('dashboard')]);

            default:
                return response()->json(['success' => false, 'message' => 'Invalid step'], 400);
        }
    }

    /**
     * Show store settings
     */
    public function settings()
    {
        $user = auth()->user();
        $store = $user->store;

        if (! $store) {
            return redirect()->route('store.setup-wizard');
        }

        Gate::authorize('view', $store);

        return view('store.settings', compact('store'));
    }

    /**
     * Update basic store settings
     */
    public function updateBasic(Request $request)
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'vat_id' => 'nullable|string|max:255',
            'is_small_business' => 'boolean',
            'invoice_number_format' => 'nullable|string|max:100',
        ]);

        $store->update($validated);
        ActivityLogger::info('store.settings.basic', 'Basic settings updated', $store);

        return redirect()->back()->with('success', 'Grundeinstellungen wurden aktualisiert');
    }

    /**
     * Update bank settings
     */
    public function updateBank(Request $request)
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_holder' => 'required|string|max:255',
            'iban' => 'required|string|max:34',
            'bic' => 'required|string|max:11',
        ]);

        $store->update($validated);
        ActivityLogger::info('store.settings.bank', 'Bank settings updated', $store);

        return redirect()->back()->with('success', 'Bankdaten wurden aktualisiert');
    }

    /**
     * Update BrickLink settings
     */
    public function updateBrickLink(Request $request)
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        $validated = $request->validate([
            'bl_consumer_key' => 'required|string',
            'bl_consumer_secret' => 'required|string',
            'bl_token' => 'required|string',
            'bl_token_secret' => 'required|string',
        ]);

        $store->update($validated);
        ActivityLogger::info('store.settings.bricklink', 'BrickLink credentials updated', $store);

        return redirect()->back()->with('success', 'BrickLink-Zugangsdaten wurden aktualisiert');
    }

    /**
     * Update SMTP settings
     */
    public function updateSmtp(Request $request)
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        $validated = $request->validate([
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
        ]);

        $store->update($validated);
        ActivityLogger::info('store.settings.smtp', 'SMTP settings updated', $store);

        return redirect()->back()->with('success', 'E-Mail-Einstellungen wurden aktualisiert');
    }

    /**
     * Update Nextcloud settings
     */
    public function updateNextcloud(Request $request)
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        $validated = $request->validate([
            'nextcloud_url' => 'nullable|url|max:255',
            'nextcloud_username' => 'nullable|string|max:255',
            'nextcloud_password' => 'nullable|string|max:255',
            'nextcloud_invoice_path' => 'nullable|string|max:255',
        ]);

        $store->update($validated);
        ActivityLogger::info('store.settings.nextcloud', 'Nextcloud settings updated', $store);

        return redirect()->back()->with('success', 'Nextcloud-Einstellungen wurden aktualisiert');
    }

    /**
     * Test Nextcloud connection
     */
    public function testNextcloud()
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        try {
            if (! $store->nextcloud_url || ! $store->nextcloud_username || ! $store->nextcloud_password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nextcloud-Zugangsdaten sind nicht vollständig konfiguriert.',
                ], 400);
            }

            $nextcloud = new \App\Services\NextcloudService($store);

            if (! $nextcloud->testConnection()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verbindung zu Nextcloud fehlgeschlagen. Bitte prüfen Sie die Einstellungen.',
                ], 500);
            }

            ActivityLogger::info('store.nextcloud.test', 'Nextcloud connection tested successfully', $store);

            return response()->json([
                'success' => true,
                'message' => 'Verbindung zu Nextcloud erfolgreich! ('.trim($store->nextcloud_url, '/').')',
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error('store.nextcloud.test_failed', 'Nextcloud test failed: '.$e->getMessage(), $store);

            return response()->json([
                'success' => false,
                'message' => 'Nextcloud-Test fehlgeschlagen: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test SMTP connection
     */
    public function testSmtp()
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        try {
            if (! $store->hasSmtpCredentials()) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMTP-Zugangsdaten sind nicht vollständig konfiguriert.',
                ], 400);
            }

            // Configure runtime SMTP mailer
            config([
                'mail.mailers.test_smtp' => [
                    'transport' => 'smtp',
                    'host' => $store->smtp_host,
                    'port' => $store->smtp_port,
                    'encryption' => $store->smtp_encryption ?? 'tls',
                    'username' => $store->smtp_username,
                    'password' => $store->smtp_password,
                    'timeout' => null,
                    'local_domain' => parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST),
                ],
            ]);

            // Send test email using the custom mailer
            \Mail::mailer('test_smtp')->raw(
                'Dies ist eine Test-E-Mail von BrickStore. Ihre SMTP-Konfiguration funktioniert korrekt!',
                function ($message) use ($store) {
                    $message->to($store->user->email)
                        ->from($store->smtp_from_address ?? $store->user->email, $store->smtp_from_name ?? $store->company_name)
                        ->subject('SMTP Test - BrickStore');
                }
            );

            ActivityLogger::info('store.smtp.test', 'SMTP connection tested successfully', $store);

            return response()->json([
                'success' => true,
                'message' => 'Test-E-Mail wurde erfolgreich versendet an '.$store->user->email,
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error('store.smtp.test_failed', 'SMTP test failed: '.$e->getMessage(), $store);

            return response()->json([
                'success' => false,
                'message' => 'SMTP-Test fehlgeschlagen: '.$e->getMessage(),
            ], 500);
        }
    }
}
