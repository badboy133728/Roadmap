# Курс на развитие

Публичный карьерный навигатор: профориентационный тест, каталог профессий, дорожные карты, зарплаты по городам и план смены профессии.

## Стек

- Laravel 10 + PHP 8.1
- MySQL 8
- Blade + Tailwind CSS + Alpine.js
- Laravel Breeze (личный кабинет)
- Filament 3 (админ-панель)

## Настройка OSPanel

1. Домен: `curs.local` → папка `C:\OSPanel\domains\Roadmap\public`
2. База данных: `curs` (уже создана при установке)
3. В `.env`:
   - `APP_URL=http://curs.local`
   - `DB_DATABASE=curs`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=` (пустой, как в OSPanel)

4. Включите расширение `intl` в PHP (для Filament):
   `C:\OSPanel\modules\php\PHP_8.1\php.ini` → `extension = php_intl.dll`

5. Команды (из папки проекта):

```bash
php artisan migrate --seed
npm run build
```

## Доступ

| Раздел | URL |
|--------|-----|
| Сайт | http://curs.local |
| Админка | http://curs.local/admin |
| Личный кабинет | http://curs.local/dashboard |

**Админ:** `admin@curs.local` / `password`

## Города

- Волгоград (по умолчанию)
- Астрахань

## Данные

- **152 профессии** в 12 категориях
- Зарплаты (junior/middle/senior) для обоих городов
- 10 вопросов профориентационного теста
- Учебные заведения Волгограда и Астрахани

## Дисклеймер

Зарплаты и рекомендации носят ознакомительный характер. Данные обновляются через админ-панель.
