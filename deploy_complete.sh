#!/bin/bash
# Complete deployment script for ujicoba to 192.168.0.73
# Copy-paste this entire script at once - only need to enter password ONCE

DEPLOY_BASE="/home/fdx/dockerizer"
APP_NAME="ujicoba"
GIT_REPO="https://github.com/nabz22/ujibobaphalcon.git"

echo "=========================================="
echo "✨ UJICOBA DEPLOYMENT SCRIPT"
echo "=========================================="
echo ""
echo "🚀 Starting deployment to /home/fdx/dockerizer/$APP_NAME"
echo ""

# All commands in one SSH session to minimize password prompts
ssh fdx@192.168.0.73 << 'ENDSSH'

echo "[1/6] Creating deployment directory..."
mkdir -p /home/fdx/dockerizer/ujicoba
cd /home/fdx/dockerizer/ujicoba

echo "[2/6] Verifying tools..."
git --version
docker --version
docker-compose --version
echo ""

echo "[3/6] Initializing git repository..."
git init
git config user.email "deploy@server"
git config user.name "Deploy User"
git remote add origin https://github.com/nabz22/ujibobaphalcon.git

echo "[4/6] Fetching repository files..."
git fetch origin main
git reset --hard origin/main

echo "[5/6] Repository contents:"
ls -la | head -20

echo ""
echo "[6/6] Starting Docker containers..."
docker-compose up -d

echo ""
echo "=========================================="
echo "✅ DEPLOYMENT COMPLETE!"
echo "=========================================="
echo ""
echo "📍 Container Status:"
docker ps -a

echo ""
echo "🌐 Access your application:"
echo "   App: http://192.168.0.73:8092"
echo "   PHPMyAdmin: http://192.168.0.73:8081"
echo ""

ENDSSH

echo "✅ Done! Check the container status above."
