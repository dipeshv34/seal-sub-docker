<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/subscription-webhook-created',
        '/seal-topic-subscription-created',
        '/subscription-webhook-updated',
        '/subscription-webhook-cancelled',
        '/seal-topic-subscription-created',
        '/seal-topic-subscription-updated',
        '/seal-topic-subscription-cancelled',
        '/shopify-product-created',
        '/shopify-product-updated'

    ];
}
