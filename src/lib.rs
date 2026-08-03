pub mod db;
pub mod handlers;
pub mod models;

use axum::{routing::get, Router};
use tower_http::cors::CorsLayer;
use tower_http::services::ServeDir;

use crate::db::Db;

/// Build the Axum application router with all API routes and static file
/// serving wired up. Kept separate from `main` so integration tests can spin
/// up the same app against an in-memory database.
pub fn build_app(db: Db) -> Router {
    let api = Router::new()
        .route("/health", get(handlers::health))
        .route(
            "/contacts",
            get(handlers::list_contacts).post(handlers::create_contact),
        )
        .route(
            "/contacts/:id",
            get(handlers::get_contact)
                .put(handlers::update_contact)
                .delete(handlers::delete_contact),
        )
        .with_state(db);

    Router::new()
        .nest("/api", api)
        .fallback_service(ServeDir::new("static"))
        .layer(CorsLayer::permissive())
}
