# Development workflow

## Setup

```
make start    # install PHP + JS dependencies, build dev assets, clear/warm the cache
```

Then serve the app with any PHP web server pointed at `public/`, for example:

```
php -S 127.0.0.1:8000 -t public
```

Run `make watch` in a separate terminal while working on the frontend to rebuild assets
automatically on change.

Run `make help` to see every available Makefile target with a short description.

## Code style checks

```
make check              # runs both of the checks below
make php-cs-test        # PHP-CS-Fixer, dry-run (see .php-cs-fixer.dist.php)
make php-cs-test-fix    # PHP-CS-Fixer, applies fixes
make js-cs-test          # semistandard, check
make js-cs-test-fix      # semistandard, applies fixes
```

PHP-CS-Fixer is installed on demand into a separate `tools/php-cs-fixer` Composer project
(kept out of the main `composer.json`) and removed again after the check runs.

## Tests

```
make test    # runs both suites below
make tu       # PHPUnit unit tests (excludes the "functional" group)
make tf       # PHPUnit functional tests (the "functional" group only)
```

Functional tests boot the real Symfony kernel against the `test` environment
(`APP_ENV=test`, configuration in `.env.test`); unit tests do not. Transmission RPC calls are
never made for real in tests: `TransmissionClientInterface` is mocked/stubbed with PHPUnit
(`createMock()`/`createStub()`), asserting the exact RPC method and argument array each
service call is expected to send.

`make test-ci` is the entry point used by CI ([.github/workflows/check.yaml](../.github/workflows/check.yaml));
it also runs `make check`.

## Full local validation

Before submitting a change, the full clean pipeline (removes `vendor/` and `node_modules/`,
reinstalls everything, rebuilds assets, and runs both checks and tests) can be run with:

```
make clean check test
```

## Production build

`make deploy` installs PHP dependencies without dev packages, builds production (minified,
versioned) frontend assets, warms the cache, dumps an optimized autoloader, and fixes
permissions on `var/` — it does not perform any deployment to a remote host, packaging, or
process management, which are left to the environment running the application.
