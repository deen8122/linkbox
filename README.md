# Запуск проекта

Быстрый старт одной командой (поднимает контейнеры, накатывает миграции и сидеры, делает `storage:link`, ставит npm-зависимости):

    make init

После неё останется запустить дев-сервер фронтенда вручную:

    ./vendor/bin/sail npm run dev

Команды по отдельности:

./vendor/bin/sail up -d


./vendor/bin/sail npm run dev

sail up -d
sail down
sail artisan migrate
sail npm install
sail npm run dev

# Сброс базы и сидеры

Пересоздать все таблицы и наполнить базу тестовыми данными (пользователь, теги, ссылки, блоки ссылок):

    sail artisan migrate:fresh --seed

Прогнать сидеры без пересоздания таблиц (на уже существующей базе):

    sail artisan db:seed

Запустить конкретный сидер:

    sail artisan db:seed --class=TagSeeder
    sail artisan db:seed --class=LinkSeeder
    sail artisan db:seed --class=LinkBlockSeeder

# Авторизация

Авторизация без пароля, по одноразовому коду на email.

1. Запросить код:

       POST /auth/request-code
       { "email": "test@example.com" }

   Письмо реально не отправляется — код пишется в лог `storage/logs/laravel.log` (`Login code generated`).

2. Подтвердить код (действует 10 минут):

       POST /auth/verify-code
       { "email": "test@example.com", "code": "123456" }

   После успешной проверки создаётся сессия (cookie), пользователь создаётся автоматически, если ещё не существует.

3. Выйти из аккаунта:

       POST /auth/logout

Тестовый пользователь после `sail artisan migrate:fresh --seed`: `test@example.com` (пароль не используется, вход только по коду из лога).
