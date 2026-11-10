# Focused frontend regressions

Run `yarn test:frontend` with the Node version required by `package.json`.

These dependency-free tests load the real TypeScript modules using Node's type transformer and VM modules. Browser/network boundaries and notifications are substituted; no server, database, account, or private data is required. The suites cover download headers and lifetimes, error reporting, request parameter ownership/encoding, and keyboard event filtering.

This is not a replacement for browser acceptance tests, TypeScript checks, linting, or the production bundle build. Node's VM modules require the experimental flag used by the package script.

For regression confirmation, `ELAB_TEST_SOURCE_ROOT` can point to an unmodified source checkout. Test code remains here; imported application modules are loaded from that checkout. Tests for newly added modules require those modules to exist; use `--test-name-pattern` when comparing earlier implementations.
