# Deploying Bloom & Petal Haven to InfinityFree (free PHP + MySQL)

This puts the **whole app** online — storefront, register, cart/checkout,
contact form, **and the admin dashboard** — on a free public URL.

I've prepared an upload-ready bundle:
**`~/Documents/Bloom/bloom-petal-deploy.zip`**
and a host-ready database file inside it: **`sql/deploy_hosted.sql`**.

Steps marked **[you]** need your login/clicks; everything else is already done.

---

## 1. Create the hosting account **[you]**
1. Go to **https://infinityfree.com** → **Sign Up** (free). Verify your email.
2. **Create Account** → choose a free subdomain
   (e.g. `bloompetal.infinityfreeapp.com`) or attach your own domain.
3. Wait a few minutes for it to activate, then open the **Control Panel**.

## 2. Create the MySQL database **[you]**
1. In the control panel open **MySQL Databases**.
2. Create a database (e.g. name it `bloompetal`).
3. Copy the four values it shows — you'll need them next:
   - **DB Host** (looks like `sqlXXX.infinityfree.com`)
   - **DB Name** (looks like `epiz_XXXXXXXX_bloompetal`)
   - **DB User** (looks like `epiz_XXXXXXXX`)
   - **DB Password** (your account password)

## 3. Put your credentials in `backend/.env` **[you]**
1. Unzip `bloom-petal-deploy.zip`.
2. Open `backend/.env` and replace the placeholders with the 4 values above:
   ```
   DB_HOST=sqlXXX.infinityfree.com
   DB_PORT=3306
   DB_NAME=epiz_XXXXXXXX_bloompetal
   DB_USER=epiz_XXXXXXXX
   DB_PASS=your-db-password
   ADMIN_PASSWORD=BloomAdmin2026     ← change this to your own
   ```
3. Save.

## 4. Upload the files **[you]**
Use FTP (most reliable). Get your **FTP details** from the control panel
(**FTP Accounts**), then in **FileZilla** (free):
1. Connect with the FTP host / username / password.
2. Open the **`htdocs`** folder on the server.
3. Upload **everything inside the unzipped bundle** into `htdocs` so you get:
   ```
   htdocs/index.html
   htdocs/script.js
   htdocs/styles.css
   htdocs/backend/...        (all the .php files + admin/ + your .env)
   htdocs/sql/deploy_hosted.sql
   ```
   (The online File Manager also works if you prefer not to install FileZilla.)

## 5. Import the database **[you]**
1. Control panel → **phpMyAdmin** (the host provides it — you do **not** upload
   your own).
2. Select your database on the left.
3. **Import** tab → choose `sql/deploy_hosted.sql` → **Go**.
   (Or paste its contents into the **SQL** tab and run.)
   You should see 8 tables created and the catalogue/seed data loaded.

## 6. Set the PHP version **[you]**
Control panel → **Select PHP Version** → pick **PHP 8.0 or newer**
(the code uses PHP 8 features). Save.

## 7. Test it 🎉
- Visit **`http://your-subdomain.infinityfreeapp.com`** → register, add to cart,
  checkout, send a contact message.
- Admin: **`http://your-subdomain.infinityfreeapp.com/admin/`**
  → log in with your `ADMIN_PASSWORD`

---

## Notes
- The site **must** be opened at the InfinityFree URL (it runs PHP). The same
  files still run locally via `php -S localhost:8000`.
- `backend/.env` holds your live DB password. It is **not** committed to git.
- The DB host is **not** `localhost` on InfinityFree — always use the
  `sqlXXX.infinityfree.com` value from the panel.
- Free hosting sleeps/limits under heavy load; fine for a demo or coursework.
- To manage data later, use the host's **phpMyAdmin** (your local
  `setup-phpmyadmin.sh` is for your machine only).
