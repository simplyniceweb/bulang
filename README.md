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

## Firefox silent print
To enable silent, automatic printing in Firefox (bypassing the print dialog), type about:config in the address bar, search for print.always_print_silent, and set it to true. Additionally, set print.show_print_progress to false to hide the print status bar. Use this with a single default printer.

TODO: