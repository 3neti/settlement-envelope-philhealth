# Settlement Envelope PhilHealth demonstration

`3neti/settlement-envelope-philhealth` owns the synthetic `philhealth.bst.demo@1.0.0`
workflow. It depends on the generic `3neti/settlement-envelope` contracts, not
on x-change. The `^1.3` dependency uses the published workflow discovery and integration contracts.

Public classes live under `ThreeNeti\SettlementEnvelopePhilhealth`:

- `Data\PhilhealthBstDemoSubmissionData`: validated synthetic intake and deterministic fingerprint.
- `Data\PhilhealthBstDemoResultData`: demonstration-only, always awaiting review.
- `Services\PhilhealthBstDemoWorkflowAdapter`: local/testing-only adapter.
- `WorkflowAssets::driverPath()` and `driverDirectory()`: bundled versioned YAML paths.

Installation does not activate a driver, bind an adapter, publish configuration,
register routes, or send notifications. Consumers explicitly register the asset
directory and adapter in their own composition layer.

The adapter does not persist, call HTTP providers, approve claims, or initiate
payouts. Its deterministic reference is not a durable idempotency ledger. Hosts
remain responsible for caller authorization, trusted amount provenance, evidence
review, persistence, and any later economic action. Hashes do not establish
document authenticity. This fixture is not official PhilHealth policy; use only
synthetic data.

Run `composer test` and `composer pint` after installing development dependencies.
Local candidate resolution belongs in a temporary consumer manifest, never in
this package's release metadata.
