# Configuration

Seedlet is configured entirely through environment variables, loaded from `.env` (defaults,
committed) and `.env.local` (local overrides, git-ignored). `.env.test` sets test-only
overrides used by the functional test suite.

| Variable                 | Default                                    | Description                                                                 |
|---------------------------|---------------------------------------------|-------------------------------------------------------------------------------|
| `APP_ENV`                 | `prod`                                       | Symfony environment (`dev`, `test`, `prod`).                                  |
| `APP_SECRET`               | *(empty)*                                    | Symfony secret used for CSRF tokens, signed cookies, etc. Set this yourself.  |
| `TRANSMISSION_URL`         | `http://localhost:9091/transmission/rpc`     | Full URL of the Transmission daemon's RPC endpoint.                          |
| `TRANSMISSION_USERNAME`    | *(empty)*                                    | RPC basic-auth username. Leave empty if Transmission has no RPC auth.        |
| `TRANSMISSION_PASSWORD`    | *(empty)*                                    | RPC basic-auth password.                                                       |
| `TRANSMISSION_TIMEOUT`     | `10`                                          | HTTP timeout (seconds) for RPC requests, see `TransmissionClient`.            |
| `DEFAULT_URI`               | `http://localhost`                           | Default URI used by Symfony's router to generate URLs outside of an HTTP request (e.g. CLI). |

These are consumed via `#[Autowire(env: ...)]` attributes on `TransmissionClient` (see
[src/Transmission/TransmissionClient.php](../src/Transmission/TransmissionClient.php)) and in
`config/packages/routing.php`.

To configure a local instance, edit `.env.local` (already created, empty by default):

```
APP_SECRET=change-me
TRANSMISSION_URL=http://localhost:9091/transmission/rpc
TRANSMISSION_USERNAME=
TRANSMISSION_PASSWORD=
```

No database or other external service is required beyond a reachable Transmission daemon.
