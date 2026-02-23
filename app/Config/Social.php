<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Social extends BaseConfig
{
    /**
     * Google OAuth2 credentials.
     * Set via .env: oauth.google.clientId / oauth.google.clientSecret
     */
    public string $googleClientId     = '';
    public string $googleClientSecret = '';

    /**
     * Facebook OAuth2 credentials.
     * Set via .env: oauth.facebook.clientId / oauth.facebook.clientSecret
     */
    public string $facebookClientId     = '';
    public string $facebookClientSecret = '';

    /**
     * Twitter / X OAuth 1.0a app credentials for twitteroauth (posting).
     * Set via .env: oauth.twitter.*
     */
    public string $twitterApiKey       = '';
    public string $twitterApiSecret    = '';
    public string $twitterAccessToken  = '';
    public string $twitterAccessSecret = '';

    public function __construct()
    {
        parent::__construct();

        // Allow .env overrides
        $this->googleClientId     = env('oauth.google.clientId',         $this->googleClientId);
        $this->googleClientSecret = env('oauth.google.clientSecret',     $this->googleClientSecret);
        $this->facebookClientId   = env('oauth.facebook.clientId',       $this->facebookClientId);
        $this->facebookClientSecret = env('oauth.facebook.clientSecret', $this->facebookClientSecret);
        $this->twitterApiKey      = env('oauth.twitter.apiKey',          $this->twitterApiKey);
        $this->twitterApiSecret   = env('oauth.twitter.apiSecret',       $this->twitterApiSecret);
        $this->twitterAccessToken = env('oauth.twitter.accessToken',     $this->twitterAccessToken);
        $this->twitterAccessSecret= env('oauth.twitter.accessSecret',    $this->twitterAccessSecret);
    }
}
