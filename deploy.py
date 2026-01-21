#!/usr/bin/env python3
"""
Ujicoba Deployment Script
Deploy aplikasi ke server 192.168.0.73
"""

import subprocess
import sys
import os
from pathlib import Path

# Configuration
SERVER_IP = "192.168.0.73"
USER = "fdx"
PASSWORD = "k2Zd2qS2j"
DEPLOY_BASE = "/home/fdx/dockerizer"
APP_NAME = "ujicoba"
GIT_REPO = "https://github.com/nabz22/ujibobaphalcon.git"

def run_ssh_command(command, description=""):
    """Execute SSH command on remote server"""
    if description:
        print(f"\n📍 {description}")
    
    # Format: sshpass -p PASSWORD ssh user@host "command"
    full_cmd = f'sshpass -p "{PASSWORD}" ssh -o StrictHostKeyChecking=no {USER}@{SERVER_IP} "{command}"'
    
    print(f"   $ {command}")
    try:
        result = subprocess.run(full_cmd, shell=True, capture_output=True, text=True)
        if result.stdout:
            print(result.stdout)
        if result.stderr and result.returncode != 0:
            print(f"   ❌ Error: {result.stderr}")
            return False
        return True
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        return False

def check_sshpass():
    """Check if sshpass is installed"""
    try:
        subprocess.run("sshpass -V", shell=True, capture_output=True, check=True)
        return True
    except:
        print("❌ sshpass not found. Please install it first:")
        print("   Ubuntu/Debian: sudo apt-get install sshpass")
        print("   macOS: brew install sshpass")
        print("   Windows: install via scoop or download from https://sourceforge.net/projects/sshpass/files/")
        return False

def main():
    print("\n" + "="*50)
    print("🚀 UJICOBA DEPLOYMENT SCRIPT")
    print("="*50)
    
    # Check prerequisites
    print("\n[CHECK] Verifying prerequisites...")
    if not check_sshpass():
        sys.exit(1)
    
    # Step 1: Test SSH Connection
    print("\n[STEP 1] Testing SSH connection...")
    if not run_ssh_command("echo 'SSH OK'; whoami", "SSH Connection Test"):
        print("❌ Failed to connect to server")
        sys.exit(1)
    
    # Step 2: Setup directories
    print("\n[STEP 2] Setting up deployment directories...")
    run_ssh_command(f"mkdir -p {DEPLOY_BASE}/{APP_NAME} && ls -la {DEPLOY_BASE}/", "Create directories")
    
    # Step 3: Verify tools
    print("\n[STEP 3] Verifying server tools...")
    run_ssh_command("git --version && docker --version && docker-compose --version", "Tool versions")
    
    # Step 4: Initialize git repository
    print("\n[STEP 4] Initializing git repository...")
    git_init_cmd = f"cd {DEPLOY_BASE}/{APP_NAME} && git init && git config user.email 'deploy@server' && git config user.name 'Deploy User'"
    run_ssh_command(git_init_cmd, "Git initialization")
    
    # Step 5: Add remote and fetch
    print("\n[STEP 5] Adding git remote and fetching...")
    git_remote_cmd = f"cd {DEPLOY_BASE}/{APP_NAME} && git remote add origin {GIT_REPO}"
    run_ssh_command(git_remote_cmd, "Add git remote")
    
    # Step 6: Clone/Pull files
    print("\n[STEP 6] Fetching repository files...")
    git_fetch_cmd = f"cd {DEPLOY_BASE}/{APP_NAME} && git fetch origin main && git reset --hard origin/main"
    run_ssh_command(git_fetch_cmd, "Fetch and reset to main")
    
    # Step 7: List files
    print("\n[STEP 7] Verifying deployment...")
    run_ssh_command(f"ls -la {DEPLOY_BASE}/{APP_NAME}/", "Directory listing")
    
    # Step 8: Start containers
    print("\n[STEP 8] Starting Docker containers...")
    docker_cmd = f"cd {DEPLOY_BASE}/{APP_NAME} && docker-compose up -d"
    run_ssh_command(docker_cmd, "Docker Compose Up")
    
    # Step 9: Check status
    print("\n[STEP 9] Checking container status...")
    run_ssh_command("docker ps -a", "Container status")
    
    print("\n" + "="*50)
    print("✅ DEPLOYMENT COMPLETE!")
    print("="*50)
    print(f"\n🌐 Access your application:")
    print(f"   App: http://{SERVER_IP}:8092")
    print(f"   PHPMyAdmin: http://{SERVER_IP}:8081")
    print("\n")

if __name__ == "__main__":
    main()
