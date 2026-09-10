<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use flight\Engine;

/**
 * MarketplaceAdminController - admin surface for the Marketplace.
 *
 * Connect/disconnect the Pubvana account, browse the store catalog and push
 * items to the account-bound cart, and manage purchases (verify, install,
 * reinstall-all, domain move).
 *
 * @package Pubvana\Plugins\Marketplace\Controllers
 */
class MarketplaceAdminController extends AdminController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana.marketplace');
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/marketplace'), '/');
    }

    public function index(): void
    {
        $svc = $this->app->marketplace();
        $this->render('pubvana/marketplace/admin/index', [
            'pageTitle'    => 'Marketplace',
            'connected'    => $svc->connected(),
            'accountEmail' => $svc->accountEmail(),
            'prefillEmail' => $this->currentUserEmail(),
            'categories'   => $svc->connected() ? $svc->categories() : [],
            'items'        => $svc->connected() ? $svc->items() : [],
            'adminBase'    => $this->adminBase(),
        ]);
    }

    public function connect(): void
    {
        $email = $this->currentUserEmail();
        if ($email === '') {
            $this->app->session()->flash('danger', 'No logged-in admin email is available to connect with.');
            $this->app->redirect($this->adminBase());
            return;
        }
        $password = (string) ($this->app->request()->data->password ?? '');
        $passwordConf = (string) ($this->app->request()->data->password_conf ?? '');
        $result = $this->app->marketplace()->connectAccount($email, $password, $passwordConf);
        if (!empty($result['ok'])) {
            $this->app->session()->flash('success', 'Connected to the Pubvana account. Browse the catalog below.');
        } else {
            $this->app->session()->flash('danger', $result['reason'] ?? 'Could not connect.');
        }
        $this->app->redirect($this->adminBase());
    }

    /**
     * The pubvanacms account email for the account connecting: the current
     * admin's email, so each admin owns their own store account.
     */
    private function currentUserEmail(): string
    {
        $user = $this->app->auth()->user();
        if ($user === null) {
            return '';
        }
        return (string) ($this->app->auth()->users()->getEmail($user) ?? '');
    }

    public function disconnect(): void
    {
        $this->app->marketplace()->disconnectAccount();
        $this->app->session()->flash('info', 'Disconnected from the Pubvana account.');
        $this->app->redirect($this->adminBase());
    }

    public function purchases(): void
    {
        $svc = $this->app->marketplace();
        $records = $svc->connected() ? $svc->localInstallRecords() : [];
        $this->render('pubvana/marketplace/admin/purchases', [
            'pageTitle'   => 'Purchases',
            'connected'   => $svc->connected(),
            'records'     => $records,
            'adminBase'   => $this->adminBase(),
        ]);
    }

    public function verify(): void
    {
        $result = $this->app->marketplace()->purchases();
        $this->app->session()->flash('success', 'Purchases verified against pubvanacms.com.');
        $this->app->redirect($this->adminBase() . '/purchases');
    }

    public function addToCart(): void
    {
        $productId = (int) ($this->app->request()->data->product_id ?? 0);
        $currency = (string) ($this->app->request()->data->currency ?? 'USD');
        $result = $this->app->marketplace()->addToCart($productId, $currency);
        $this->app->json($result);
    }

    public function install(): void
    {
        $productId = (int) ($this->app->request()->data->product_id ?? 0);
        $itemType = (string) ($this->app->request()->data->item_type ?? 'plugin');
        $record = $this->app->marketplace()->installRecordForProduct($productId);
        if ($record !== null && $this->app->marketplace()->needsDomainMove($productId)) {
            $this->app->session()->flash('warning', 'Your license is bound to another domain. Confirm the transfer in your email, then install again.');
            $this->app->marketplace()->requestDomainMove($productId);
            $this->app->redirect($this->adminBase() . '/purchases');
            return;
        }
        $result = $this->app->marketplace()->install($productId, $itemType);
        $this->app->session()->flash(!empty($result['ok']) ? 'success' : 'danger', $result['reason']);
        $this->app->redirect($this->adminBase() . '/purchases');
    }

    public function reinstallAll(): void
    {
        $svc = $this->app->marketplace();
        if (!$svc->connected()) {
            $this->app->session()->flash('danger', 'Connect a Pubvana account first.');
            $this->app->redirect($this->adminBase());
            return;
        }
        $result = $svc->reinstallAll();
        $failed = count($result['failed']);
        if ($failed > 0) {
            $this->app->session()->flash('warning', 'Reinstalled ' . (int) $result['ok'] . ', skipped ' . (int) $result['skipped'] . '. Some items failed.');
        } else {
            $this->app->session()->flash('success', 'Reinstalled ' . (int) $result['ok'] . ' items, skipped ' . (int) $result['skipped'] . '.');
        }
        $this->app->redirect($this->adminBase() . '/purchases');
    }

    public function cartOpen(): void
    {
        $this->app->redirect($this->app->marketplace()->checkoutUrl());
    }
}