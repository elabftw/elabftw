# SQL migrations

## Create a migration

```sh
bin/console dev:genschema add_booking_color
```

This generates a UTC timestamp and descriptive name:

- `migrations/2026_09_14_090000_add_booking_color.sql`
- `migrations/2026_09_14_090000_add_booking_color-down.sql`

Write the forward SQL and its compensating rollback in those files. Use a unique,
short snake_case description. The generator refuses to overwrite existing files.
Do not edit or rename migrations that have already shipped; add a new migration.

There is no shared schema number to increment. `schema_migrations` records each
applied ID, batch, execution order, and application time. Pending files run in
filename order, including older timestamps introduced after newer ones have run.
Timestamps do not resolve SQL dependencies or incompatible changes: dependent
migrations still need to be ordered and reviewed together.

## Apply and inspect

```sh
bin/console db:check
bin/console db:update
bin/console db:update --step
```

`db:check` lists applied and pending migrations. Exit codes are 0 for current,
1 for an upgrade, and 2 for an incompatible checkout (newer legacy baseline or
applied migrations absent from the checkout).

Each update uses one batch, or one batch per migration with `--step`. Timestamped
migration/history writes are serialized with a MySQL advisory lock. A migration
is recorded only after its SQL succeeds. MySQL DDL and history writes are not one
atomic transaction: after a process crash, inspect the database and repair the
history explicitly if necessary.

## Roll back

```sh
bin/console db:revert                              # last batch
bin/console db:revert --step=1                     # last migration
bin/console db:revert 2026_09_14_090000_add_booking_color
```

Rollback runs in reverse **execution** order, not timestamp order. An explicit ID
must be the latest applied migration. All SQL pairs in the rollback plan must
exist before execution begins. A failed down migration remains in history.
Rollback can lose data; down SQL must be reviewed just like forward SQL.

## Repair history explicitly

```sh
bin/console dev:forceschema 2026_09_14_090000_add_booking_color
bin/console dev:forceschema 2026_09_14_090000_add_booking_color --forget
```

These commands mark one migration as applied or pending without executing its SQL.
They are for manual recovery after verifying the actual database state. They do
not migrate to a target timestamp and do not alter other history entries.
`--force` error suppression remains available only for legacy numbered SQL;
timestamped migrations always stop on an error.

## Legacy baseline and new installations

Schema 224 is the final numbered schema. `SchemaVersionChecker::REQUIRED_SCHEMA`,
`structure.sql`, and `Config::create()` describe this fixed baseline and must no
longer be updated for each feature. Put new configuration defaults and structural
changes in timestamped migrations instead.

Existing installations run the original numbered upgrades through 224, preserving
historical upgrade behavior, then initialize migration history and run pending
migrations. Fresh installs and development database resets load the baseline,
initialize its configuration, and run the same timestamped migrations before
creating application data. Importing `structure.sql` manually is therefore only
the first step; initialize configuration and run `bin/console db:update` afterward.

`db:revert 224` and numeric `dev:forceschema` remain available for legacy recovery,
but only when no timestamped migrations remain recorded. A numeric rollback must
match the current legacy schema; numeric force only changes its metadata.

## SQL helpers and failed upgrades

Procedures in `procedures.sql` are available in migration SQL:

- `DropFK(table name, foreign key name)`
- `DropIdx(table name, index name)`
- `DropColumn(table name, column name)`
- `drop_fk_if_exists(table name, column name)`

`db:update` executes the matching down SQL when an up migration fails. MySQL DDL
cannot be rolled back as a transaction, so down migrations must handle a partially
applied up migration. Prefer `IF EXISTS` and the helper procedures where applicable.
If compensation also fails, inspect and repair the database before retrying.

## Former columns

Columns kept so the down action would not lose information:

- `items_types.bookable_old` since 4.8.0, schema 127; removed in 180
- `users.archived` since 5.3.0, schema 180
