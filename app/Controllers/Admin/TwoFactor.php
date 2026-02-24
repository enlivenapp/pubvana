<?php

namespace App\Controllers\Admin;

use OTPHP\TOTP;

class TwoFactor extends BaseAdminController
{
    /**
     * Show QR code setup page for the current user.
     */
    public function setup(): string
    {
        $userId = auth()->id();
        $user   = auth()->user();

        // Generate a fresh secret each time setup is loaded
        $totp = TOTP::generate();
        $totp->setLabel($user->email ?? ($user->username ?? 'user'));
        $totp->setIssuer(setting('App.siteName') ?? 'Pubvana');

        $secret = $totp->getSecret();
        $uri    = $totp->getProvisioningUri();

        // Store temporarily in session until confirmed
        session()->set('totp_temp_secret', $secret);

        return $this->adminView('twofactor/setup', array_merge(
            $this->baseData('2FA Setup', 'users'),
            [
                'provisioning_uri' => $uri,
                'secret'           => $secret,
                'user_id'          => $userId,
            ]
        ));
    }

    /**
     * Validate the first code and save the secret.
     */
    public function confirm()
    {
        $userId = auth()->id();
        $secret = session()->get('totp_temp_secret');

        if (! $secret) {
            return redirect()->to('/admin/users/2fa/setup')
                ->with('error', 'Setup session expired — please start again.');
        }

        $code = trim($this->request->getPost('totp_code') ?? '');
        $totp = TOTP::createFromSecret($secret);

        if (! $totp->verify($code, null, 1)) {
            return redirect()->to('/admin/users/2fa/setup')
                ->with('error', 'Invalid code — please scan the QR code and try once more.');
        }

        db_connect()->table('users')
            ->where('id', $userId)
            ->update(['totp_secret' => $secret, 'totp_enabled' => 1]);

        session()->remove('totp_temp_secret');
        // Mark as verified so the filter doesn't immediately challenge the user
        session()->set('totp_2fa_verified', true);

        return redirect()->to('/admin/users/' . $userId . '/profile')
            ->with('success', 'Two-factor authentication enabled.');
    }

    /**
     * Disable TOTP after verifying the current code.
     */
    public function disable()
    {
        $userId = auth()->id();

        $row = db_connect()->table('users')
            ->select('totp_secret, totp_enabled')
            ->where('id', $userId)
            ->get()->getRowObject();

        if (! $row || ! $row->totp_enabled) {
            return redirect()->to('/admin/users/' . $userId . '/profile')
                ->with('error', '2FA is not currently enabled.');
        }

        $code = trim($this->request->getPost('totp_code') ?? '');
        $totp = TOTP::createFromSecret($row->totp_secret);

        if (! $totp->verify($code, null, 1)) {
            return redirect()->to('/admin/users/' . $userId . '/profile')
                ->with('error', 'Invalid code — 2FA was not disabled.');
        }

        db_connect()->table('users')
            ->where('id', $userId)
            ->update(['totp_secret' => null, 'totp_enabled' => 0]);

        session()->remove('totp_2fa_verified');

        return redirect()->to('/admin/users/' . $userId . '/profile')
            ->with('success', 'Two-factor authentication disabled.');
    }
}
