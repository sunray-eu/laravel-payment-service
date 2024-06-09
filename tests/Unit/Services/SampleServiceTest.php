<?php

use App\Services\SampleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Config::set('services.samplepaywall.base_uri', 'https://api.sandbox.sample.com');
    Config::set('services.samplepaywall.key', 'test_client_key');
    Config::set('services.samplepaywall.secret', 'test_client_secret');

    $this->sampleService = Mockery::mock(SampleService::class)->makePartial();
});

it('generates a payment link successfully', function () {
    $request = new Request([
        'payment_method' => 'credit_card'
    ]);

    $this->sampleService->shouldReceive('uuid_create')
        ->andReturn('some-random-id');

    $link = $this->sampleService->getPaymentLink($request, 'https://return.url', 'https://cancel.url');

    expect($link)->toBe('https://example.com/payment/some-random-id');
});

it('handles approval successfully', function () {
    $result = $this->sampleService->handleApproval();

    expect($result['status'])->toBe('success');
});

it('fails to generate a payment link with missing payment method', function () {
    $request = new Request([]);

    $this->expectException(ValidationException::class);

    $this->sampleService->getPaymentLink($request, 'https://return.url', 'https://cancel.url');
});

it('decodes response correctly', function () {
    $response = json_encode(['key' => 'value']);

    $reflection = new ReflectionClass(new SampleService());
    $method = $reflection->getMethod('decodeResponse');
    $method->setAccessible(true);

    $decoded = $method->invoke($this->sampleService, $response);

    expect($decoded)->toEqual((object) ['key' => 'value']);
});

it('generates random uuid', function () {
    $randomString = $this->sampleService->uuid_create();

    expect($randomString)->toBeString();
});

it('generates correct access token', function () {
    $sampleService = new SampleService();
    $reflection = new ReflectionClass($sampleService);
    $method = $reflection->getMethod('resolveAccessToken');
    $method->setAccessible(true);

    $generatedToken = $method->invoke($sampleService);

    expect($generatedToken)->toBe('Bearer test_client_secret');
});
