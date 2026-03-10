composer install
npm run build; npm run dev;
php artisan migrate:fresh --seed

php artisan serve
php artisan serve --host=192.168.254.200 --port=80
## change .env and vite.config.ts if you want to change ip address

php artisan reverb:start
php artisan route:list
php artisan config:clear; php artisan cache:clear; php artisan route:clear


## LAMP server setup
https://gemini.google.com/app/50beccd009083a04

TODO:
Cannot open if there's no winner declaration
Reset the betting controls after cancel / open new round
