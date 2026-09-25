<?php

declare(strict_types=1);

namespace ThreeNeti\SettlementEnvelopePhilhealth\Data;

use DomainException;
use LBHurtado\SettlementEnvelope\Contracts\WorkflowSubmission;
use SensitiveParameter;

/** Synthetic intake only: evidence hashes are not authenticity or reviewer authority. */
final readonly class PhilhealthBstDemoSubmissionData implements WorkflowSubmission
{
    /** @param array{claim_form: string, hospital_bill: string} $documentHashes */
    public function __construct(
        private string $key,
        private string $claimReference,
        private int $requestedAmountMinor,
        #[SensitiveParameter] private string $patientName,
        #[SensitiveParameter] private string $patientMobile,
        #[SensitiveParameter] private array $documentHashes,
    ) {
        if (trim($key) === '' || strlen($key) > 255 || preg_match('/[\x00-\x1f\x7f]/', $key)
            || preg_match('/\A[A-Za-z0-9-]{3,50}\z/', $claimReference) !== 1
            || $requestedAmountMinor <= 0
            || trim($patientName) === '' || mb_strlen($patientName) > 255
            || preg_match('/\A(?:09[0-9]{9}|\+639[0-9]{9})\z/', $patientMobile) !== 1
            || count($documentHashes) !== 2) {
            throw new DomainException('A complete synthetic BST submission is required.');
        }
        foreach (['claim_form', 'hospital_bill'] as $document) {
            if (! is_string($documentHashes[$document] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/', $documentHashes[$document]) !== 1) {
                throw new DomainException('Both synthetic BST document hashes are required.');
            }
        }
    }

    public function workflowId(): string
    {
        return 'philhealth.bst.demo';
    }

    public function workflowVersion(): string
    {
        return '1.0.0';
    }

    public function idempotencyKey(): string
    {
        return $this->key;
    }

    public function fingerprint(): string
    {
        return hash('sha256', json_encode([
            'workflow' => $this->workflowId().'@'.$this->workflowVersion(),
            'reference' => $this->claimReference,
            'requested_amount_minor' => $this->requestedAmountMinor,
            'patient_name' => $this->patientName,
            'patient_mobile' => $this->patientMobile,
            'claim_form' => $this->documentHashes['claim_form'],
            'hospital_bill' => $this->documentHashes['hospital_bill'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
