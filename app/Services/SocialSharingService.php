<?php

namespace App\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use Config\Social as SocialConfig;

class SocialSharingService
{
    protected SocialConfig $config;

    public function __construct()
    {
        $this->config = new SocialConfig();
    }

    /**
     * Share a post to all configured social networks.
     */
    public function share(object $post): void
    {
        if (empty($post->share_on_publish)) {
            return;
        }

        $url   = post_url($post->slug);
        $title = $post->title;

        $this->shareToTwitter($title, $url);
        $this->shareToFacebook($title, $url);
    }

    public function shareToTwitter(string $title, string $url): bool
    {
        $key    = $this->config->twitterApiKey;
        $secret = $this->config->twitterApiSecret;
        $token  = $this->config->twitterAccessToken;
        $tsecret= $this->config->twitterAccessSecret;

        if (! $key || ! $secret || ! $token || ! $tsecret) {
            log_message('info', 'SocialSharingService: Twitter credentials not configured, skipping.');
            return false;
        }

        try {
            $connection = new TwitterOAuth($key, $secret, $token, $tsecret);
            $connection->setApiVersion('2');
            $text = mb_substr($title, 0, 230) . ' ' . $url;
            $connection->post('tweets', ['text' => $text], true);
            log_message('info', 'SocialSharingService: Tweeted "' . $title . '"');
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'SocialSharingService Twitter error: ' . $e->getMessage());
            return false;
        }
    }

    public function shareToFacebook(string $title, string $url): bool
    {
        $pageId    = env('sharing.facebook.pageId', '');
        $pageToken = env('sharing.facebook.pageToken', '');

        if (! $pageId || ! $pageToken) {
            log_message('info', 'SocialSharingService: Facebook credentials not configured, skipping.');
            return false;
        }

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 10]);
            $endpoint = 'https://graph.facebook.com/v18.0/' . $pageId . '/feed';
            $response = $client->post($endpoint, [
                'form_params' => [
                    'message'      => $title,
                    'link'         => $url,
                    'access_token' => $pageToken,
                ],
                'http_errors' => false,
            ]);

            $body = json_decode($response->getBody(), true);
            if (! empty($body['id'])) {
                log_message('info', 'SocialSharingService: Facebook post created: ' . $body['id']);
                return true;
            }

            log_message('warning', 'SocialSharingService Facebook response: ' . $response->getBody());
            return false;
        } catch (\Throwable $e) {
            log_message('error', 'SocialSharingService Facebook error: ' . $e->getMessage());
            return false;
        }
    }
}
