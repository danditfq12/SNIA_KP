<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * CSRF Protection Method
     * --------------------------------------------------------------------------
     *
     * Protection Method for Cross Site Request Forgery protection.
     *
     * @var string 'cookie' or 'session'
     */
    public string $csrfProtection = 'session'; // Changed to session for better reliability

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Randomization
     * --------------------------------------------------------------------------
     *
     * Randomize the CSRF Token for added security.
     */
    public bool $tokenRandomize = true; // Changed to true for better security

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Name
     * --------------------------------------------------------------------------
     *
     * Token name for Cross Site Request Forgery protection.
     */
    public string $tokenName = 'csrf_token_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Header Name
     * --------------------------------------------------------------------------
     *
     * Header name for Cross Site Request Forgery protection.
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * --------------------------------------------------------------------------
     * CSRF Cookie Name
     * --------------------------------------------------------------------------
     *
     * Cookie name for Cross Site Request Forgery protection.
     */
    public string $cookieName = 'csrf_cookie_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Expires
     * --------------------------------------------------------------------------
     *
     * Expiration time for Cross Site Request Forgery protection cookie.
     *
     * Defaults to two hours (in seconds).
     */
    public int $expires = 14400; // Increased to 4 hours for longer forms

    /**
     * --------------------------------------------------------------------------
     * CSRF Regenerate
     * --------------------------------------------------------------------------
     *
     * Regenerate CSRF Token on every submission.
     */
    public bool $regenerate = false; // Changed to false to prevent token mismatch on forms

    /**
     * --------------------------------------------------------------------------
     * CSRF Redirect
     * --------------------------------------------------------------------------
     *
     * Redirect to previous page with error on failure.
     *
     * @see https://codeigniter4.github.io/userguide/libraries/security.html#redirection-on-failure
     */
    public bool $redirect = false; // Set to false to prevent automatic redirects on CSRF failure

    /**
     * --------------------------------------------------------------------------
     * CSRF Excluded URIs
     * --------------------------------------------------------------------------
     *
     * List of URIs to exclude from CSRF protection.
     * Useful for webhook endpoints and API routes.
     */
    public array $excludeURIs = [
        // Webhook endpoints
        'webhook/*',
        'webhook/midtrans/*',
        'webhook/midtrans/handle',
        'webhook/midtrans/test',
        'webhook/midtrans/simulate',
        
        // API endpoints
        'api/*',
        
        // Other external endpoints
        'cron/*',
        'cli/*',
    ];

    /**
     * --------------------------------------------------------------------------
     * Content Security Policy
     * --------------------------------------------------------------------------
     *
     * Enables the Response's Content Security Policy to restrict the sources of
     * content that are allowed to be loaded on your site. For security reasons
     * you should enable this for any production environment.
     */
    public ?array $csp = null;

    /**
     * --------------------------------------------------------------------------
     * CSP Report Only
     * --------------------------------------------------------------------------
     *
     * Specifies that the CSP should report violations but not enforce them.
     * Useful for testing CSP policies before enforcing them.
     */
    public bool $cspReportOnly = false;

    /**
     * --------------------------------------------------------------------------
     * CSP Auto Nonce
     * --------------------------------------------------------------------------
     *
     * When enabled, nonces will be automatically generated for inline styles
     * and scripts. You can retrieve the nonce value using the csp_nonce() function.
     */
    public bool $cspAutoNonce = true;

    /**
     * --------------------------------------------------------------------------
     * CSP Script Nonce
     * --------------------------------------------------------------------------
     *
     * When enabled, nonces will be automatically generated for inline scripts.
     */
    public bool $cspScriptNonce = true;

    /**
     * --------------------------------------------------------------------------
     * CSP Style Nonce
     * --------------------------------------------------------------------------
     *
     * When enabled, nonces will be automatically generated for inline styles.
     */
    public bool $cspStyleNonce = true;
}