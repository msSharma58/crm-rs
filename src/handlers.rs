use axum::{
    extract::{Path, State},
    http::StatusCode,
    response::IntoResponse,
    Json,
};
use serde_json::json;

use crate::db::Db;
use crate::models::{NewContact, UpdateContact};

/// Simple health check used to verify the service is up.
pub async fn health() -> impl IntoResponse {
    Json(json!({ "status": "ok" }))
}

pub async fn list_contacts(State(db): State<Db>) -> impl IntoResponse {
    match db.list_contacts() {
        Ok(contacts) => Json(contacts).into_response(),
        Err(e) => internal_error(e),
    }
}

pub async fn get_contact(State(db): State<Db>, Path(id): Path<i64>) -> impl IntoResponse {
    match db.get_contact(id) {
        Ok(Some(contact)) => Json(contact).into_response(),
        Ok(None) => not_found(),
        Err(e) => internal_error(e),
    }
}

pub async fn create_contact(
    State(db): State<Db>,
    Json(payload): Json<NewContact>,
) -> impl IntoResponse {
    if payload.name.trim().is_empty() || payload.email.trim().is_empty() {
        return (
            StatusCode::UNPROCESSABLE_ENTITY,
            Json(json!({ "error": "name and email are required" })),
        )
            .into_response();
    }
    match db.create_contact(payload) {
        Ok(contact) => (StatusCode::CREATED, Json(contact)).into_response(),
        Err(e) => internal_error(e),
    }
}

pub async fn update_contact(
    State(db): State<Db>,
    Path(id): Path<i64>,
    Json(payload): Json<UpdateContact>,
) -> impl IntoResponse {
    match db.update_contact(id, payload) {
        Ok(Some(contact)) => Json(contact).into_response(),
        Ok(None) => not_found(),
        Err(e) => internal_error(e),
    }
}

pub async fn delete_contact(State(db): State<Db>, Path(id): Path<i64>) -> impl IntoResponse {
    match db.delete_contact(id) {
        Ok(true) => StatusCode::NO_CONTENT.into_response(),
        Ok(false) => not_found(),
        Err(e) => internal_error(e),
    }
}

fn not_found() -> axum::response::Response {
    (
        StatusCode::NOT_FOUND,
        Json(json!({ "error": "contact not found" })),
    )
        .into_response()
}

fn internal_error(e: rusqlite::Error) -> axum::response::Response {
    tracing::error!("database error: {e}");
    (
        StatusCode::INTERNAL_SERVER_ERROR,
        Json(json!({ "error": "internal server error" })),
    )
        .into_response()
}
