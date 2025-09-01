#!/bin/bash

# Ganti dengan username Docker Hub Anda
DOCKERHUB_USERNAME="redomeire"
APP_NAME="paytack-backend"

echo "Memulai proses deployment..."

# 2. Menarik image terbaru dari Docker Hub
echo "Menarik image terbaru: $DOCKERHUB_USERNAME/$APP_NAME:latest"
sudo docker pull $DOCKERHUB_USERNAME/$APP_NAME:latest

# 3. Mematikan container yang sedang berjalan (jika ada)
echo "Mematikan container yang sedang berjalan..."
sudo docker-compose down

# 4. Menjalankan container baru dengan image yang baru ditarik
echo "Menjalankan container baru..."
sudo docker-compose up -d

# 5. Menjalankan perintah post-deployment Laravel di dalam container 'app'
echo "Menjalankan migrasi database..."
sudo docker-compose exec app php artisan migrate --force

echo "Membersihkan dan membuat cache baru..."
sudo docker-compose exec app php artisan optimize:clear
sudo docker-compose exec app php artisan optimize

# 6. Membersihkan image Docker yang tidak terpakai (dangling images)
echo "Membersihkan image lama..."
sudo docker image prune -f

echo "Deployment selesai!"
