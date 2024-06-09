<?php

namespace App\Resolvers;

use App\Services\PaywallService;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Class PaymentPlatformResolver
 * @package App\Resolvers
 *
 * Resolves the appropriate payment platform service based on configuration.
 */
class PaymentPlatformResolver
{
    /**
     * PaymentPlatformResolver constructor.
     */
    public function __construct()
    {
    }

    /**
     * Resolves the payment platform service.
     *
     * @param string $paymentPlatformName The name of the payment platform to resolve.
     * @return PaywallService The resolved payment platform service.
     * @throws \Exception If the payment platform is not configured correctly.
     * @throws BindingResolutionException If the service cannot be resolved.
     */
    public function resolveService(string $paymentPlatformName): PaywallService
    {
        // Retrieve the class name of the payment platform service from the configuration
        $service = Config::get("services.{$paymentPlatformName}.class");

        if ($service) {
            // Resolve and return the service instance
            return app()->make($service);
        }

        // Throw an exception if the payment platform is not configured correctly
        throw new \Exception('The selected platform is not in the configuration');
    }
}
