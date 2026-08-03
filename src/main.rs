use std::net::SocketAddr;

use crm_rs::{build_app, db::Db};

#[tokio::main]
async fn main() {
    tracing_subscriber::fmt()
        .with_env_filter(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "info,tower_http=info".into()),
        )
        .init();

    let db_path = std::env::var("DATABASE_PATH").unwrap_or_else(|_| "crm.db".to_string());
    let db = Db::open(&db_path).expect("failed to open database");

    let app = build_app(db);

    let port: u16 = std::env::var("PORT")
        .ok()
        .and_then(|p| p.parse().ok())
        .unwrap_or(3000);
    let addr = SocketAddr::from(([0, 0, 0, 0], port));

    let listener = tokio::net::TcpListener::bind(addr)
        .await
        .expect("failed to bind address");

    tracing::info!("crm-rs listening on http://{addr} (db: {db_path})");

    axum::serve(listener, app).await.expect("server error");
}
