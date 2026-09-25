<?php

declare(strict_types=1);

namespace ThreeNeti\SettlementEnvelopePhilhealth;

final class WorkflowAssets
{
    public static function driverDirectory(): string
    {
        return dirname(__DIR__).'/resources/drivers';
    }

    public static function driverPath(): string
    {
        return self::driverDirectory().'/philhealth.bst.demo/v1.0.0.yaml';
    }
}
