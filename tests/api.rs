use std::net::SocketAddr;

use crm_rs::{build_app, db::Db};

/// Spawn the app on an ephemeral port backed by an in-memory database and
/// return the base URL.
async fn spawn_app() -> String {
    let db = Db::open(":memory:").expect("open in-memory db");
    let app = build_app(db);

    let listener = tokio::net::TcpListener::bind(SocketAddr::from(([127, 0, 0, 1], 0)))
        .await
        .unwrap();
    let addr = listener.local_addr().unwrap();

    tokio::spawn(async move {
        axum::serve(listener, app).await.unwrap();
    });

    format!("http://{addr}")
}

#[tokio::test]
async fn health_endpoint_works() {
    let base = spawn_app().await;
    let resp = reqwest::get(format!("{base}/api/health")).await.unwrap();
    assert_eq!(resp.status(), 200);
    let body: serde_json::Value = resp.json().await.unwrap();
    assert_eq!(body["status"], "ok");
}

#[tokio::test]
async fn create_and_list_contact() {
    let base = spawn_app().await;
    let client = reqwest::Client::new();

    // Initially empty.
    let list: serde_json::Value = client
        .get(format!("{base}/api/contacts"))
        .send()
        .await
        .unwrap()
        .json()
        .await
        .unwrap();
    assert_eq!(list.as_array().unwrap().len(), 0);

    // Create a contact.
    let created = client
        .post(format!("{base}/api/contacts"))
        .json(&serde_json::json!({
            "name": "Ada Lovelace",
            "email": "ada@example.com",
            "company": "Analytical Engines",
            "status": "lead"
        }))
        .send()
        .await
        .unwrap();
    assert_eq!(created.status(), 201);
    let created: serde_json::Value = created.json().await.unwrap();
    assert_eq!(created["name"], "Ada Lovelace");
    let id = created["id"].as_i64().unwrap();

    // List now has one.
    let list: serde_json::Value = client
        .get(format!("{base}/api/contacts"))
        .send()
        .await
        .unwrap()
        .json()
        .await
        .unwrap();
    assert_eq!(list.as_array().unwrap().len(), 1);

    // Fetch it by id.
    let one: serde_json::Value = client
        .get(format!("{base}/api/contacts/{id}"))
        .send()
        .await
        .unwrap()
        .json()
        .await
        .unwrap();
    assert_eq!(one["email"], "ada@example.com");
}

#[tokio::test]
async fn update_and_delete_contact() {
    let base = spawn_app().await;
    let client = reqwest::Client::new();

    let created: serde_json::Value = client
        .post(format!("{base}/api/contacts"))
        .json(&serde_json::json!({ "name": "Grace Hopper", "email": "grace@navy.mil" }))
        .send()
        .await
        .unwrap()
        .json()
        .await
        .unwrap();
    let id = created["id"].as_i64().unwrap();

    // Update status to customer.
    let updated: serde_json::Value = client
        .put(format!("{base}/api/contacts/{id}"))
        .json(&serde_json::json!({ "status": "customer" }))
        .send()
        .await
        .unwrap()
        .json()
        .await
        .unwrap();
    assert_eq!(updated["status"], "customer");
    assert_eq!(updated["name"], "Grace Hopper");

    // Delete it.
    let del = client
        .delete(format!("{base}/api/contacts/{id}"))
        .send()
        .await
        .unwrap();
    assert_eq!(del.status(), 204);

    // Now 404.
    let missing = client
        .get(format!("{base}/api/contacts/{id}"))
        .send()
        .await
        .unwrap();
    assert_eq!(missing.status(), 404);
}

#[tokio::test]
async fn create_requires_name_and_email() {
    let base = spawn_app().await;
    let client = reqwest::Client::new();

    let resp = client
        .post(format!("{base}/api/contacts"))
        .json(&serde_json::json!({ "name": "", "email": "" }))
        .send()
        .await
        .unwrap();
    assert_eq!(resp.status(), 422);
}
