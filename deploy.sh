DOCKERHUB_USERNAME="redomeire"
APP_NAME="paytrack-backend"

echo "Memulai proses deployment..."
echo "$DOCKERHUB_TOKEN" | sudo docker login -u "$DOCKERHUB_USERNAME" -p $DOCKERHUB_TOKEN

sudo docker pull $DOCKERHUB_USERNAME/$APP_NAME:latest

sudo docker-compose down

sudo docker-compose up -d

sudo docker-compose exec -T php php artisan migrate --force

sudo docker-compose exec -T php php artisan optimize:clear
sudo docker-compose exec -T php php artisan optimize

sudo docker image prune -f

sudo docker-compose exec -T php php artisan queue:restart

sudo docker-compose exec -T php php artisan passport:keys

sudo docker-compose exec -T php chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

sudo docker-compose exec -T php chmod 660 /var/www/html/storage/oauth-*.key

echo "Deployment Done!"
