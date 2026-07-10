#!/bin/bash
# ───────────────────────────────────────────────
#  Set up phpMyAdmin for the bloom_petal database.
#
#  Copies the phpMyAdmin that ships with XAMPP into ./phpmyadmin and writes
#  a config pointing at the standalone MySQL on 127.0.0.1:3306. The DB
#  password is read from backend/.env (so it is never committed).
#  The phpmyadmin/ folder is gitignored; run this once after cloning.
#
#  Usage:  ./setup-phpmyadmin.sh
# ───────────────────────────────────────────────
set -e
cd "$(dirname "$0")"

SRC="/Applications/XAMPP/xamppfiles/phpmyadmin"
DEST="./phpmyadmin"

if [ ! -d "$SRC" ]; then
    echo "✗ Could not find phpMyAdmin at $SRC (is XAMPP installed?)"
    exit 1
fi

# Read DB_PASS from backend/.env
DB_PASS=$(grep -E '^DB_PASS=' backend/.env | head -1 | cut -d= -f2-)

echo "→ Copying phpMyAdmin from XAMPP…"
rm -rf "$DEST"
cp -R "$SRC" "$DEST"
mkdir -p "$DEST/tmp" && chmod 777 "$DEST/tmp"

SECRET=$(openssl rand -hex 16)

echo "→ Writing config.inc.php…"
cat > "$DEST/config.inc.php" <<PHP
<?php
declare(strict_types=1);
\$cfg['blowfish_secret'] = '${SECRET}bloompetal';
\$i = 0;
\$i++;
\$cfg['Servers'][\$i]['auth_type']    = 'config';
\$cfg['Servers'][\$i]['user']         = 'root';
\$cfg['Servers'][\$i]['password']     = '${DB_PASS}';
\$cfg['Servers'][\$i]['host']         = '127.0.0.1';
\$cfg['Servers'][\$i]['port']         = '3306';
\$cfg['Servers'][\$i]['connect_type'] = 'tcp';
\$cfg['Servers'][\$i]['compress']     = false;
\$cfg['Servers'][\$i]['AllowNoPassword'] = false;
\$cfg['Servers'][\$i]['only_db'] = ['bloom_petal'];
\$cfg['TempDir']       = __DIR__ . '/tmp';
\$cfg['ServerDefault'] = 1;
PHP

echo "✓ phpMyAdmin ready."
echo "  Start the site:  /Applications/XAMPP/xamppfiles/bin/php -S localhost:8000"
echo "  Then open:       http://localhost:8000/phpmyadmin/"
