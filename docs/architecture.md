# Architecture

## Overview

Seedlet is a thin, server-rendered Symfony application: a single Twig page
(`templates/main/index.html.twig`) loads a JavaScript frontend that talks to a small JSON API,
which in turn drives a Transmission daemon over its RPC protocol.

```
Browser (JS modules) --fetch--> Symfony controllers --> TorrentManager --> TransmissionClient --> Transmission RPC
```

## Backend layers

- **Controllers** (`src/Controller/`) are the HTTP boundary. They decode the request, delegate
  to `TorrentManager`, and return JSON responses. They contain no Transmission-specific logic.
  - `MainController` renders the single-page shell.
  - `TorrentController` (`/api/torrents`) lists, adds, updates, pauses/resumes and removes
    torrents, and updates per-file download selection.
  - `SessionController` (`/api/session`) reads/updates Transmission's global session settings
    (speed limits, alt-speed mode).
- **`Torrent\TorrentManager`** is the only class in the application that knows Transmission's
  RPC method and field names (e.g. `torrent-get`, `torrent-add`, `session-set`). Controllers
  never call the RPC client directly.
- **`Torrent\Request\*`** are validated, immutable request DTOs (e.g. `AddTorrentRequest`,
  `TorrentUpdateRequest`, `FileSelectionRequest`, `SessionUpdateRequest`) built with static
  factory methods (`fromUri()`, `fromArray()`, ...). Validation happens at construction time,
  so a controller never has to deal with a partially-valid request.
- **`Torrent\Dto\*`** are read-only response DTOs (e.g. `TorrentSummary`, `TorrentDetail`,
  `AddedTorrent`, `SessionSettings`) built from raw RPC responses via `fromRpc()` factories.
- **`Transmission\TransmissionClient`** implements `TransmissionClientInterface` and is the only
  place that speaks HTTP to Transmission.

## Transmission RPC integration

Transmission's RPC protocol requires every request to carry a session id (`X-Transmission-Session-Id`
header), obtained from a `409 Conflict` response to a request that omits it. `TransmissionClient`:

1. Sends the request (optionally with HTTP basic auth if credentials are configured).
2. On a `409` response, caches the session id from the response header and retries the request
   once.
3. Wraps unreachable/network failures in `TransmissionUnavailableException` and RPC-level
   failures in `TransmissionRpcException`.

See the [RPC specification](https://github.com/transmission/transmission/blob/main/docs/rpc-spec.md)
for the underlying protocol.

### Adding torrents in a specific initial state

Torrents can be added from a `.torrent` file upload, a magnet link, or an HTTP(S) URL, and can
be started immediately or added paused. `TorrentManager::addTorrent()` sets the `paused`
argument directly on the `torrent-add` RPC call so Transmission creates the torrent in the
desired state in a single request, instead of adding it and issuing a separate `torrent-stop`
call afterwards.

## Frontend

The frontend is a single Webpack Encore entry point (`assets/javascripts/seedlet.js`) that wires
together small, focused ES module classes under `assets/javascripts/modules/`, each responsible
for one piece of UI: the torrent list/table (`torrentList.js`), the add-torrent modal
(`addTorrentModal.js`), the torrent details modal (`torrentDetailsModal.js`), the remove
confirmation modal (`removeTorrentModal.js`), settings (`settingsModal.js`), and a thin `fetch`
wrapper around the JSON API (`api.js`). There is no frontend framework or build-time router; the
page polls/refreshes the torrent list via the API.

See [frontend.md](frontend.md) for build details and [development.md](development.md) for the
day-to-day workflow.
