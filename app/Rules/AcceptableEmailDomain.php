<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class AcceptableEmailDomain implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if (! is_string($value)) {
            return;
        }

        $domain = Str::lower(
            trim(
                Str::afterLast(
                    $value,
                    '@',
                ),
            ),
        );

        if ($domain === '') {
            return;
        }

        $blockedDomains = $this->configuredDomains(
            'registration.blocked_email_domains',
        );

        if (
            $this->matchesConfiguredDomain(
                $domain,
                $blockedDomains,
            )
        ) {
            $fail(
                'Disposable, temporary, or reserved email addresses are not allowed.',
            );

            return;
        }

        $allowedDomains = $this->configuredDomains(
            'registration.allowed_email_domains',
        );

        if (
            $allowedDomains !== []
            && ! $this->matchesConfiguredDomain(
                $domain,
                $allowedDomains,
            )
        ) {
            $fail(
                'Please use an approved permanent email address.',
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function configuredDomains(
        string $key,
    ): array {
        $domains = config(
            $key,
            [],
        );

        if (! is_array($domains)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    static function (
                        mixed $domain,
                    ): string {
                        if (! is_string($domain)) {
                            return '';
                        }

                        return Str::lower(
                            trim($domain),
                        );
                    },
                    $domains,
                ),
            ),
        );
    }

    /**
     * Match both the exact domain and its subdomains.
     *
     * Blocking mailinator.com also blocks inbox.mailinator.com.
     *
     * @param  array<int, string>  $configuredDomains
     */
    private function matchesConfiguredDomain(
        string $domain,
        array $configuredDomains,
    ): bool {
        foreach ($configuredDomains as $configuredDomain) {
            if (
                $domain === $configuredDomain
                || str_ends_with(
                    $domain,
                    '.'.$configuredDomain,
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
