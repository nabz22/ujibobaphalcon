# DEPLOYMENT INSTRUCTIONS untuk ujicoba ke server 192.168.0.73

## Prerequisites
- Server IP: 192.168.0.73
- SSH User: fdx
- Deploy Path: /home/fdx/dockerizer/ujicoba
- Git Repo: https://github.com/nabz22/ujibobaphalcon.git

## STEP 1: SSH ke server dan setup direktori

```bash
ssh fdx@192.168.0.73
# Password: k2Zd2qS2j

mkdir -p /home/fdx/dockerizer/ujicoba
cd /home/fdx/dockerizer/ujicoba

# Verify tools
git --version
docker --version
docker-compose --version
```

## STEP 2: Setup git repository

```bash
cd /home/fdx/dockerizer/ujicoba

# Initialize git
git init
git config user.email "deploy@server"
git config user.name "Deploy User"

# Add remote repository
git remote add origin https://github.com/nabz22/ujibobaphalcon.git

# Fetch dan pull dari remote
git fetch origin
git reset --hard origin/main
```

## STEP 3: Jalankan docker-compose

```bash
cd /home/fdx/dockerizer/ujicoba

# Jalankan container (pastikan port tersedia)
docker-compose up -d

# Verify
docker ps
docker-compose logs
```

## STEP 4: Akses aplikasi

- Aplikasi: http://192.168.0.73:8092
- PHPMyAdmin: http://192.168.0.73:8081

---

## Quick Deploy Command (copy-paste di server terminal)

```bash
mkdir -p /home/fdx/dockerizer/ujicoba && cd /home/fdx/dockerizer/ujicoba && git init && git config user.email "deploy@server" && git config user.name "Deploy User" && git remote add origin https://github.com/nabz22/ujibobaphalcon.git && git fetch origin && git reset --hard origin/main && docker-compose up -d
```
