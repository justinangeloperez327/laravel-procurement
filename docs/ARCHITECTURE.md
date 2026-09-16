# Architecture

## Objective

The application digitizes the operational procurement lifecycle while keeping legal interpretation and professional procurement judgment with authorized personnel.

## Architecture Style

A modular Laravel monolith is used to meet the November delivery target without introducing distributed-system overhead.

## Core Domain Chain

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

The initial schema establishes the first four stages. Execution modules will build on this chain rather than duplicate planning data.

## Key Rules

1. Important procurement records are relational, not JSON blobs.
2. Workflow state is explicit and auditable.
3. Objective rules may be validated by software; professional judgment is recorded, not replaced.
4. Procurement methods will become configurable domain records instead of scattered conditional logic.
5. Document files live in object storage while document metadata and versions live in PostgreSQL.
6. Audit records are append-only from normal application workflows.
7. Authorization is permission-driven through policies and roles.

## Money

Amounts use `decimal(18, 2)`. Monetary values must never use floating-point database types.

## Time

Application timestamps are stored in UTC. User-facing dates and timestamps can later be rendered in the Procuring Entity's configured timezone.
