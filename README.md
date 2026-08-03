# crm-rs

A minimal **CRM** (Customer Relationship Management) web application written in Rust.

It exposes a small REST API for managing contacts/leads and serves a lightweight
single-page UI on top of it. State is persisted in a local SQLite database
(bundled — no external database server required).

## Stack

- [Axum](https://github.com/tokio-rs/axum) HTTP framework on [Tokio](https://tokio.rs/)
- [rusqlite](https://github.com/rusqlite/rusqlite) with the `bundled` SQLite feature
- Vanilla HTML/CSS/JS frontend served from `static/`

## Requirements

- Rust (stable) toolchain with `cargo` — the project builds against Rust 1.83+
- A C compiler (`cc`/`gcc`/`clang`) — required to build bundled SQLite

## Getting started

```bash
# Fetch dependencies
cargo fetch

# Run the development server (defaults to http://localhost:3000)
cargo run
```

Then open <http://localhost:3000> in a browser to use the CRM UI.

### Configuration

| Env var         | Default  | Description                          |
| --------------- | -------- | ------------------------------------ |
| `PORT`          | `3000`   | Port the HTTP server listens on      |
| `DATABASE_PATH` | `crm.db` | SQLite file path (use `:memory:` for ephemeral) |
| `RUST_LOG`      | `info`   | Log level filter                     |

## API

| Method   | Path                 | Description            |
| -------- | -------------------- | ---------------------- |
| `GET`    | `/api/health`        | Health check           |
| `GET`    | `/api/contacts`      | List all contacts      |
| `POST`   | `/api/contacts`      | Create a contact       |
| `GET`    | `/api/contacts/:id`  | Fetch a single contact |
| `PUT`    | `/api/contacts/:id`  | Update a contact       |
| `DELETE` | `/api/contacts/:id`  | Delete a contact       |

Example:

```bash
curl -s -X POST http://localhost:3000/api/contacts \
  -H 'Content-Type: application/json' \
  -d '{"name":"Ada Lovelace","email":"ada@example.com","status":"lead"}'
```

## Development

```bash
cargo fmt --all          # format
cargo clippy --all-targets -- -D warnings   # lint
cargo test               # run the integration test suite
cargo run                # run the dev server
```
