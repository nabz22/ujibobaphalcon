#!/usr/bin/env pwsh

# Deploy Configuration
$ServerIP = "192.168.0.73"
$User = "fdx"
$Password = "k2Zd2qS2j"
$DeployBase = "/home/fdx/dockerizer"
$AppName = "ujicoba"
$GitRepo = "https://github.com/nabz22/ujibobaphalcon.git"

Write-Host "`n========== UJICOBA DEPLOYMENT SCRIPT ==========" -ForegroundColor Cyan

# Function untuk execute command di server dengan password
function Exec-SSH {
    param([string]$Command)
    
    # Escape quotes dan special characters
    $cmd = "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $User@$ServerIP `"$Command`""
    Write-Host "  $ $Command" -ForegroundColor Gray
    
    # Use heredoc untuk pass password
    $process = @"
#!/usr/bin/expect -f
set timeout 5
spawn ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $User@$ServerIP "$Command"
expect "password:"
send "$Password\r"
expect eof
@"
    
    # Coba alternatif: gunakan PowerShell SSH yang built-in (jika Windows 10/11 modern)
    try {
        $output = Invoke-Expression $cmd 2>&1
        return $output
    } catch {
        Write-Host "  ❌ Gagal execute command: $_" -ForegroundColor Red
        return $null
    }
}

Write-Host "`n[STEP 1] Testing SSH connection..." -ForegroundColor Yellow
Exec-SSH "echo 'SSH Connection OK'; whoami"

Write-Host "`n[STEP 2] Setup deployment directory..." -ForegroundColor Yellow
Exec-SSH "mkdir -p $DeployBase/$AppName && ls -la $DeployBase/"

Write-Host "`n[STEP 3] Verify environment..." -ForegroundColor Yellow
Exec-SSH "echo '=== Git ===' && git --version && echo '=== Docker ===' && docker --version && echo '=== Docker Compose ===' && docker-compose --version"

Write-Host "`n[STEP 4] Initialize git repository..." -ForegroundColor Yellow
Exec-SSH "cd $DeployBase/$AppName && git init && git config user.email 'deploy@server' && git config user.name 'Deploy User' && git remote add origin $GitRepo"

Write-Host "`n[STEP 5] Clone repository..." -ForegroundColor Yellow
Exec-SSH "cd $DeployBase/$AppName && git pull origin main --allow-unrelated-histories || git fetch origin main && git reset --hard origin/main"

Write-Host "`n[STEP 6] Verify files..." -ForegroundColor Yellow
Exec-SSH "ls -la $DeployBase/$AppName/"

Write-Host "`n✅ Deployment preparation complete!" -ForegroundColor Green
Write-Host "   Path: $DeployBase/$AppName`n" -ForegroundColor Cyan
