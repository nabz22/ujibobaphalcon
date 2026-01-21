#!/usr/bin/env pwsh
# Deploy script untuk aplikasi ujicoba ke server 192.168.0.73

$ServerIP = "192.168.0.73"
$User = "fdx"
$Password = "k2Zd2qS2j"
$DeployBase = "/home/fdx/dockerizer"
$AppName = "ujicoba"

# Create SSH session dengan password
$SecurePassword = ConvertTo-SecureString $Password -AsPlainText -Force
$Credential = New-Object System.Management.Automation.PSCredential($User, $SecurePassword)

function Invoke-SSHCommand {
    param(
        [string]$Command,
        [string]$Description
    )
    Write-Host "`n=== $Description ===" -ForegroundColor Cyan
    $result = & sshpass -p "$Password" ssh -o StrictHostKeyChecking=no "$User@$ServerIP" "$Command"
    Write-Host $result
    return $result
}

# 1. Cek server dan setup direktori
Write-Host "`n[STEP 1] Setup direktori di server..." -ForegroundColor Green
Invoke-SSHCommand "mkdir -p $DeployBase/$AppName && ls -la $DeployBase/" "Cek/Buat direktori"

# 2. Cek git dan docker
Write-Host "`n[STEP 2] Verifikasi environment di server..." -ForegroundColor Green
Invoke-SSHCommand "git --version && echo '---' && docker --version && echo '---' && docker-compose --version" "Git dan Docker version"

# 3. Initialize git repo di server (jika belum ada)
Write-Host "`n[STEP 3] Setup git repository..." -ForegroundColor Green
Invoke-SSHCommand "cd $DeployBase/$AppName && git init && git config user.email 'deploy@server' && git config user.name 'Deploy User'" "Initialize git repo"

Write-Host "`n[Deploy preparation complete!]" -ForegroundColor Yellow
