# Deploy script untuk Ujicoba
# Menggunakan SSH key authentication atau manual setup

param(
    [switch]$SetupKeys,
    [string]$ServerIP = "192.168.0.73",
    [string]$Username = "fdx"
)

$Password = "k2Zd2qS2j"
$DeployBase = "/home/fdx/dockerizer"
$AppName = "ujicoba"
$GitRepo = "https://github.com/nabz22/ujibobaphalcon.git"

Write-Host "`n🚀 UJICOBA DEPLOYMENT" -ForegroundColor Cyan
Write-Host "Target: $ServerIP" -ForegroundColor Gray

# Function untuk menjalankan commands via SSH
function Invoke-RemoteCommand {
    param(
        [string]$Command,
        [string]$Description
    )
    
    Write-Host "`n▶ $Description" -ForegroundColor Yellow
    Write-Host "  $ $Command" -ForegroundColor Gray
    
    # Combine commands dengan semicolon untuk single SSH session
    & ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $Username@$ServerIP $Command
}

# Setup SSH key jika dipilih
if ($SetupKeys) {
    Write-Host "`n[SETUP] Generating SSH key..." -ForegroundColor Cyan
    $keyPath = "$HOME\.ssh\ujicoba_deploy"
    
    if (-not (Test-Path $keyPath)) {
        ssh-keygen -t rsa -b 4096 -f $keyPath -N "" -C "ujicoba-deploy"
        Write-Host "✓ SSH key generated at $keyPath" -ForegroundColor Green
    }
    
    # Upload public key (manual step)
    Write-Host "`n[IMPORTANT] Manual step required:" -ForegroundColor Red
    Write-Host "  1. Copy public key:" -ForegroundColor White
    Write-Host "     cat $keyPath.pub" -ForegroundColor Gray
    Write-Host "  2. Add to server ~/.ssh/authorized_keys" -ForegroundColor White
    Write-Host "  3. Then run this script without -SetupKeys flag" -ForegroundColor White
    exit
}

Write-Host "`n" + ("="*50) -ForegroundColor Cyan

try {
    # Test connection
    Write-Host "`n[1/7] Testing SSH connection..." -ForegroundColor Cyan
    ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $Username@$ServerIP "echo 'Connected OK'; whoami" | Out-Null
    Write-Host "✓ SSH connection successful" -ForegroundColor Green
    
    # Create directories
    Write-Host "`n[2/7] Creating deployment directories..." -ForegroundColor Cyan
    & ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $Username@$ServerIP "mkdir -p $DeployBase/$AppName; echo '✓ Directory ready'"
    
    # Check tools
    Write-Host "`n[3/7] Verifying tools..." -ForegroundColor Cyan
    & ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $Username@$ServerIP @"
echo 'Git:' && git --version
echo 'Docker:' && docker --version
echo 'Docker Compose:' && docker-compose --version
"@
    
    # Git initialization
    Write-Host "`n[4/7] Initializing git repository..." -ForegroundColor Cyan
    & ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $Username@$ServerIP @"
cd $DeployBase/$AppName && \
git init && \
git config user.email 'deploy@server' && \
git config user.name 'Deploy User' && \
git remote add origin $GitRepo && \
echo '✓ Git initialized'
"@
    
    # Fetch repository
    Write-Host "`n[5/7] Fetching repository..." -ForegroundColor Cyan
    & ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $Username@$ServerIP @"
cd $DeployBase/$AppName && \
git fetch origin main && \
git reset --hard origin/main && \
echo '✓ Repository ready'
"@
    
    # Verify files
    Write-Host "`n[6/7] Verifying deployment files..." -ForegroundColor Cyan
    & ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $Username@$ServerIP "ls -lah $DeployBase/$AppName/ | head -15"
    
    # Start containers
    Write-Host "`n[7/7] Starting Docker containers..." -ForegroundColor Cyan
    & ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $Username@$ServerIP @"
cd $DeployBase/$AppName && \
docker-compose up -d && \
echo '✓ Containers started' && \
echo '' && \
echo 'Container status:' && \
docker ps -a
"@
    
    Write-Host "`n" + ("="*50) -ForegroundColor Green
    Write-Host "✅ DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
    Write-Host "`n🌐 Access your application:" -ForegroundColor Cyan
    Write-Host "   App: http://$ServerIP:8092" -ForegroundColor White
    Write-Host "   PHPMyAdmin: http://$ServerIP:8081" -ForegroundColor White
    Write-Host "`nℹ️  Git Repo: $GitRepo" -ForegroundColor Gray
    Write-Host "   Deploy Path: $DeployBase/$AppName" -ForegroundColor Gray
    Write-Host ""
    
} catch {
    Write-Host "`n❌ Deployment failed: $_" -ForegroundColor Red
    exit 1
}
