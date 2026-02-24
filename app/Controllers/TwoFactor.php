<?php

namespace App\Controllers;

use OTPHP\TOTP;

/**
 * Handles TOTP verification at login time.
 * User is already authenticated by Shield; this step gates admin access
 * until the TOTP code is entered.
 */
class TwoFactor extends BaseController
{
    public function verify()
    {
        if (! auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        // If already verified this session, go straight to admin
        if (session()->get('totp_2fa_verified')) {
            return redirect()->to('/admin');
        }

        $row = db_connect()->table('users')
            ->select('totp_secret, totp_enabled')
            ->where('id', auth()->id())
            ->get()->getRowObject();

        // No TOTP set up — mark verified and continue
        if (! $row || ! $row->totp_enabled || empty($row->totp_secret)) {
            session()->set('totp_2fa_verified', true);
            return redirect()->to(session()->get('totp_redirect_url') ?? '/admin');
        }

        if ($this->request->getMethod() === 'GET') {
            return view('auth/totp_verify');
        }

        // POST — validate the code
        $code = trim($this->request->getPost('totp_code') ?? '');
        $totp = TOTP::createFromSecret($row->totp_secret);

        if ($totp->verify($code, null, 1)) {
            session()->set('totp_2fa_verified', true);
            $redirect = session()->get('totp_redirect_url') ?? '/admin';
            session()->remove('totp_redirect_url');
            return redirect()->to($redirect);
        }

        return view('auth/totp_verify', ['error' => 'Invalid code — please try again.']);
    }
}
