<?php

declare(strict_types=1);

$loader = require dirname(__DIR__).'/vendor/autoload.php';
$candidate = getenv('SETTLEMENT_ENVELOPE_SOURCE');

if (is_string($candidate) && $candidate !== '') {
    $source = realpath($candidate.'/src');

    if ($source === false || ! is_dir($source)) {
        throw new RuntimeException('SETTLEMENT_ENVELOPE_SOURCE must point to a package checkout.');
    }

    $loader->addPsr4('LBHurtado\\SettlementEnvelope\\', $source, true);
}
