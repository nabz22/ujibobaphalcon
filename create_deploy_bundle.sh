#!/bin/bash

# Deploy script untuk ujicoba - jalankan ini di local machine
# Requirements: git, ssh, curl

DEPLOY_DIR="deployment_temp"
SERVER_USER="fdx"
SERVER_IP="192.168.0.73"
SERVER_PATH="/home/fdx/dockerizer/ujicoba"
GIT_REPO="https://github.com/nabz22/ujibobaphalcon.git"

echo "Creating deployment bundle..."

# Buat temporary directory
mkdir -p "$DEPLOY_DIR"
cd "$DEPLOY_DIR"

# Copy semua file dari git
git clone "$GIT_REPO" .

# Create tar archive
cd ..
tar -czf ujicoba_deploy.tar.gz "$DEPLOY_DIR"

echo "Deploy bundle created: ujicoba_deploy.tar.gz"
echo ""
echo "Next steps at server:"
echo "1. Download file ke server"
echo "2. Extract: tar -xzf ujicoba_deploy.tar.gz"
echo "3. Run: docker-compose up -d"
