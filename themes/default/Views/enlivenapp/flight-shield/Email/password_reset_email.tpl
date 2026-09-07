<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; max-width: 480px; margin: 0 auto; padding: 24px; color: #1e293b;">
    <h2 style="font-size: 18px; margin: 0 0 16px;">Password reset requested</h2>
    <p style="margin: 0 0 16px; line-height: 1.5;">
        A password reset was requested for your account at <strong>{{ siteName }}</strong>.
        Click the button below to set a new password. The link expires in one hour.
    </p>
    <p style="margin: 0 0 24px;">
        <a href="{{ resetUrl }}"
           style="display: inline-block; padding: 10px 20px; background: #206bc4; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: 600;">
            Set a New Password
        </a>
    </p>
    <p style="margin: 0 0 16px; font-size: 13px; color: #64748b; line-height: 1.5;">
        If the button does not work, copy this address into your browser:<br>
        <a href="{{ resetUrl }}">{{ resetUrl }}</a>
    </p>
    <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8;">
        If you did not request this, you can safely ignore this email; your password stays unchanged.
    </p>
    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 16px 0;">
    <p style="margin: 0; font-size: 11px; color: #94a3b8; line-height: 1.6;">
        Requested {{ date }} from IP {{ ipAddress }}<br>
        {{ userAgent }}
    </p>
</div>
