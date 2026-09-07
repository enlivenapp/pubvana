<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; max-width: 480px; margin: 0 auto; padding: 24px; color: #1e293b;">
    <h2 style="font-size: 18px; margin: 0 0 16px;">Your sign-in link</h2>
    <p style="margin: 0 0 16px; line-height: 1.5;">
        Click the button below to sign in. The link is single-use and expires
        within the hour.
    </p>
    <p style="margin: 0 0 24px;">
        <a href="{{ magicLinkUrl }}"
           style="display: inline-block; padding: 10px 20px; background: #206bc4; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: 600;">
            Sign In
        </a>
    </p>
    <p style="margin: 0 0 16px; font-size: 13px; color: #64748b; line-height: 1.5;">
        If the button does not work, copy this address into your browser:<br>
        <a href="{{ magicLinkUrl }}">{{ magicLinkUrl }}</a>
    </p>
    <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8;">
        If you did not request this, you can safely ignore this email.
    </p>
    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 16px 0;">
    <p style="margin: 0; font-size: 11px; color: #94a3b8; line-height: 1.6;">
        Requested {{ date }} from IP {{ ipAddress }}<br>
        {{ userAgent }}
    </p>
</div>
