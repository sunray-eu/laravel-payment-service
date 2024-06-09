<?php

use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Support\Facades\Config;
use App\Services\PayPalService;
use App\Services\SampleService;
use Illuminate\Contracts\Container\BindingResolutionException;

beforeEach(function () {
    Config::set('services.paypal.class', PayPalService::class);
    Config::set('services.samplepaywall.class', SampleService::class);
    $this->resolver = new PaymentPlatformResolver();
});

it('resolves PayPal service correctly', function () {
    $service = $this->resolver->resolveService('paypal');
    expect($service)->toBeInstanceOf(PayPalService::class);
});

it('resolves Sample service correctly', function () {
    $service = $this->resolver->resolveService('samplepaywall');
    expect($service)->toBeInstanceOf(SampleService::class);
});

it('throws exception for unconfigured service', function () {
    Config::set('services.unconfigured.class', null);
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('The selected platform is not in the configuration');

    $this->resolver->resolveService('unconfigured');
});

it('throws BindingResolutionException for invalid service class', function () {
    Config::set('services.invalid.class', 'InvalidClass');
    $this->expectException(BindingResolutionException::class);

    $this->resolver->resolveService('invalid');
});
