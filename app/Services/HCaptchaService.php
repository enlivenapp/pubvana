<?php

namespace App\Services;

class HCaptchaService
{
    public function verify(string $token): bool
    {
        if (ENVIRONMENT === 'testing' || ! getenv('HCAPTCHA_SECRET_KEY')) {
            return true; // skip in dev/test if no key configured
        }
        $client   = \Config\Services::curlrequest();
        $response = $client->post('https://hcaptcha.com/siteverify', [
            'form_params' => [
                'secret'   => getenv('HCAPTCHA_SECRET_KEY'),
                'response' => $token,
            ],
        ]);
        $body = json_decode($response->getBody(), true);
        return $body['success'] ?? false;
    }
}
