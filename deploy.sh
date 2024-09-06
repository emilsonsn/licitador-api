gti pull https://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@github.com/emilsonsn/licitador-api.git

composer update

php artisan key:generate

php artisan migrate

php artisan db:seed

php artisan optimize

php artisan optimize:clear