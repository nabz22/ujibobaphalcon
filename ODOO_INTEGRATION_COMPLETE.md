# Odoo Integration dengan Phalcon

Dokumentasi lengkap integrasi antara aplikasi Phalcon dengan Odoo ERP System.

## 🚀 Fitur Utama

- ✅ Sync data Notes ke Odoo (mail.activity)
- ✅ Bidirectional synchronization
- ✅ Auto-sync capability
- ✅ Comprehensive error handling & logging
- ✅ REST API untuk integrasi
- ✅ Tracking sync status

## 📋 Prerequisites

- Docker & Docker Compose
- Phalcon Framework 4.0+
- Odoo 16 (atau versi lebih baru)
- MySQL 8.0+
- PostgreSQL 13+ (untuk Odoo)

## 🔧 Setup

### 1. Environment Variables

Buat file `.env` di root project:

```env
# Odoo Configuration
ODOO_ENABLED=true
ODOO_URL=http://odoo:8069
ODOO_DB=odoo
ODOO_USER=admin
ODOO_PASSWORD=admin
ODOO_AUTO_SYNC=false
```

### 2. Docker Setup

```bash
# Build dan jalankan containers
docker-compose up -d

# Cek status containers
docker ps

# Verify Odoo is running
curl http://localhost:8069
```

Akses:
- **Phalcon API**: http://localhost:8092
- **Odoo**: http://localhost:8069
- **PHPMyAdmin**: http://localhost:8081

### 3. Database Migration

Migrasi akan berjalan otomatis saat container start. Untuk manual:

```bash
docker-compose exec db mysql -u root -proot catatanharian < docker/mysql/02-odoo-sync.sql
```

Tables yang dibuat:
- `odoo_sync` - Tracking sync status
- `odoo_logs` - Audit log

## 📡 API Endpoints

### Odoo Health Check

```bash
GET /api/odoo/health
```

Response:
```json
{
  "status": "healthy",
  "timestamp": "2026-01-21 10:30:00",
  "services": {
    "phalcon": "ok",
    "odoo": "connected"
  }
}
```

### Read Data dari Odoo

```bash
GET /api/odoo/read?model=sale.order&fields=id,name,state&limit=10
```

Parameters:
- `model` (required) - Model Odoo (sale.order, res.partner, etc)
- `fields` - Comma-separated fields (default: id,name)
- `limit` - Hasil maksimal (default: 10)

Response:
```json
{
  "status": true,
  "source": "odoo",
  "model": "sale.order",
  "data": [...],
  "count": 5
}
```

### Create Data di Odoo

```bash
POST /api/odoo/create

Content-Type: application/x-www-form-urlencoded
model=sale.order&data={"name":"SO001","partner_id":1}
```

Response:
```json
{
  "status": true,
  "odoo_id": 123,
  "model": "sale.order"
}
```

### Sync Notes ke Odoo

```bash
POST /api/odoo/sync-notes
```

Response:
```json
{
  "status": true,
  "synced_count": 10,
  "failed_count": 2,
  "total": 12,
  "details": [
    {
      "note_id": 1,
      "odoo_id": 456,
      "status": "synced"
    },
    {
      "note_id": 2,
      "status": "failed",
      "error": "Partner not found"
    }
  ]
}
```

### Get Sync Status

```bash
GET /api/odoo/sync-status
```

Response:
```json
{
  "status": true,
  "pending": 5,
  "synced": 45,
  "failed": 3,
  "total": 53
}
```

### Get Failed Syncs

```bash
GET /api/odoo/failed-syncs
```

Response:
```json
{
  "status": true,
  "count": 3,
  "data": [
    {
      "id": 1,
      "entity_type": "notes",
      "entity_id": 5,
      "error_message": "Invalid date format",
      "updated_at": "2026-01-21 09:15:00"
    }
  ]
}
```

## 🔄 Synchronization

### Manual Sync

```bash
# Sync all notes to Odoo
curl -X POST http://localhost:8092/api/odoo/sync-notes

# Check sync status
curl http://localhost:8092/api/odoo/sync-status
```

### Auto Sync (Background Job)

Enable auto-sync di `app/config/odoo.php`:

```php
'sync' => [
    'auto_sync'      => true,
    'sync_interval'  => 300,  // 5 minutes
    'batch_size'     => 100,
]
```

Implementasi cron job:

```bash
*/5 * * * * curl -s http://localhost:8092/api/odoo/sync-notes >> /var/log/odoo-sync.log 2>&1
```

## 📊 Data Models Mapping

### Notes → Odoo Activity

```
Phalcon:                Odoo:
judul        →          summary
isi          →          note
tanggal      →          activity_date
created_at   →          create_date
```

## 🛠️ OdooService Class

### Methods

```php
// Constructor
new OdooService($config)

// Read data
read($model, $fields, $domain, $limit)

// Create data
create($model, $values)

// Update data
write($model, $ids, $values)

// Delete data
delete($model, $ids)
```

### Example Usage

```php
$odoo = new OdooService([
    'url'      => 'http://odoo:8069',
    'database' => 'odoo',
    'username' => 'admin',
    'password' => 'admin'
]);

// Get all sale orders
$orders = $odoo->read('sale.order', ['id', 'name', 'amount_total']);

// Create new contact
$contact_id = $odoo->create('res.partner', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update sale order
$odoo->write('sale.order', [123], [
    'state' => 'confirmed'
]);
```

## 📝 OdooSync Model

Tracking table untuk sync operations:

```php
// Get sync status
$sync = OdooSync::getSyncStatus('notes', 1);

// Mark as synced
$sync->markSynced($odooId);

// Mark as failed
$sync->markSynced(null, 'Error message');
```

## 🐛 Troubleshooting

### Odoo Connection Error

```
Error: Failed to authenticate with Odoo
```

**Solution:**
- Verify Odoo is running: `docker-compose ps`
- Check credentials di `.env`
- Verify database name and user

### Sync Failed

Check `odoo_logs` table:

```sql
SELECT * FROM odoo_logs WHERE status = 'failed' ORDER BY created_at DESC;
```

Or via API:

```bash
curl http://localhost:8092/api/odoo/failed-syncs
```

### Network Issue (Host to Container)

Jika Odoo di host machine (bukan Docker):

```php
// Use host.docker.internal (Windows/Mac)
'url' => 'http://host.docker.internal:8069'

// Or use Docker network IP (Linux)
'url' => 'http://172.17.0.1:8069'
```

## 📚 Additional Resources

- [Odoo JSON-RPC API](https://www.odoo.com/documentation/16.0/reference/external_api.html)
- [Phalcon Framework](https://docs.phalconphp.com/)
- [Docker Odoo Images](https://hub.docker.com/_/odoo)

## 📄 License

MIT License

## 👥 Support

Untuk issues atau pertanyaan, silahkan buka issue di repository ini.
