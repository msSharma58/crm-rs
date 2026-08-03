use serde::{Deserialize, Serialize};

/// A CRM contact / lead record.
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Contact {
    pub id: i64,
    pub name: String,
    pub email: String,
    pub company: Option<String>,
    pub phone: Option<String>,
    pub status: String,
    pub notes: Option<String>,
    pub created_at: String,
}

/// Payload used when creating a new contact.
#[derive(Debug, Deserialize)]
pub struct NewContact {
    pub name: String,
    pub email: String,
    pub company: Option<String>,
    pub phone: Option<String>,
    pub status: Option<String>,
    pub notes: Option<String>,
}

/// Payload used when updating an existing contact. All fields optional.
#[derive(Debug, Deserialize)]
pub struct UpdateContact {
    pub name: Option<String>,
    pub email: Option<String>,
    pub company: Option<String>,
    pub phone: Option<String>,
    pub status: Option<String>,
    pub notes: Option<String>,
}
