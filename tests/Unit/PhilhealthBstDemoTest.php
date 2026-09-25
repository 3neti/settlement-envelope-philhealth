<?php

declare(strict_types=1);

use Illuminate\Contracts\Foundation\Application;
use LBHurtado\SettlementEnvelope\Contracts\WorkflowSubmission;
use LBHurtado\SettlementEnvelope\Enums\WorkflowIntegrationStatus;
use Symfony\Component\Yaml\Yaml;
use ThreeNeti\SettlementEnvelopePhilhealth\Data\PhilhealthBstDemoSubmissionData;
use ThreeNeti\SettlementEnvelopePhilhealth\Services\PhilhealthBstDemoWorkflowAdapter;
use ThreeNeti\SettlementEnvelopePhilhealth\WorkflowAssets;

/** @param array<string, mixed> $overrides */
function syntheticBstSubmission(array $overrides = []): PhilhealthBstDemoSubmissionData
{
    return new PhilhealthBstDemoSubmissionData(...array_replace([
        'key' => 'synthetic-bst-1',
        'claimReference' => 'DEMO-001',
        'requestedAmountMinor' => 15000,
        'patientName' => 'Synthetic Patient',
        'patientMobile' => '09170000000',
        'documentHashes' => ['claim_form' => str_repeat('a', 64), 'hospital_bill' => str_repeat('b', 64)],
    ], $overrides));
}

it('returns only deterministic pending-review demo results without application side effects', function (): void {
    $application = Mockery::mock(Application::class);
    $application->shouldReceive('environment')->with('local', 'testing')->twice()->andReturnTrue();
    $adapter = new PhilhealthBstDemoWorkflowAdapter($application);
    $submission = syntheticBstSubmission();
    $first = $adapter->submit($submission);
    $second = $adapter->submit($submission);

    expect($adapter->workflowId())->toBe('philhealth.bst.demo')
        ->and($adapter->workflowVersion())->toBe('1.0.0')
        ->and($submission->workflowId())->toBe($adapter->workflowId())
        ->and($submission->workflowVersion())->toBe($adapter->workflowVersion())
        ->and($first->reference())->toMatch('/\ABST-DEMO-[A-F0-9]{16}\z/')
        ->and($second->reference())->toBe($first->reference())
        ->and($first->status())->toBe(WorkflowIntegrationStatus::AwaitingReview)
        ->and($first->demonstrationOnly())->toBeTrue();
});

it('rejects environments outside local testing before processing submissions', function (): void {
    $application = Mockery::mock(Application::class);
    $application->shouldReceive('environment')->with('local', 'testing')->once()->andReturnFalse();

    expect(fn () => (new PhilhealthBstDemoWorkflowAdapter($application))->submit(syntheticBstSubmission()))
        ->toThrow(DomainException::class, 'restricted to local testing');
});

it('rejects submissions from other integrations', function (): void {
    $application = Mockery::mock(Application::class);
    $application->shouldReceive('environment')->with('local', 'testing')->once()->andReturnTrue();
    $submission = Mockery::mock(WorkflowSubmission::class);

    expect(fn () => (new PhilhealthBstDemoWorkflowAdapter($application))->submit($submission))
        ->toThrow(DomainException::class, 'requires its synthetic intake submission');
});

it('fingerprints content independently of idempotency key and document ordering', function (): void {
    $submission = syntheticBstSubmission();

    expect($submission->idempotencyKey())->toBe('synthetic-bst-1')
        ->and($submission->fingerprint())->toMatch('/\A[a-f0-9]{64}\z/')
        ->and(syntheticBstSubmission(['key' => 'another-key'])->fingerprint())->toBe($submission->fingerprint())
        ->and(syntheticBstSubmission(['documentHashes' => [
            'hospital_bill' => str_repeat('b', 64), 'claim_form' => str_repeat('a', 64),
        ]])->fingerprint())->toBe($submission->fingerprint());
});

it('changes the fingerprint when submitted content changes', function (array $overrides): void {
    expect(syntheticBstSubmission($overrides)->fingerprint())->not->toBe(syntheticBstSubmission()->fingerprint());
})->with([
    'reference' => [['claimReference' => 'DEMO-002']],
    'amount' => [['requestedAmountMinor' => 15001]],
    'name' => [['patientName' => 'Another Synthetic Patient']],
    'mobile' => [['patientMobile' => '+639170000001']],
    'documents' => [['documentHashes' => ['claim_form' => str_repeat('c', 64), 'hospital_bill' => str_repeat('b', 64)]]],
]);

it('rejects incomplete or malformed synthetic intake', function (array $overrides): void {
    expect(fn () => syntheticBstSubmission($overrides))->toThrow(DomainException::class);
})->with([
    'empty key' => [['key' => ' ']],
    'long key' => [['key' => str_repeat('a', 256)]],
    'control key' => [['key' => "key\n"]],
    'invalid reference' => [['claimReference' => '../bad']],
    'short reference' => [['claimReference' => 'AB']],
    'zero amount' => [['requestedAmountMinor' => 0]],
    'negative amount' => [['requestedAmountMinor' => -1]],
    'empty patient' => [['patientName' => ' ']],
    'long patient' => [['patientName' => str_repeat('a', 256)]],
    'invalid mobile' => [['patientMobile' => 'not-a-mobile']],
    'missing evidence' => [['documentHashes' => ['claim_form' => str_repeat('a', 64)]]],
    'invalid hash' => [['documentHashes' => ['claim_form' => 'bad', 'hospital_bill' => str_repeat('b', 64)]]],
    'wrong evidence keys' => [['documentHashes' => ['other' => str_repeat('a', 64), 'hospital_bill' => str_repeat('b', 64)]]],
]);

it('ships an opt-in versioned workflow that requires review and a trusted amount signal', function (): void {
    $definition = Yaml::parseFile(WorkflowAssets::driverPath());
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect(WorkflowAssets::driverPath())->toBe(WorkflowAssets::driverDirectory().'/philhealth.bst.demo/v1.0.0.yaml')
        ->and($definition['driver']['id'])->toBe('philhealth.bst.demo')
        ->and($definition['driver']['version'])->toBe('1.0.0')
        ->and($definition['driver']['domain'])->toBe('testing')
        ->and($definition['workflow']['requires_review'])->toBeTrue()
        ->and($definition['workflow']['plans'])->toBe([])
        ->and($definition['signals']['definitions'][0])->toMatchArray([
            'key' => 'amount_verified', 'source' => 'host', 'default' => false,
        ])
        ->and($definition['gates']['definitions'][0]['rule'])->toBe('checklist.required_accepted && signal.amount_verified')
        ->and($manifest)->not->toHaveKeys(['repositories', 'version'])
        ->and($manifest['require'])->not->toHaveKey('3neti/x-change');
});

afterEach(function (): void {
    Mockery::close();
});
