# Changelog

## v0.1.5 - 2026-10-21

- R3: normalize direct request targets once before policy classification; remove send-time ltrim, reject ambiguous paths and cover multiple leading slashes, queries, encodings, relative paths and activation routes.

- R2: enforce HTTPS/TLS and no redirects for provisioning; classify HTTP errors independently of JSON and preserve Retry-After; add security regressions.

- Add explicit partner provisioning helper and ConflictException; included in 0.1.5.

## v0.1.4 - 2026-06-24

- Added `campaigns()->update()` and `campaigns()->delete()` for the public `/campaigns/{campaignId}` update/delete contract.
- Updated campaign service documentation to match the public OpenAPI surface.

## v0.1.3 - 2026-06-24

- Added `emails()->ingestBounce()` for the public `POST /emails/bounces` contract.
- Documented manual bounce ingestion for provider/API callbacks that report delivery failures outside the signed SMTP webhook flow.

## v0.1.2 - 2026-05-30

- Added `contacts()` service for the public contact endpoints (`list`, `create`, `show`, `update`, `replace`, `delete`).
- Aligned the SDK surface with the OpenAPI update that introduced `/contacts`.

## v0.1.1 - 2026-05-29

- Documented the public OpenAPI spec as the normative contract for the package.
- Expanded contract coverage for base URL normalization and cross-domain payload serialization tests.
- Added quickstart example and SDK-facing validation guidance.

## v0.1.0

- Initial framework-agnostic PHP SDK for the public Pulse API.
