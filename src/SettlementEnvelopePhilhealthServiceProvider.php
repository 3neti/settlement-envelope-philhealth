<?php

declare(strict_types=1);

namespace ThreeNeti\SettlementEnvelopePhilhealth;

use Illuminate\Support\ServiceProvider;
use LBHurtado\SettlementEnvelope\Services\DriverSourceRegistry;
use ThreeNeti\SettlementEnvelopePhilhealth\Services\PhilhealthBstDemoWorkflowAdapter;

final class SettlementEnvelopePhilhealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PhilhealthBstDemoWorkflowAdapter::class);
        $this->app->tag([PhilhealthBstDemoWorkflowAdapter::class], 'settlement-envelope.workflow-adapters');

        $this->callAfterResolving(DriverSourceRegistry::class, function (DriverSourceRegistry $sources): void {
            $sources->register('3neti/settlement-envelope-philhealth', WorkflowAssets::driverDirectory());
        });
    }
}
