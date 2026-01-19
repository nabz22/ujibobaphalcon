# Dokumentasi Integrasi Phalcon - Odoo API

## Daftar Endpoint API

### 1. Get Notes dari Database Lokal Phalcon
**Endpoint:** `GET /api/notes`

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "title": "Judul Catatan",
      "content": "Isi catatan",
      "note_date": "2026-01-14",
      "created_at": "2026-01-14 10:30:00"
    }
  ]
}
```

### 2. Get Data dari Odoo API
**Endpoint:** `GET /api/odoo-notes`

**Response:**
```json
{
  "status": true,
  "source": "odoo_api",
  "data": [
    {
      "id": 1,
      "name": "Sale Order",
      "state": "draft",
      "create_date": ["2026-01-14", "10:30:00"]
    }
  ],
  "timestamp": "2026-01-14 10:30:00"
}
```

### 3. Sync Data dari Phalcon ke Odoo
**Endpoint:** `POST /api/sync-to-odoo`

**Response:**
```json
{
  "status": true,
  "synced_count": 5,
  "message": "Synced 5 notes to Odoo"
}
```

### 4. Health Check
**Endpoint:** `GET /api/health`

**Response:**
```json
{
  "status": true,
  "message": "Phalcon API is running",
  "services": {
    "phalcon_db": "connected",
    "odoo_api": "configured"
  }
}
```

---

## Konfigurasi Odoo Service

Edit file `app/library/OdooService.php` dan sesuaikan konfigurasi:

```php
$this->odooService = new OdooService([
    'url'      => 'http://odoo:8069',  // URL Odoo Anda
    'database' => 'odoo',              // Nama database Odoo
    'username' => 'admin',             // Username Odoo
    'password' => 'admin'              // Password Odoo
]);
```

---

## Menjalankan dengan Docker

### 1. Build dan start container
```bash
docker-compose up -d
```

### 2. Akses API
- **Phalcon API:** http://localhost:8080/api/notes
- **Odoo:** http://odoo:8069 (jika Odoo berjalan di Docker)

### 3. Cek status container
```bash
docker-compose ps
```

### 4. View logs
```bash
docker-compose logs -f phalcon-app
```

---

## Integrasi Odoo (Opsional)

Jika ingin menjalankan Odoo di Docker, uncomment bagian di `docker-compose.yml`:

```yaml
# odoo:
#   image: odoo:latest
#   ...
```

Kemudian jalankan:
```bash
docker-compose up -d odoo
```

---

## Troubleshooting

### Error: "Failed to authenticate with Odoo"
- Pastikan URL Odoo benar
- Pastikan username dan password benar
- Pastikan database Odoo sudah ada

### Error: "Connection refused"
- Pastikan Odoo sudah running
- Pastikan network Docker sudah terhubung dengan benar
- Check logs: `docker-compose logs odoo`

### Phalcon tidak bisa connect ke Odoo
- Jika Odoo eksternal, gunakan IP address Odoo yang bisa diakses
- Jika dalam Docker, pastikan kedua container dalam network yang sama

---

## File-File Penting

- **OdooService:** `/app/library/OdooService.php` - Service untuk komunikasi dengan Odoo
- **ApiController:** `/app/controllers/ApiController.php` - Controller dengan endpoint API
- **Docker Compose:** `/docker-compose.yml` - Konfigurasi container
