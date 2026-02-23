<?php

namespace App\Controllers;

use Config\Social as SocialConfig;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;

class SocialAuth extends BaseController
{
    protected array $supportedProviders = ['google', 'facebook'];

    public function redirect(string $provider)
    {
        $provider = strtolower($provider);
        if (! in_array($provider, $this->supportedProviders, true)) {
            return redirect()->to('/login')->with('error', 'Unsupported OAuth provider.');
        }

        $oauthProvider = $this->makeProvider($provider);
        if (! $oauthProvider) {
            return redirect()->to('/login')->with('error', ucfirst($provider) . ' OAuth is not configured.');
        }

        $options = [];
        if ($provider === 'google') {
            $options['scope'] = ['email', 'profile'];
        } elseif ($provider === 'facebook') {
            $options['scope'] = ['email'];
        }

        $authUrl = $oauthProvider->getAuthorizationUrl($options);
        session()->set('oauth2state_' . $provider, $oauthProvider->getState());

        return redirect()->to($authUrl);
    }

    public function callback(string $provider)
    {
        $provider = strtolower($provider);
        if (! in_array($provider, $this->supportedProviders, true)) {
            return redirect()->to('/login')->with('error', 'Unsupported OAuth provider.');
        }

        $storedState = session()->get('oauth2state_' . $provider);
        $state       = $this->request->getGet('state');

        if (! $storedState || $state !== $storedState) {
            session()->remove('oauth2state_' . $provider);
            return redirect()->to('/login')->with('error', 'Invalid OAuth state. Please try again.');
        }
        session()->remove('oauth2state_' . $provider);

        $code = $this->request->getGet('code');
        if (! $code) {
            return redirect()->to('/login')->with('error', 'No authorization code received.');
        }

        $oauthProvider = $this->makeProvider($provider);
        if (! $oauthProvider) {
            return redirect()->to('/login')->with('error', ucfirst($provider) . ' OAuth is not configured.');
        }

        try {
            $token        = $oauthProvider->getAccessToken('authorization_code', ['code' => $code]);
            $resourceOwner = $oauthProvider->getResourceOwner($token);
        } catch (\Throwable $e) {
            log_message('error', 'SocialAuth callback error: ' . $e->getMessage());
            return redirect()->to('/login')->with('error', 'OAuth authentication failed. Please try again.');
        }

        $email          = $resourceOwner->getEmail();
        $providerUserId = (string) $resourceOwner->getId();
        $identityType   = 'oauth_' . $provider;

        if (! $email) {
            return redirect()->to('/login')->with('error', 'Could not retrieve email from ' . ucfirst($provider) . '.');
        }

        $db = db_connect();

        // Check for existing OAuth identity
        $identity = $db->table('auth_identities')
            ->where('type', $identityType)
            ->where('name', $providerUserId)
            ->get()->getRowObject();

        if ($identity) {
            // Log them in via Shield
            $userModel = new \CodeIgniter\Shield\Models\UserModel();
            $user      = $userModel->findById($identity->user_id);
            if ($user) {
                auth()->login($user);
                return redirect()->to('/')->with('success', 'Welcome back!');
            }
        }

        // Check if a user with this email already exists (email_password identity)
        $emailIdentity = $db->table('auth_identities')
            ->where('type', 'email_password')
            ->where('secret', $email)
            ->get()->getRowObject();

        if ($emailIdentity) {
            // Link OAuth identity to existing account
            $userId = $emailIdentity->user_id;
        } else {
            // Create new Shield user
            $username = $this->uniqueUsername($email);
            $userId   = $db->table('users')->insert([
                'username'   => $username,
                'active'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], true);

            $db->table('auth_identities')->insert([
                'user_id'    => $userId,
                'type'       => 'email_password',
                'name'       => $email,
                'secret'     => $email,
                'secret2'    => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->table('auth_groups_users')->insert([
                'user_id'    => $userId,
                'group'      => 'subscriber',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Store OAuth identity
        $db->table('auth_identities')->insert([
            'user_id'    => $userId,
            'type'       => $identityType,
            'name'       => $providerUserId,
            'secret'     => $providerUserId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $userModel = new \CodeIgniter\Shield\Models\UserModel();
        $user      = $userModel->findById($userId);
        auth()->login($user);

        return redirect()->to('/')->with('success', 'Logged in via ' . ucfirst($provider) . '.');
    }

    protected function makeProvider(string $provider): ?object
    {
        $config      = new SocialConfig();
        $baseUrl     = rtrim(config('App')->baseURL, '/');
        $callbackUrl = $baseUrl . '/auth/social/' . $provider . '/callback';

        return match ($provider) {
            'google' => ($config->googleClientId && $config->googleClientSecret)
                ? new Google([
                    'clientId'     => $config->googleClientId,
                    'clientSecret' => $config->googleClientSecret,
                    'redirectUri'  => $callbackUrl,
                ])
                : null,

            'facebook' => ($config->facebookClientId && $config->facebookClientSecret)
                ? new Facebook([
                    'clientId'        => $config->facebookClientId,
                    'clientSecret'    => $config->facebookClientSecret,
                    'redirectUri'     => $callbackUrl,
                    'graphApiVersion' => 'v18.0',
                ])
                : null,

            default => null,
        };
    }

    protected function uniqueUsername(string $email): string
    {
        $base = strtolower(explode('@', $email)[0]);
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'user';
        $db   = db_connect();
        $name = $base;
        $i    = 2;
        while ($db->table('users')->where('username', $name)->countAllResults() > 0) {
            $name = $base . $i++;
        }
        return $name;
    }
}
