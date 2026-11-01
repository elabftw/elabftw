# eLabFTW — notes for Claude

## Dev environment

The app runs in the `elabftw/elabdev` image (the dev variant of the production `elabftw/elabimg`),
with `DEV_MODE=true`. The repo is bind-mounted into the container at `/elabftw`.

**All PHP, Composer, Node/Yarn and `bin/console` commands run INSIDE the container, never on the host.**

- Start the stack: `./containers/elabdev/start.sh` (compose file: `containers/elabdev/docker-compose.yml`)
- Check it's up: `docker ps` → containers `elabftw` (app) and `mysql` (DB)
- Run a command: `docker exec elabftw <cmd>` — never use `-it`, there is no TTY
- Web UI: https://elab.localhost:3148 (self-signed cert, use `curl -k`)
- phpMyAdmin: http://localhost:8082 (user `elabftw`, password in the `mysql` service of the compose file)
- Logs: `docker logs elabftw`

If the container isn't running, start it before doing anything else. Don't work around a missing
container by installing tools on the host.

## Common tasks

| Task | Command |
|---|---|
| PHP deps | `docker exec elabftw composer install` |
| JS deps | `docker exec elabftw yarn install` |
| Build JS + main CSS | `docker exec elabftw yarn buildall:dev` |
| Build PDF-export CSS only | `docker exec elabftw yarn buildcss` |
| Unit tests | `docker exec elabftw yarn unit` |
| Full test suite | `docker exec elabftw yarn test` (slow) |
| Lint/static analysis before commit | `docker exec elabftw yarn pre-commit` |
| Everything CI checks | `docker exec elabftw yarn full` — must pass before a PR |
| Static analysis | `docker exec elabftw yarn phpstan` / `yarn psalm` |
| PHP code style | `docker exec elabftw yarn phpcs` |
| Regenerate JS i18n terms | `docker exec elabftw bin/console dev:i18n4js` |

Other scripts are in the `scripts` section of `package.json`; check there before inventing a command.

Watchers (`yarn watchjs`, `yarn watchpdfcss`) never exit: only run them in the background, and prefer
a one-shot `yarn buildall:dev` when you just need to verify a change.

`main.scss` is bundled by webpack with the JS, so SCSS changes need `buildall:dev` (or `watchjs`),
not `buildcss`. Rebuild before checking anything in the browser.

## Database changes

Migrations live in `src/sql/`. Do NOT add new numbered `schemaNNN.sql` files and do NOT bump
`SchemaVersionChecker::REQUIRED_SCHEMA`.

1. `docker exec elabftw bin/console dev:genschema <short_name>` → creates
   `migration-<uuid>_<name>.sql` and `migration-<uuid>_<name>.down.sql` (templates only)
2. Write both the up and the down SQL. The up migration must be safe with `db:update --force`.
3. Update `src/sql/structure.sql` so fresh installs get the final structure
   (and `src/Models/Config.php` if config defaults change).
4. Test it: `bin/console db:update`, `db:status`, `db:revert <uuid>`, then `db:update` again.
5. Run `composer dump-autoload` after adding/removing migration files.

Never use `dev:forceschema` unless explicitly asked; it changes migration state without touching the DB.

## Code organization

- `web/` — real entry pages (experiments.php, database.php, login.php…)
- `src/Models` — CRUD classes; `src/Services`, `src/Elabftw` — services/utilities
- `src/ts` — all frontend code (TypeScript, bundled by webpack)
- `src/scss`, `src/sql`, `src/langs`, `src/tools`
- `containers/` — Docker images; `documentation/` — docs site; `apidoc/` — API specs
- PSR-4 autoloading, namespace `Elabftw\Elabftw`

Glossary: Experiments, Resources, and their templates are all **Entities**. In code, Resources are
**Items** and Resource templates are **ItemsTypes**.

## Conventions

- PHP follows PSR standards; respect `.editorconfig` (no trailing whitespace, no BOM).
- camelCase in PHP and JS; dash-separated names for CSS classes in HTML.
- Frontend: vanilla TypeScript only. No jQuery selectors or functions.
- Comments explain *why*, not *what*.
- Exceptions:
  - Never throw a generic `Exception`; use one from `Elabftw\Exceptions`.
  - `ImproperActionException` for bad requests, `ForbiddenException` for authorization.
  - Throw as soon as something goes wrong instead of returning `bool`.
  - Don't catch exceptions in models, only in controllers.

## Git

- Branch from `master`; PRs target `master`.
- Run `yarn pre-commit` (inside the container) before committing.
- All PR code must be reviewed by a human and tested. Don't open PRs autonomously; leave that to me.
