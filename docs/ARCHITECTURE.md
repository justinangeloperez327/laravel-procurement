# Procurement Application Architecture

## Goal

Build a Philippine government procurement management application that digitizes the operational procurement lifecycle while keeping legal interpretation and professional procurement judgment with authorized personnel.

## Architecture Style

The application is a Laravel modular monolith. Existing Laravel starter-kit authentication, security, Inertia, React, and UI infrastructure are retained. Procurement capabilities are organized under `app/Domain`.

## Initial Domain Chain

```text
Market Scoping
    ↓
PPMP
    ↓
APP
    ↓
Procurement Project
    ↓
BAC / Bidding / Evaluation
    ↓
Award
    ↓
Contract / PO
    ↓
Delivery / Inspection
    ↓
Completion / Supplier Performance
```

The first implementation slice establishes Organization, Fiscal Year, Suppliers, Procurement Methods, Market Scoping, PPMP, APP, and Procurement Project.

## Design Rules

1. Procurement records use relational tables for system-of-record data.
2. JSON is reserved for genuinely flexible configuration or snapshots, not core transactional records.
3. Workflow states are explicit and auditable.
4. Objective rules may be validated by software; professional judgment is recorded rather than replaced.
5. Procurement methods are configurable records instead of scattered string conditions.
6. Document binaries will live in object storage; metadata and versions will live in PostgreSQL.
7. Monetary database columns use fixed-precision decimal types, never floating point.
8. Application timestamps remain UTC; display timezone is configurable at the organization level later.
9. Cross-domain behavior should be implemented through actions/services rather than oversized controllers.
10. The November deadline favors a modular monolith over microservices, CQRS, event sourcing, or a BPMN engine.
