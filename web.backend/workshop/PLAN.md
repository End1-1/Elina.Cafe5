# План: Elina Workshop → полный веб

## Цель

Веб-клиент цеха в `migrate-to-web` без сторонних зависимостей.  
Flutter **не трогаем** (остаётся как есть, web — отдельный артефакт).

## Зафиксировано

| # | Решение |
|---|--------|
| 1 | Vanilla HTML/CSS/JS, без npm/React/Vue |
| 2 | Пока только web; Flutter не меняем |
| 3 | Backend не трогаем |
| 4 | Деплой `/workshop/` |
| 5 | PIN-логин как во Flutter |
| UI | Новый макет: tablet-first, адаптив под телефон |

## Деплой

Скопировать содержимое `migrate-to-web/` (кроме PLAN.md) в web-root `/workshop/`.
