DOCKERHUB_USERNAME="redomeire"
APP_NAME="paytrack-backend"

echo "Memulai proses deployment..."
echo "$DOCKERHUB_TOKEN" | sudo docker login -u "$DOCKERHUB_USERNAME" -p $DOCKERHUB_TOKEN

sudo podman pull docker.io/$DOCKERHUB_USERNAME/$APP_NAME:latest

sudo podman-compose down

sudo podman-compose up -d

sudo podman-compose exec -T php php artisan migrate --force

sudo podman-compose exec -T php php artisan optimize:clear
sudo podman-compose exec -T php php artisan optimize

sudo podman image prune -f

sudo podman-compose exec -T php php artisan queue:restart

sudo podman-compose exec -T php php artisan passport:keys

sudo podman-compose exec -T php chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

sudo podman-compose exec -T php chmod 660 /var/www/html/storage/oauth-*.key

echo "Deployment Done!"
