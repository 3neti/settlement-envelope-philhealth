<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use LBHurtado\SettlementEnvelope\Contracts\WorkflowIntegrationAdapter;
use LBHurtado\SettlementEnvelope\Services\DriverSourceRegistry;
use ThreeNeti\SettlementEnvelopePhilhealth\Services\PhilhealthBstDemoWorkflowAdapter;
use ThreeNeti\SettlementEnvelopePhilhealth\SettlementEnvelopePhilhealthServiceProvider;

it('advertises its Laravel provider for automatic discovery', function (): void {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['extra']['laravel']['providers'])->toContain(SettlementEnvelopePhilhealthServiceProvider::class);
});

it('registers package resources without eagerly resolving execution services', function (bool $alreadyResolved): void {
    $app = new Container;
    $app->singleton(DriverSourceRegistry::class);
    if ($alreadyResolved) {
        $app->make(DriverSourceRegistry::class);
    }

    (new SettlementEnvelopePhilhealthServiceProvider($app))->register();

    expect($app->resolved(PhilhealthBstDemoWorkflowAdapter::class))->toBeFalse()
        ->and($app->bound(PhilhealthBstDemoWorkflowAdapter::class))->toBeTrue()
        ->and($app->resolved(DriverSourceRegistry::class))->toBe($alreadyResolved);

    $sources = $app->make(DriverSourceRegistry::class);
    expect($sources->roots())->toBe([
        '3neti/settlement-envelope-philhealth' => realpath(dirname(__DIR__, 2).'/resources/drivers'),
    ])->and($sources->read('3neti/settlement-envelope-philhealth', 'philhealth.bst.demo/v1.0.0.yaml'))
        ->toBe(file_get_contents(dirname(__DIR__, 2).'/resources/drivers/philhealth.bst.demo/v1.0.0.yaml'))
        ->and($app->resolved(PhilhealthBstDemoWorkflowAdapter::class))->toBeFalse();
})->with([false, true]);

it('resolves its tagged adapter without executing environment or submission logic', function (): void {
    $app = new Container;
    $application = Mockery::mock(Application::class);
    $application->shouldNotReceive('environment');
    $app->instance(Application::class, $application);
    (new SettlementEnvelopePhilhealthServiceProvider($app))->register();

    $adapters = iterator_to_array($app->tagged('settlement-envelope.workflow-adapters'));

    expect($adapters)->toHaveCount(1)
        ->and($adapters[0])->toBeInstanceOf(WorkflowIntegrationAdapter::class)
        ->toBe($app->make(PhilhealthBstDemoWorkflowAdapter::class))
        ->and($adapters[0]->workflowId())->toBe('philhealth.bst.demo');
});
