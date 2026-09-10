<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Services;

use Pubvana\Plugins\Marketplace\Models\MarketplaceInstall;
use flight\Engine;

/**
 * MarketplaceService - the Marketplace facade, mapped as $app->marketplace().
 *
 * The Marketplace is the companion app for the Digital Store on
 * pubvanacms.com. It talks to the store over a server-to-server API using an
 * account token, never a hand-entered key:
 *
 *   1. The admin links this site to a Pubvana account using their admin
 *      email plus a password (create or sign-in on the store).
 *   2. The catalog is browsed here and items are pushed to the account-bound
 *      cart at the store.
 *   3. "Purchase on pubvanacms.com" opens the checkout in a new tab with the
 *      cart already populated.
 *   4. "Purchases" verifies ownership back at the store for this domain and
 *      lists every owned item with its license state, ready to install or
 *      reinstall.
 *
 * Self-verification (phone-home) runs on a ~2 week cadence and is disclosed
 * up front when the account is first connected.
 *
 * @package Pubvana\Plugins\Marketplace\Services
 */
class MarketplaceService
{
    /**
     * @param Engine<object>       $app
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected \PDO $pdo,
        protected Engine $app,
        protected array $config,
    ) {
    }

    // -----------------------------------------------------------------
    // Account / connection
    // -----------------------------------------------------------------

    /**
     * Whether an account token is configured for this site.
     */
    public function connected(): bool
    {
        return (string) ($this->app->settings()->get('Marketplace.account_token') ?? '') !== '';
    }

    /**
     * The connected account email, if known.
     */
    public function accountEmail(): string
    {
        return (string) ($this->app->settings()->get('Marketplace.account_email') ?? '');
    }

