# MiniBox: a tiny personal drive in PHP

MiniBox is a very small personal file storage ("drive"). You sign up with an email and a
password, then upload files (with an optional description), see your list of files, download
or delete them. Every user sees only their own files.

It is written in plain object-oriented PHP (8.2+) without a framework. The database is PostgreSQL,
and uploaded files are saved in a folder on the server. [Composer](https://getcomposer.org)
installs the only library, [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) (reads `.env`),
and loads our classes (`App\Foo` lives in `src/Foo.php`).

## How the code is organised

```
public/index.php              every request starts here
public/css/app.css            the stylesheet
public/too-large.html         page nginx shows for files over 32 MB
src/App.php                   creates the objects, lists the routes, checks CSRF, shows error pages
src/Router.php                tiny router: "GET /files/(\d+)/download" -> function
src/Config.php                reads settings from the environment / .env
src/Database.php              PDO connection to PostgreSQL
src/Auth.php                  who is logged in (the session stores the user id)
src/Csrf.php                  secret token for every form
src/Storage.php               saves, sends and deletes files in the upload folder
src/View.php                  renders templates; escaping, flash messages, formatting, icons
src/Models/Model.php          base class: gives models the database
src/Models/User.php           users: find, create, check password
src/Models/File.php           files: list / find / create / delete, always for one user
src/Exceptions/HttpException.php      "stop and show a 404 / 403 page"
src/Exceptions/RedirectException.php  "stop and go to another page"
src/Controllers/AuthController.php    sign up, log in, log out
src/Controllers/FileController.php    list, upload, download, delete
src/Controllers/HealthController.php  GET /health
views/                        HTML templates (layout, index, login, register, error)
bin/init-db.php               creates the tables
database/schema.sql           the tables: users, files
storage/uploads/              uploaded files (random names)
storage/sessions/             PHP session files (login and messages)
```

What happens on an upload (`POST /upload`):

1. `App` checks the form's CSRF token, then `Router` calls `FileController::upload()`.
2. `Auth::requireUser()` makes sure somebody is logged in (otherwise: go to `/login`).
3. The controller checks the file (chosen, not empty, not larger than `MAX_UPLOAD_MB`).
4. `Storage::save()` moves it into `storage/uploads/` under a random name.
5. `File::create()` saves the owner, original name, type and size in the database.
6. The browser is redirected back to `/`, which shows "Uploaded ...".

Security basics used in the code: passwords are stored with `password_hash()`, the session id
changes after login, every form has a CSRF token, all output is escaped, SQL uses placeholders,
and a file that belongs to somebody else is simply "not found" (404).

### Pages

| Method | URL | What it does |
|---|---|---|
| GET | `/` | Upload form and your files (visitors are sent to `/login`) |
| POST | `/upload` | Upload a file |
| GET | `/files/{id}/download` | Download a file |
| POST | `/files/{id}/delete` | Delete a file |
| GET, POST | `/register` | Sign up |
| GET, POST | `/login` | Log in |
| POST | `/logout` | Log out |
| GET | `/health` | `{"status":"ok","db":"ok","hostname":"..."}` (500 when the database is not reachable) |

The footer of every page shows the server name, the database host and where files are stored.

## Run with Docker

Docker Desktop must be running (Windows and macOS).

```bash
docker compose up --build
```

Open http://localhost:8080 and sign up. Docker starts four containers:

| Container | What it is |
|---|---|
| `web` | nginx. Serves `public/` and sends PHP requests to `app`. Config: `docker/nginx.conf` |
| `app` | PHP-FPM with the code. On start it creates the tables (`php bin/init-db.php`) |
| `db` | PostgreSQL 16 |
| `adminer` | Web UI for the database |

**Adminer** is at http://localhost:8081. Log in with System `PostgreSQL`, Server `db`,
Username `minibox`, Password `secret`, Database `minibox`.

Useful commands:

```bash
docker compose logs -f web app                        # nginx and PHP logs
docker compose exec db psql -U minibox                # SQL shell
docker compose down                                   # stop (data is kept)
docker compose down -v                                # stop and delete the database
```

Uploaded files appear in `storage/uploads/` of this folder (it is mounted into the `app`
container). `docker compose up` rebuilds the app image each time, so it always runs the current code.

Other host ports (when 8080, 5432 or 8081 are already taken):

```bash
APP_HOST_PORT=9080 DB_HOST_PORT=5433 ADMINER_HOST_PORT=9081 docker compose up -d            # macOS / Linux
$env:APP_HOST_PORT="9080"; $env:DB_HOST_PORT="5433"; $env:ADMINER_HOST_PORT="9081"; docker compose up -d   # Windows PowerShell
```

## Run without Docker

You need PHP 8.2+ ([XAMPP](https://www.apachefriends.org) works) with the extensions `pdo_pgsql`,
`mbstring`, `fileinfo`, [Composer](https://getcomposer.org/download/) and a PostgreSQL database
you can connect to. Windows: see
[Setting up a Windows computer](../README.md#setting-up-a-windows-computer).

1. Install the library and create the settings file:

   ```bash
   composer install
   cp .env.example .env
   ```

2. Open `.env` and enter your database: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.

3. Create the tables and start the app:

   ```bash
   php bin/init-db.php
   php -S localhost:8080 -t public -d upload_max_filesize=12M -d post_max_size=14M
   ```

4. Open http://localhost:8080 and sign up.

The `-d` options raise PHP's upload limits (without them PHP accepts only 2 MB).
If a page says "The database has no tables yet", run `php bin/init-db.php`.
If it says "Cannot connect to the database", check the `DB_*` values in `.env`.

## Configuration

Settings come from environment variables or from `.env` (environment variables win).

| Variable | Default | Meaning |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | PostgreSQL host |
| `DB_PORT` | `5432` | PostgreSQL port |
| `DB_NAME` | `minibox` | Database name |
| `DB_USER` | `minibox` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `UPLOAD_DIR` | `storage/uploads` | Where files are saved (relative to the project folder, or an absolute path such as `/srv/minibox` or `C:\minibox\uploads`) |
| `MAX_UPLOAD_MB` | `10` | Largest file the app accepts |
| `APP_DEBUG` | `false` | `true` shows error details on the error page |

Upload size is limited in three places. Keep them in this order:
`MAX_UPLOAD_MB` (10) < PHP `upload_max_filesize` / `post_max_size` (12M / 14M, `docker/php.ini`)
< nginx `client_max_body_size` (32M). Then files that are too big get a clear message from the app.

## Deploying to an Ubuntu server (nginx + PHP-FPM)

Tested with Ubuntu 24.04, which ships PHP 8.3. These commands run on the Linux server (connect
first, for example with `ssh ubuntu@<server-ip>`).

1. **Install the packages**

   ```bash
   sudo apt update
   sudo apt install -y nginx php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml \
       composer unzip git postgresql
   ```

2. **Create the database**

   ```bash
   sudo -u postgres psql -c "CREATE USER minibox WITH PASSWORD 'choose-a-password';"
   sudo -u postgres psql -c "CREATE DATABASE minibox OWNER minibox;"
   ```

3. **Copy the app and install the library**

   ```bash
   git clone <your-repo-url> /tmp/minibox-src
   sudo mkdir -p /var/www/minibox
   sudo cp -r /tmp/minibox-src/dropbox_php_oop/. /var/www/minibox/
   cd /var/www/minibox
   sudo composer install --no-dev --optimize-autoloader
   ```

4. **Configure**

   ```bash
   sudo cp .env.example .env
   sudo nano .env                 # set DB_PASSWORD (and DB_HOST if the database is elsewhere)
   php bin/init-db.php
   ```

5. **Permissions**: PHP-FPM runs as `www-data` and writes to `storage/`.

   ```bash
   sudo chown -R www-data:www-data storage
   sudo chmod 640 .env && sudo chown root:www-data .env
   ```

6. **PHP upload limits**: create `/etc/php/8.3/fpm/conf.d/99-minibox.ini`:

   ```ini
   upload_max_filesize = 12M
   post_max_size = 14M
   display_errors = Off
   ```

   ```bash
   sudo systemctl restart php8.3-fpm
   ```

7. **nginx**

   ```bash
   sudo cp deploy/nginx.conf /etc/nginx/sites-available/minibox
   sudo ln -s /etc/nginx/sites-available/minibox /etc/nginx/sites-enabled/minibox
   sudo rm -f /etc/nginx/sites-enabled/default
   sudo nginx -t && sudo systemctl reload nginx
   ```

8. **Firewall and check**

   ```bash
   sudo ufw allow 'Nginx HTTP'
   curl http://localhost/health
   ```

   Open `http://<server-ip>/` in the browser.

To update later: copy the new code, run `composer install --no-dev` and `php bin/init-db.php`,
then `sudo systemctl reload php8.3-fpm`.
