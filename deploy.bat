@REM Deployment script untuk ujicoba ke 192.168.0.73
@REM Edit: User dan password bisa diubah di bawah

@echo off
setlocal enabledelayedexpansion

set SERVER_IP=192.168.0.73
set USER=fdx
set DEPLOY_BASE=/home/fdx/dockerizer
set APP_NAME=ujicoba
set GIT_REPO=https://github.com/nabz22/ujibobaphalcon.git

echo.
echo ==========================================
echo UJICOBA DEPLOYMENT SCRIPT
echo ==========================================
echo.
echo Target Server: %SERVER_IP%
echo Deploy Path: %DEPLOY_BASE%/%APP_NAME%
echo Git Repo: %GIT_REPO%
echo.
echo IMPORTANT: Password will be prompted (use: k2Zd2qS2j)
echo.
pause

echo.
echo Connecting to server and running deployment commands...
echo.

@REM SSH connection dengan heredoc (Windows doesn't support heredoc, so we'll use multiple commands)
ssh -o StrictHostKeyChecking=no %USER%@%SERVER_IP% ^
  "echo [1/6] Creating deployment directory... && " ^
  "mkdir -p %DEPLOY_BASE%/%APP_NAME% && cd %DEPLOY_BASE%/%APP_NAME% && " ^
  "echo [2/6] Verifying tools... && " ^
  "git --version && docker --version && docker-compose --version && " ^
  "echo [3/6] Initializing git repository... && " ^
  "git init && git config user.email 'deploy@server' && git config user.name 'Deploy User' && " ^
  "git remote add origin %GIT_REPO% && " ^
  "echo [4/6] Fetching repository files... && " ^
  "git fetch origin main && git reset --hard origin/main && " ^
  "echo [5/6] Repository contents: && " ^
  "ls -la && " ^
  "echo [6/6] Starting Docker containers... && " ^
  "docker-compose up -d && " ^
  "echo. && echo ========================================== && " ^
  "echo DEPLOYMENT COMPLETE! && " ^
  "echo ========================================== && " ^
  "echo. && " ^
  "echo Container Status: && " ^
  "docker ps -a && " ^
  "echo. && " ^
  "echo Access your application: && " ^
  "echo App: http://%SERVER_IP%:8092 && " ^
  "echo PHPMyAdmin: http://%SERVER_IP%:8081 && " ^
  "echo."

echo.
if errorlevel 1 (
  echo ERROR: Deployment gagal. Periksa koneksi SSH dan password.
) else (
  echo SUCCESS: Deployment berhasil!
  echo.
  echo Aplikasi siap diakses di:
  echo   - http://%SERVER_IP%:8092
  echo   - http://%SERVER_IP%:8081
)
echo.
pause
