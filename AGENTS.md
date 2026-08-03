# AGENTS.md

## Project overview

`crm-rs` is a small CRM (Customer Relationship Management) web service written in
Rust. It serves a REST API for managing contacts/leads plus a static single-page
UI. Data is persisted in a bundled SQLite database (no external DB server).

- `src/lib.rs` — builds the Axum `Router` (`build_app`); shared by the binary and tests.
- `src/main.rs` — binary entrypoint; opens the DB and starts the HTTP server.
- `src/db.rs` — SQLite access layer (`Db`), thread-safe via `Arc<Mutex<Connection>>`.
- `src/handlers.rs` — HTTP handlers for the `/api/contacts` endpoints.
- `src/models.rs` — request/response data models.
- `static/index.html` — the frontend served at `/`.
- `tests/api.rs` — integration tests that boot the app on an in-memory DB.

## Common commands

Standard commands are documented in `README.md`. In short:

- Build: `cargo build`
- Run dev server: `cargo run` (serves `http://localhost:3000`)
- Test: `cargo test`
- Lint: `cargo clippy --all-targets -- -D warnings`
- Format check: `cargo fmt --all -- --check`

## Cursor Cloud specific instructions

- Toolchain is pinned via `rust-toolchain.toml` to a stable Rust that supports
  edition 2024 (the VM's default `1.83.0` is too old for some transitive
  dependencies such as `hashbrown`). The pinned toolchain is auto-installed by
  rustup on the first `cargo` invocation, so no manual `rustup` step is needed.
- Bundled SQLite (`rusqlite`'s `bundled` feature) compiles SQLite from C source,
  so a C compiler must be present (`gcc`/`clang` are already available on the VM).
- The `reqwest` dev-dependency is intentionally configured with
  `default-features = false` (no native-tls) because the test client only talks
  to the local app over plain HTTP. Do not re-enable native-tls without also
  installing `libssl-dev`, or clippy/tests will fail to build against `openssl-sys`.
- Running the server writes a `crm.db` SQLite file in the working directory
  (git-ignored). Delete it for a clean slate, or set `DATABASE_PATH=:memory:`
  for an ephemeral run. The port is configurable via `PORT`.
