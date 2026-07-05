# Օնլայն հաշվետվություններ (HTML + PHP)

Պարզ վեբ-տարբերակ Flutter հավելվածի `picasso.elina.online.reports`-ի փոխարեն։

## Դեպլոյ

Պատճենել `migrate-web/` պանակի **բովանդակությունը** սերվերի **վեբ-արմատ** մեջ (որտեղ արդեն կա `/engine/`):

```
/
├── engine/          ← առկա backend (web.backend)
├── index.php
├── login.php
├── online-report.php
├── day-end.php
├── logout.php
├── inc/
├── templates/
└── assets/
```

Նույն դոմենը, առանց CORS-ի։ API-ն կանչվում է `https://<host>/engine/...`։

## Էջեր

| Ֆայլ | Նկարագրություն |
|------|----------------|
| `login.php` | Մուտք (օգտանուն / գաղտնաբառ) |
| `online-report.php` | Օնլայն վաճառք, գումարներ, դրամարկղ |
| `day-end.php` | Օրվա փակում |
| `logout.php` | Ելք |

## Backend

Օգտագործվում են առկա endpoint-ները.

- `POST /engine/login.php`
- `POST /engine/logout.php`
- `POST /engine/shop/reports/online-main.php`
- `POST /engine/shop/reports/day-end.php`

SQL և հաշվետվության տրամաբանությունը **չի փոխվել**։

## Պահանջներ

- PHP 8+ (`curl` extension)
- PHP sessions

## Flutter

Հին Flutter build-ը կարելի է թողնել պարALLEL կամ հանել, երբ վեբ-տարբերակը ստուգված լինի։
