<div class="paywall-overlay mt-4">
    <div class="paywall-blur"></div>
    <div class="paywall-cta text-center p-5">
        <i class="fas fa-lock fa-2x text-warning mb-3"></i>
        <h4 class="font-weight-bold mb-2">Members Only</h4>
        <p class="text-muted mb-4">This post is available to registered members. Sign in or create a free account to continue reading.</p>
        <a href="<?= base_url('login?redirect=' . urlencode(current_url())) ?>" class="btn btn-primary mr-2">
            <i class="fas fa-sign-in-alt mr-1"></i> Sign In
        </a>
        <a href="<?= base_url('register') ?>" class="btn btn-outline-secondary">
            Create Account
        </a>
    </div>
</div>
<style>
.paywall-overlay { position: relative; }
.paywall-blur {
    height: 120px;
    background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, rgba(255,255,255,0.95) 80%, #fff 100%);
    margin-bottom: -4px;
}
.paywall-cta {
    background: #fff;
    border: 1px solid #e3e6f0;
    border-radius: .5rem;
    box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.1);
}
</style>
