<?php

$parseDomains = static function (
    mixed $value,
): array {
    if (! is_string($value)) {
        return [];
    }

    return array_values(
        array_unique(
            array_filter(
                array_map(
                    static fn (string $domain): string => mb_strtolower(
                        trim($domain),
                    ),
                    explode(',', $value),
                ),
            ),
        ),
    );
};

return [
    /*
    |--------------------------------------------------------------------------
    | Strict domain allowlist
    |--------------------------------------------------------------------------
    |
    | When this list is empty, any permanent domain is allowed unless it
    | appears in the blocklist below.
    |
    | When populated, registration accepts only these domains and their
    | subdomains. Strict allowlisting provides the strongest protection
    | against newly created disposable-email services.
    |
    */
    'allowed_email_domains' => $parseDomains(
        env(
            'CITIRX_ALLOWED_EMAIL_DOMAINS',
            '',
        ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Disposable and reserved domain blocklist
    |--------------------------------------------------------------------------
    |
    | This local list avoids depending on an external API during account
    | registration. Additional domains can be supplied through .env.
    |
    */
    'blocked_email_domains' => array_values(
        array_unique([
            // Reserved and non-deliverable domains.
            'example.com',
            'example.net',
            'example.org',
            'invalid',
            'localhost',
            'test',

            // Common disposable-email services.
            '10minutemail.com',
            'dispostable.com',
            'emailondeck.com',
            'fakeinbox.com',
            'getnada.com',
            'grr.la',
            'guerrillamail.biz',
            'guerrillamail.com',
            'guerrillamail.de',
            'guerrillamail.info',
            'guerrillamail.net',
            'guerrillamail.org',
            'guerrillamailblock.com',
            'maildrop.cc',
            'mailinator.com',
            'mintemail.com',
            'mohmal.com',
            'sharklasers.com',
            'temp-mail.org',
            'tempmail.com',
            'throwawaymail.com',
            'trashmail.com',
            'yopmail.com',

            ...$parseDomains(
                env(
                    'CITIRX_ADDITIONAL_BLOCKED_EMAIL_DOMAINS',
                    '',
                ),
            ),
        ]),
    ),
];
