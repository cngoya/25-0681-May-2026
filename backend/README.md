# Bloom & Petal Haven — Backend (PHP + MySQL)

A small PHP API that stores sign-ups from the website's "Create Account" form
into a MySQL database. Built for a stock **XAMPP** install on macOS.

## Files

| File                 | Purpose                                                        |
|----------------------|---------------------------------------------------------------|
| `../sql/schema.sql`  | Creates the `bloom_petal` database and `users` table.         |
| `config.php`         | DB credentials (XAMPP defaults; override with env vars).      |
| `db.php`             | Returns a shared PDO connection.                              |
| `helpers.php`        | JSON responses + server-side validators.                     |
| `signup.php`         | `POST` endpoint — validates and saves a sign-up.             |

## Setup

### 1. Start MySQL & Apache
Open the **XAMPP** app (manager-osx) and start **MySQL** (and Apache if you'll
serve the site through it). MySQL must be running before the API works.

### 2. Create the database
Either via the command line:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < sql/schema.sql
```

…or paste the contents of `sql/schema.sql` into **phpMyAdmin → SQL**
(http://localhost/phpmyadmin).

### 3. Serve the site (so the form can reach the PHP)
The page must be served over `http://`, not opened as a `file://` path.
Quickest option — PHP's built-in server from the project folder:

```bash
cd ~/Documents/Bloom/bloom-petal-website
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000
```

Then open **http://localhost:8000**.

(Or copy the project into `/Applications/XAMPP/xamppfiles/htdocs/` and use
`http://localhost/bloom-petal-website/`.)

## The API

### `POST /backend/signup.php`
Accepts a form-encoded body **or** a JSON body with: `name`, `email`,
`phone`, `gender` (`female` | `male` | `other` | `prefer-not`).

Responses:

| Status | Body                                              | Meaning                       |
|--------|---------------------------------------------------|-------------------------------|
| 201    | `{ ok: true, message, name, email }`              | Saved.                        |
| 400    | `{ ok: false, errors: { field: message } }`       | Validation failed.            |
| 409    | `{ ok: false, message }`                           | Email already registered.     |
| 405    | `{ ok: false, message }`                           | Wrong HTTP method.            |
| 500    | `{ ok: false, message }`                           | Server / database error.      |

Quick test with curl:

```bash
curl -X POST http://localhost:8000/backend/signup.php \
  -H 'Content-Type: application/json' \
  -d '{"name":"Cleon Mwangi","email":"cleon@gmail.com","phone":"0712345678","gender":"male"}'
```

## Security notes
- All inserts use **prepared statements** (no SQL injection).
- Input is validated on the server, not just in the browser.
- `config.php` ships with XAMPP defaults (`root` / empty password). On a real
  host, set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` env vars instead of
  hard-coding credentials.
