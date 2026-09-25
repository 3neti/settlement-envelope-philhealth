# Settlement Envelope PhilHealth demonstration

`3neti/settlement-envelope-philhealth` owns the synthetic `philhealth.bst.demo@1.0.0`
workflow. It depends on the generic `3neti/settlement-envelope` contracts, not
on x-change. The `^1.3` dependency uses the published workflow discovery and integration contracts.

Public classes live under `ThreeNeti\SettlementEnvelopePhilhealth`:

- `Data\PhilhealthBstDemoSubmissionData`: validated synthetic intake and deterministic fingerprint.
- `Data\PhilhealthBstDemoResultData`: demonstration-only, always awaiting review.
- `Services\PhilhealthBstDemoWorkflowAdapter`: local/testing-only adapter.
- `WorkflowAssets::driverPath()` and `driverDirectory()`: bundled versioned YAML paths.

Laravel automatically discovers `SettlementEnvelopePhilhealthServiceProvider`,
which registers the bundled driver source and lazily binds the concrete adapter
under the `settlement-envelope.workflow-adapters` container tag. This requires the
upcoming settlement-envelope 1.4 source registry; before releasing this change,
raise the dependency minimum from 1.3 to 1.4 after that version is published and
refresh the lockfile. This candidate is not release-ready while its dependency
minimum remains `^1.3`.

Installation makes the workflow discoverable, but does not activate it, publish
configuration, register routes, send notifications, or submit a workflow. Discovery
does not bypass host readiness, authorization, or environment checks.

The adapter does not persist, call HTTP providers, approve claims, or initiate
payouts. Its deterministic reference is not a durable idempotency ledger. Hosts
remain responsible for caller authorization, trusted amount provenance, evidence
review, persistence, and any later economic action. Hashes do not establish
document authenticity. This fixture is not official PhilHealth policy; use only
synthetic data.

Run `composer test` and `composer pint` after installing development dependencies.
Local candidate resolution belongs in a temporary consumer manifest, never in
this package's release metadata.

To test against an unreleased generic package checkout without modifying vendor,
run `SETTLEMENT_ENVELOPE_SOURCE=/absolute/path/to/settlement-envelope composer test`.
