composer install
npm run build; npm run dev;
php artisan migrate:fresh --seed
php artisan serve
php artisan reverb:start
php artisan route:list
php artisan config:clear; php artisan cache:clear; php artisan route:clear