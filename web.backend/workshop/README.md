# Elina Workshop — Web

Vanilla HTML/CSS/JS client for Elina workshop (no npm / no frameworks).

## Deploy

Copy these files (except `PLAN.md`) to the web server path **`/workshop/`**:

```
/workshop/index.html
/workshop/css/
/workshop/js/
/workshop/assets/
```

Requires existing backend at `/engine/elinaworkshop/` (unchanged).

## Local note

Open via the same host as the API (same origin).  
If testing from another origin, configure reverse proxy or temporarily set `AppConfig.apiBase` in `js/config.js`.

## Auth

PIN login → `login.php` method=2 → `sessionkey` in `localStorage`.
