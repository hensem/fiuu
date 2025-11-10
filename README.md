# Application Bulk Submission to Channel Partner

Contain Docker image that is ready to be used in a Docker container.

This Laravel + Vue (Vite) application allows users to submit multiple applications at once to a partner channel, following strict data and editing rules.

---

## Setup Guide (Local Development via Docker)

### 1. Clone the repository

Open PowerShell:

```
mkdir D:\fiuu
cd D:\fiuu
git clone https://github.com/hensem/fiuu.git
```

---

### 2. Build and start Docker containers

```
cd fiuu\docker
docker compose build
docker compose up -d
```

---

### 3. Recreate the MySQL volume

```
docker compose down -v
Remove-Item -Recurse -Force .\mysql_data
docker compose build
docker compose up -d
```

---

### 4. Restore database from the dump file

```
cmd /c "docker exec -i laravel_db mysql -u root -proot laravel < ..\mysql_dump.sql"
```

If there’s any error, it’s likely because MySQL initialization just completed but hasn’t yet switched to “running mode.”  
Wait a few more minutes and try again.

To check MySQL status:

```
docker logs laravel_db
```

If you see:
```
[Server] /usr/sbin/mysqld: ready for connections.
```
then the database is ready.

---

### 5. Install Laravel backend dependencies

```
docker exec -it laravel_app bash
```

Then inside the container:

```
cd /var/www/html
composer install
php artisan key:generate
php artisan config:clear
```

---

### 6. Build frontend (Vite)

Still inside the container:

```
npm install
npm run build
```

This generates the frontend assets required by Laravel in:
```
/var/www/html/public/build/
```

If you skip this step, Laravel will throw:
```
Illuminate\Foundation\ViteManifestNotFoundException
```

---

### 7. Create the cache table, Clear cache and fix permissions

```
php artisan cache:table
php artisan migrate
php artisan config:clear
php artisan cache:clear
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Then exit the container:

```
exit
```

---

### 8. Visit the site

Open in your browser:

http://localhost:8080

---

## Default Login Credentials

Email: admin@example.com  
Password: secret

---

## Application Rules

- Login with email + password.
- Any logged-in user can create another user.
- Users cannot be deleted or have a status.
- Users cannot change passwords. Passwords are encoded and immutable (even in DB).
- Applications and attachments are editable only when `application.status = draft`.
- Submissions (partner + applications) are editable only when `submission.status = draft`.
- Submitting a submission:
  - Sets `submission.status = submitted`
  - Fills `submitted_by` and `submitted_at`
  - Sets all related `applications.status = submitted`
- A submission cannot be submitted if it has no attached applications.
- Every field update (in any table) is logged (DB-level, no UI).
- No record in any table can be deleted — unused data remains unchanged.

---

## Other Files

ERD.pdf — Entity Relationship Diagram for the system  
required_APIs.pdf — List of required API endpoints

---

## Technology Stack

- Backend: PHP 8.2 (Laravel)
- Frontend: Vue + Vite
- Database: MySQL 8.0
- Web Server: Nginx
- Containerization: Docker Compose

---

## Docker Services Overview

| Service       | Description                                  | Port |
|----------------|----------------------------------------------|------|
| laravel_app    | PHP-FPM (Laravel Backend + Node.js + Composer) | 9000 |
| laravel_web    | Nginx Web Server                             | 8080 |
| laravel_db     | MySQL Database                               | 3307 |

---

## Troubleshooting Tips

**ViteManifestNotFoundException**  
Run inside `laravel_app`:
```
npm install
npm run build
```

**MySQL permission errors**  
```
docker exec -it laravel_db bash -c "chown -R mysql:mysql /var/lib/mysql"
docker restart laravel_db
```

**Check MySQL is ready**
```
docker logs laravel_db
```
Look for:
```
[Server] /usr/sbin/mysqld: ready for connections.
```

---

Author: [@ahmad kamal sulong](https://github.com/hensem)  
License: Private / Internal Project
