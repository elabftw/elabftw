# SQL folder

## Description

This folder holds all the database structure migration files. Every migration has a `schema` number, and the corresponding SQL statements contained within the corresponding file are run in order during `db:update` action.

## Former columns

Columns that should be removed after a while because they are not used anymore but were kept around so the `-down` action would not lose information:

- `items_types.bookable_old` since 4.8.0, schema 127
