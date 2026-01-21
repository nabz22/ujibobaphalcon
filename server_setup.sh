#!/bin/bash
# Server setup script untuk deployment aplikasi ujicoba

set -e

DEPLOY_BASE="/home/fdx/dockerizer"
APP_NAME="ujicoba"
APP_PATH="$DEPLOY_BASE/$APP_NAME"
GIT_REPO_URL="https://github.com/Nazmi/ujicoba.git"  # Update dengan URL repo yang benar

echo "=========================================="
echo "SETUP DEPLOYMENT UJICOBA"
echo "=========================================="

# 1. Buat direktori
echo "[1] Membuat direktori..."
mkdir -p "$APP_PATH"
cd "$APP_PATH"

# 2. Cek git
echo "[2] Cek git dan docker..."
git --version
docker --version
docker-compose --version

# 3. Initialize git repository jika belum ada
echo "[3] Setup git repository..."
if [ -d .git ]; then
    echo "  Git repo sudah ada, update..."
    git pull origin main || echo "  Pull gagal, mungkin belum ada remote"
else
    echo "  Inisialisasi git repo baru..."
    git init
    git config user.email "deploy@server"
    git config user.name "Deploy User"
    # Add remote (uncomment jika sudah ada repo hosting)
    # git remote add origin $GIT_REPO_URL
fi

# 4. Cek file penting
echo "[4] Verifikasi file..."
ls -la

echo ""
echo "=========================================="
echo "Setup selesai!"
echo "Direktori: $APP_PATH"
echo "Next step: Clone/push file aplikasi"
echo "=========================================="
