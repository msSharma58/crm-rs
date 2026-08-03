use rusqlite::{Connection, Row};
use std::sync::{Arc, Mutex};

use crate::models::{Contact, NewContact, UpdateContact};

/// Thread-safe database handle shared across request handlers.
#[derive(Clone)]
pub struct Db {
    conn: Arc<Mutex<Connection>>,
}

impl Db {
    /// Open a database at `path` (use ":memory:" for an ephemeral store) and
    /// ensure the schema exists.
    pub fn open(path: &str) -> rusqlite::Result<Self> {
        let conn = Connection::open(path)?;
        let db = Db {
            conn: Arc::new(Mutex::new(conn)),
        };
        db.init_schema()?;
        Ok(db)
    }

    fn init_schema(&self) -> rusqlite::Result<()> {
        let conn = self.conn.lock().unwrap();
        conn.execute_batch(
            "CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                company TEXT,
                phone TEXT,
                status TEXT NOT NULL DEFAULT 'lead',
                notes TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );",
        )?;
        Ok(())
    }

    pub fn list_contacts(&self) -> rusqlite::Result<Vec<Contact>> {
        let conn = self.conn.lock().unwrap();
        let mut stmt = conn.prepare(
            "SELECT id, name, email, company, phone, status, notes, created_at
             FROM contacts ORDER BY id DESC",
        )?;
        let rows = stmt.query_map([], row_to_contact)?;
        rows.collect()
    }

    pub fn get_contact(&self, id: i64) -> rusqlite::Result<Option<Contact>> {
        let conn = self.conn.lock().unwrap();
        let mut stmt = conn.prepare(
            "SELECT id, name, email, company, phone, status, notes, created_at
             FROM contacts WHERE id = ?1",
        )?;
        let mut rows = stmt.query_map([id], row_to_contact)?;
        match rows.next() {
            Some(r) => Ok(Some(r?)),
            None => Ok(None),
        }
    }

    pub fn create_contact(&self, input: NewContact) -> rusqlite::Result<Contact> {
        let status = input.status.unwrap_or_else(|| "lead".to_string());
        let conn = self.conn.lock().unwrap();
        conn.execute(
            "INSERT INTO contacts (name, email, company, phone, status, notes)
             VALUES (?1, ?2, ?3, ?4, ?5, ?6)",
            rusqlite::params![
                input.name,
                input.email,
                input.company,
                input.phone,
                status,
                input.notes,
            ],
        )?;
        let id = conn.last_insert_rowid();
        let mut stmt = conn.prepare(
            "SELECT id, name, email, company, phone, status, notes, created_at
             FROM contacts WHERE id = ?1",
        )?;
        stmt.query_row([id], row_to_contact)
    }

    pub fn update_contact(
        &self,
        id: i64,
        input: UpdateContact,
    ) -> rusqlite::Result<Option<Contact>> {
        {
            let conn = self.conn.lock().unwrap();
            let existing_count: i64 =
                conn.query_row("SELECT COUNT(*) FROM contacts WHERE id = ?1", [id], |r| {
                    r.get(0)
                })?;
            if existing_count == 0 {
                return Ok(None);
            }
            conn.execute(
                "UPDATE contacts SET
                    name = COALESCE(?2, name),
                    email = COALESCE(?3, email),
                    company = COALESCE(?4, company),
                    phone = COALESCE(?5, phone),
                    status = COALESCE(?6, status),
                    notes = COALESCE(?7, notes)
                 WHERE id = ?1",
                rusqlite::params![
                    id,
                    input.name,
                    input.email,
                    input.company,
                    input.phone,
                    input.status,
                    input.notes,
                ],
            )?;
        }
        self.get_contact(id)
    }

    pub fn delete_contact(&self, id: i64) -> rusqlite::Result<bool> {
        let conn = self.conn.lock().unwrap();
        let affected = conn.execute("DELETE FROM contacts WHERE id = ?1", [id])?;
        Ok(affected > 0)
    }
}

fn row_to_contact(row: &Row) -> rusqlite::Result<Contact> {
    Ok(Contact {
        id: row.get(0)?,
        name: row.get(1)?,
        email: row.get(2)?,
        company: row.get(3)?,
        phone: row.get(4)?,
        status: row.get(5)?,
        notes: row.get(6)?,
        created_at: row.get(7)?,
    })
}