    /**
     * Bind this site to a Pubvana account by signing in with the store email
     * + password, or creating a new account (email activation) and returning
     * the token that links this site to the account.
     *
     * Returns a result array with 'ok' and 'reason'.
     *
     * @return array{ok: bool, reason?: string}
     */
    public function connectAccount(string $email, string $password, string $passwordConf): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'reason' => 'A valid email address is required.'];
        }
        if ($password === '') {
            return ['ok' => false, 'reason' => 'A password is required.'];
        }
        if ($passwordConf === '' || $passwordConf !== $password) {
            return ['ok' => false, 'reason' => 'The passwords do not match.'];
        }

        $body = $this->httpPostJson($this->apiUrl('auth/token'), [
            'email'         => $email,
            'password'      => $password,
            'password_conf' => $passwordConf,
        ]);
        $data = $this->decode($body);
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not be reached. Please try again.'];
        }
        if (!empty($data['activation_required'])) {
            return ['ok' => false, 'reason' => 'A new Pubvana account (' . $email . ') needs activation. Check your inbox for the activation email, then connect again.'];
        }
        if (empty($data['ok']) || empty($data['token'])) {
            return ['ok' => false, 'reason' => (string) ($data['reason'] ?? 'Could not connect to the Pubvana account. Please try again.')];
        }

        $this->app->settings()->set('Marketplace.account_token', (string) $data['token']);
        $this->app->settings()->set('Marketplace.account_email', $email);
        $this->app->settings()->set('Marketplace.connected_at', date('c'));
        $this->app->settings()->set('Marketplace.verify_disclosed_at', date('c'));

        return ['ok' => true];
    }

    /**
     * Disconnect the account token (does not revoke the store account).
     */
    public function disconnectAccount(): void
    {
        $this->app->settings()->set('Marketplace.account_token', '');
        $this->app->settings()->set('Marketplace.account_email', '');
    }

    // -----------------------------------------------------------------
    // Catalog
    // -----------------------------------------------------------------

    /**
     * Fetch categories with their products from the store, cached for 1 hour.
     *
     * @return array<int, array<string, mixed>>
     */
    public function categories(): array
    {
        if (!$this->withToken()) {
            return [];
        }
        $data = $this->decode($this->httpGet($this->apiUrl('categories')));
        if (!is_array($data) || empty($data['ok']) || !is_array($data['categories'])) {
            return [];
        }
        return $data['categories'];
    }

    /**
     * Fetch the marketplace-listed item catalog.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(string $currency = 'USD'): array
    {
        if (!$this->withToken()) {
            return [];
        }
        $url = $this->apiUrl('items') . '&currency=' . urlencode($currency);
        $data = $this->decode($this->httpGet($url));
        if (!is_array($data) || empty($data['ok']) || !is_array($data['items'])) {
            return [];
        }
        return $data['items'];
    }

    // -----------------------------------------------------------------
    // Cart
    // -----------------------------------------------------------------

    /**
     * Push a product into the account-bound cart at the store.
     *
     * @return array{ok: bool, reason?: string}
     */
    public function addToCart(int $productId, string $currency = 'USD'): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        $body = $this->httpPostJson($this->apiUrl('cart/add'), [
            'product_id' => $productId,
            'currency'   => $currency,
        ]);
        $data = $this->decode($body);
        if (!is_array($data)) {
            return ['ok' => false, 'reason' => 'The store could not update your cart.'];
        }
        return ['ok' => !empty($data['ok']), 'reason' => (string) ($data['reason'] ?? '')];
    }

    /**
     * The store URL that opens checkout for the account-bound cart.
     */
    public function checkoutUrl(): string
    {
        return rtrim((string) ($this->config['store_url'] ?? ''), '/') . '/checkout';
    }

    // -----------------------------------------------------------------
    // Purchases / verification
    // -----------------------------------------------------------------

    /**
     * Verify which owned products license this domain and reconcile the local
     * marketplace_installs against the store's answer.
     *
     * @return array<int, array<string, mixed>> Serialized purchase records.
     */
    public function purchases(): array
    {
        if (!$this->withToken()) {
            return [];
        }
        $domain = $this->siteDomain();
        $url = $this->apiUrl('purchases') . '&domain=' . urlencode($domain);
        $data = $this->decode($this->httpGet($url));
        if (!is_array($data) || empty($data['ok']) || !is_array($data['purchases'])) {
            return [];
        }

        $this->reconcileInstalls($data['purchases']);
        return $data['purchases'];
    }

    /**
     * Upsert local install records from a store purchases response.
     *
     * @param array<int, array<string, mixed>> $purchases
     */
    protected function reconcileInstalls(array $purchases): void
    {
        $model = new MarketplaceInstall($this->pdo);
        $now = date('Y-m-d H:i:s');
        $domain = $this->siteDomain();
        foreach ($purchases as $p) {
            $pid = (int) ($p['product_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $row = $model->findByProductId($pid);
            $data = [
                'product_name'     => (string) ($p['name'] ?? ''),
                'slug'             => (string) ($p['slug'] ?? ''),
                'item_type'        => in_array($p['item_type'] ?? '', ['plugin', 'theme', 'file'], true) ? (string) $p['item_type'] : 'plugin',
                'folder'           => (string) ($p['folder'] ?? ''),
                'installed_version'=> null,
                'license_key'      => (string) ($p['license_key'] ?? ''),
                'license_scope'    => in_array($p['scope'] ?? '', ['single_site', 'multi_site', 'none'], true) ? (string) $p['scope'] : 'single_site',
                'license_valid'    => !empty($p['licensed']) ? 1 : 0,
                'license_last_checked' => $now,
                'expires_at'       => ($p['expires'] ?? '') !== '' ? (string) $p['expires'] : null,
                'renews_at'        => ($p['renews'] ?? '') !== '' ? (string) $p['renews'] : null,
                'is_subscription'  => ($p['renews'] ?? '') !== '' ? 1 : 0,
                'registered_domain'=> $domain,
                'updated_at'       => $now,
            ];
            if ($row === null) {
                $data['store_product_id'] = $pid;
                $data['created_at'] = $now;
                $model = new MarketplaceInstall($this->pdo);
                foreach ($data as $k => $v) {
                    $model->$k = $v;
                }
                $model->insert();
            } else {
                foreach ($data as $k => $v) {
                    $row->$k = $v;
                }
                $row->save();
            }
        }
    }

    /**
     * Local install records, serialized for the admin Purchases view.
     *
     * @return array<int, array<string, mixed>>
     */
    public function localInstallRecords(): array
    {
        $rows = (new MarketplaceInstall($this->pdo))->allTracked();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'store_product_id'  => (int) $r->store_product_id,
                'product_name'      => (string) $r->product_name,
                'item_type'         => (string) $r->item_type,
                'folder'            => (string) ($r->folder ?? ''),
                'installed_version' => (string) ($r->installed_version ?? ''),
                'installed'         => $this->isInstalled((string) $r->item_type, (string) $r->folder),
                'license_valid'     => (int) $r->license_valid === 1,
                'scope'             => (string) $r->license_scope,
                'expires'           => (string) ($r->expires_at ?? ''),
                'renews'            => (string) ($r->renews_at ?? ''),
            ];
        }
        return $out;
    }

    public function installRecordForProduct(int $storeProductId): ?MarketplaceInstall
    {
        return (new MarketplaceInstall($this->pdo))->findByProductId($storeProductId);
    }

    // -----------------------------------------------------------------
    // Install
    // -----------------------------------------------------------------

    /**
     * Validate a purchase against the store for this domain and install it.
     *
     * @return array{ok: bool, reason: string}
     */
    public function install(int $storeProductId, string $itemType): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null) {
            return ['ok' => false, 'reason' => 'Unknown purchase. Verify your purchases first.'];
        }
        if ((string) $record->license_key === '') {
            return ['ok' => false, 'reason' => 'This item has no license to validate.'];
        }

        $body = $this->httpPostJson($this->apiUrl('license/validate'), [
            'license_key' => (string) $record->license_key,
            'domain'      => $this->siteDomain(),
        ]);
        $data = $this->decode($body);
        if (!is_array($data) || empty($data['ok']) || empty($data['download_url'])) {
            return ['ok' => false, 'reason' => is_array($data) ? (string) ($data['reason'] ?? 'Validation failed.') : 'Validation failed.'];
        }

        $folder = (string) ($record->folder ?? '');
        $installed = $this->installPackage((string) $data['download_url'], $itemType, $folder, storeProductId: $storeProductId);
        if (!$installed) {
            return ['ok' => false, 'reason' => 'The package could not be installed.'];
        }

        $record->installed_version = $this->installedVersion($itemType, $folder);
        $record->updated_at = date('Y-m-d H:i:s');
        $record->save();

        return ['ok' => true, 'reason' => 'Installed.'];
    }

    /**
     * Install or reinstall all owned and licensed items whose versions align
     * with the catalog (reinstall-all). Returns a per-item result summary.
     *
     * @return array<string, mixed>
     */
    public function reinstallAll(string $currency = 'USD'): array
    {
        $purchases = $this->purchases();
        $results = ['ok' => 0, 'skipped' => 0, 'failed' => []];
        foreach ($purchases as $p) {
            $pid = (int) ($p['product_id'] ?? 0);
            $type = (string) ($p['item_type'] ?? 'plugin');
            if ($pid <= 0 || $type === 'file' || empty($p['license_key'])) {
                $results['skipped']++;
                continue;
            }
            $record = $this->installRecordForProduct($pid);
            if ($record === null) {
                $results['skipped']++;
                continue;
            }
            $folder = (string) ($record->folder ?? '');
            if (!$this->isInstalled($type, $folder)) {
                continue;
            }
            $result = $this->install($pid, $type);
            if (!empty($result['ok'])) {
                $results['ok']++;
            } else {
                $results['failed'][] = ['product_id' => $pid, 'reason' => $result['reason']];
            }
        }
        return $results;
    }

    /**
     * Download and safely extract a store package zip into the install folder
     * (plugins/ or themes/), rejecting path-traversal entries.
     */
    protected function installPackage(string $downloadUrl, string $type, string $folder, int $storeProductId = 0): bool
    {
        if (!in_array($type, ['plugin', 'theme'], true)) {
            return false;
        }
        $host = strtolower((string) parse_url($downloadUrl, PHP_URL_HOST));
        if ($host === '' || ($host !== 'localhost' && !str_ends_with($host, '.pubvanacms.com') && !str_ends_with($host, '.pubvana.net') && !str_ends_with($host, '.test'))) {
            return false;
        }
        if ($folder !== '' && !preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $folder)) {
            return false;
        }

        $destRoot = PROJECT_ROOT . \DIRECTORY_SEPARATOR . ($type === 'theme' ? 'themes' : 'plugins');

        $tmpDir = PROJECT_ROOT . \DIRECTORY_SEPARATOR . 'writable' . \DIRECTORY_SEPARATOR . 'cache' . \DIRECTORY_SEPARATOR . 'marketplace';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
            return false;
        }
        $zipPath = $tmpDir . \DIRECTORY_SEPARATOR . ($folder !== '' ? $folder : 'pkg') . '-' . bin2hex(random_bytes(4)) . '.zip';

        $zipData = $this->httpGet($downloadUrl);
        if ($zipData === null) {
            return false;
        }
        if (file_put_contents($zipPath, $zipData) === false) {
            return false;
        }

        $archive = new \ZipArchive();
        if ($archive->open($zipPath) !== true) {
            @unlink($zipPath);
            return false;
        }

        if (!$this->zipEntriesAreSafe($archive)) {
            $archive->close();
            @unlink($zipPath);
            return false;
        }

        $extractPath = $tmpDir . \DIRECTORY_SEPARATOR . 'extract';
        if (!is_dir($extractPath) && !mkdir($extractPath, 0755, true) && !is_dir($extractPath)) {
            $archive->close();
            @unlink($zipPath);
            return false;
        }

        if (!$archive->extractTo($extractPath)) {
            $archive->close();
            @unlink($zipPath);
            $this->rmdir($extractPath);
            return false;
        }
        $archive->close();

        $source = $this->resolveZipRoot($extractPath);
        $destDir = $destRoot . \DIRECTORY_SEPARATOR . $folder;
        if ($folder === '') {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return false;
        }
        if (is_dir($destDir) && !$this->rmdir($destDir)) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return false;
        }
        if (!mkdir($destDir, 0755, true)) {
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return false;
        }
        if (!$this->copyTree($source, $destDir)) {
            $this->rmdir($destDir);
            $this->rmdir($extractPath);
            @unlink($zipPath);
            return false;
        }

        $this->rmdir($extractPath);
        @unlink($zipPath);

        return true;
    }

    protected function zipEntriesAreSafe(\ZipArchive $archive): bool
    {
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $name = (string) $archive->getNameIndex($i);
            if ($name === '') {
                continue;
            }
            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, "\0") || preg_match('#^[A-Za-z]:#', $name)) {
                return false;
            }
        }
        return true;
    }

    protected function resolveZipRoot(string $extractPath): string
    {
        $entries = array_values(array_filter(scandir($extractPath) ?: [], static fn(string $f): bool => !in_array($f, ['.', '..'], true)));
        if (count($entries) === 1 && is_dir($extractPath . \DIRECTORY_SEPARATOR . $entries[0])) {
            return $extractPath . \DIRECTORY_SEPARATOR . $entries[0];
        }
        return $extractPath;
    }

    protected function copyTree(string $from, string $to): bool
    {
        $items = scandir($from) ?: [];
        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }
            $src = $from . \DIRECTORY_SEPARATOR . $item;
            $dst = $to . \DIRECTORY_SEPARATOR . $item;
            if (is_dir($src)) {
                if (!mkdir($dst, 0755, true) || !$this->copyTree($src, $dst)) {
                    return false;
                }
            } else {
                if (!copy($src, $dst)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function dirList(string $path): array
    {
        $entries = scandir($path);
        return $entries === false ? [] : $entries;
    }

    protected function rmdir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return !is_file($dir) || @unlink($dir);
        }
        foreach ($this->dirList($dir) as $entry) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }
            $path = $dir . \DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path) && !$this->rmdir($path)) {
                return false;
            }
            if (is_file($path) && !@unlink($path)) {
                return false;
            }
        }
        return @rmdir($dir);
    }

    protected function isInstalled(string $itemType, string $folder): bool
    {
        if ($folder === '') {
            return false;
        }
        $root = PROJECT_ROOT . \DIRECTORY_SEPARATOR;
        return is_dir($root . ($itemType === 'theme' ? 'themes' : 'plugins') . \DIRECTORY_SEPARATOR . $folder);
    }

    protected function installedVersion(string $itemType, string $folder): ?string
    {
        $root = PROJECT_ROOT . \DIRECTORY_SEPARATOR;
        $infoFile = $root . ($itemType === 'theme' ? 'themes' : 'plugins') . \DIRECTORY_SEPARATOR . $folder . \DIRECTORY_SEPARATOR . 'pubvana.json';
        if (is_file($infoFile)) {
            $info = json_decode((string) file_get_contents($infoFile), true);
            $v = is_array($info) ? ($info['semver'] ?? null) : null;
            if (is_string($v)) {
                return $v;
            }
        }
        $infoFile = $root . ($itemType === 'theme' ? 'themes' : 'plugins') . \DIRECTORY_SEPARATOR . $folder . \DIRECTORY_SEPARATOR . ($itemType === 'theme' ? 'theme_info' : 'plugin_info') . '.json';
        if (is_file($infoFile)) {
            $info = json_decode((string) file_get_contents($infoFile), true);
            $v = is_array($info) ? ($info['version'] ?? null) : null;
            if (is_string($v)) {
                return $v;
            }
        }
        return null;
    }

    // -----------------------------------------------------------------
    // Domain move / transfer
    // -----------------------------------------------------------------

    /**
     * Whether a single-site license bound to a different domain should trigger
     * the domain-move prompt on install.
     */
    public function needsDomainMove(int $storeProductId): bool
    {
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null || (string) $record->license_scope !== 'single_site') {
            return false;
        }
        return (string) $record->registered_domain !== $this->siteDomain();
    }

    /**
     * Request a domain transfer (rebind) for a single-site license. The store
     * emails a confirmation link that finalizes the move.
     *
     * @return array{ok: bool, reason: string}
     */
    public function requestDomainMove(int $storeProductId): array
    {
        if (!$this->withToken()) {
            return ['ok' => false, 'reason' => 'Not connected to a Pubvana account.'];
        }
        $record = $this->installRecordForProduct($storeProductId);
        if ($record === null || (string) $record->license_key === '') {
            return ['ok' => false, 'reason' => 'No license to move.'];
        }
        $body = $this->httpPostJson($this->apiUrl('license/transfer-request'), [
            'license_key' => (string) $record->license_key,
            'new_domain'  => $this->siteDomain(),
        ]);
        $data = $this->decode($body);
        if (!is_array($data) || empty($data['ok'])) {
            return ['ok' => false, 'reason' => is_array($data) ? (string) ($data['reason'] ?? 'Transfer could not start.') : 'Transfer could not start.'];
        }
        return ['ok' => true, 'reason' => 'Check your email and confirm the transfer, then install again.'];
    }

    // -----------------------------------------------------------------
    // Cron / phone-home
    // -----------------------------------------------------------------

    /**
     * Verify purchases and licenses against the store. Called by the 24h cron
     * task; this method holds the cadence gate so the store is actually hit
     * every `verify_days` (default 14) rather than every cron tick.
     */
    public function verifyIfDue(): void
    {
        if (!$this->connected()) {
            return;
        }
        $verifyDays = max(1, (int) ($this->config['verify_days'] ?? 14));
        $last = (string) ($this->app->settings()->get('Marketplace.last_verify_at') ?? '');
        if ($last !== '' && strtotime($last) !== false) {
            $due = strtotime('+ ' . $verifyDays . ' days', (int) strtotime($last));
            if ($due !== false && time() < $due) {
                return;
            }
        }

        $this->purchases();
        $this->app->settings()->set('Marketplace.last_verify_at', date('c'));
    }

    // -----------------------------------------------------------------
    // HTTP helpers
    // -----------------------------------------------------------------

    protected function withToken(): bool
    {
        return $this->connected();
    }

    protected function apiUrl(string $path): string
    {
        $base = rtrim((string) ($this->config['store_url'] ?? ''), '/');
        return $base . '/api/store/' . $path . ($this->connected() ? '?token=' . urlencode((string) $this->app->settings()->get('Marketplace.account_token')) : '');
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function httpPostJson(string $url, array $payload): ?string
    {
        $data = json_encode($payload) ?: '{}';
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($this->connected()) {
            $headers[] = 'Authorization: Bearer ' . (string) $this->app->settings()->get('Marketplace.account_token');
        }

        $urlBase = parse_url($url, PHP_URL_SCHEME) . '://' . (parse_url($url, PHP_URL_HOST) ?? '');
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
        $query = (string) (parse_url($url, PHP_URL_QUERY) ?? '');
        $finalUrl = $urlBase . rtrim($path, '/') . ($query !== '' ? '?' . $query : '');
        $timeout = (int) ($this->config['api_timeout'] ?? 10);

        if (function_exists('curl_init')) {
            $handle = curl_init($finalUrl);
            if ($handle !== false) {
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $data,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_USERAGENT      => $this->userAgent(),
                ]);
                $body = curl_exec($handle);
                curl_close($handle);
                return is_string($body) && $body !== '' ? $body : null;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $data,
                'timeout'       => $timeout,
                'ignore_errors' => false,
                'user_agent'    => $this->userAgent(),
            ],
        ]);
        $body = @file_get_contents($finalUrl, false, $context);
        return is_string($body) && $body !== '' ? $body : null;
    }

    protected function httpGet(string $url): ?string
    {
        $timeout = (int) ($this->config['api_timeout'] ?? 10);
        $headers = $this->connected() ? ['Authorization: Bearer ' . (string) $this->app->settings()->get('Marketplace.account_token')] : [];

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                $curlHeaders = [];
                foreach ($headers as $h) {
                    $curlHeaders[] = $h;
                }
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS      => 3,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_USERAGENT      => $this->userAgent(),
                    CURLOPT_HTTPHEADER     => $curlHeaders,
                ]);
                $body = curl_exec($handle);
                curl_close($handle);
                return is_string($body) && $body !== '' ? $body : null;
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout'         => $timeout,
                'follow_location' => 1,
                'max_redirects'   => 3,
                'user_agent'      => $this->userAgent(),
                'header'          => implode("\r\n", $headers),
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        return is_string($body) && $body !== '' ? $body : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decode(?string $body): ?array
    {
        if ($body === null) {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    /**
     * @return non-empty-string
     */
    protected function userAgent(): string
    {
        return 'Pubvana-Marketplace/3.0';
    }

    protected function siteDomain(): string
    {
        $domain = (string) ($this->app->get('CMS.siteUrl') ?? $this->app->request()->host ?? '');
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', (string) $domain);
        $domain = preg_replace('#^www\.#', '', (string) $domain);
        return (string) preg_replace('#[/\s]#', '', (string) $domain);
    }
}
