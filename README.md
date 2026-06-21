# ALLIANCE VK — Мебель на заказ

Сайт мебельного производства на заказ. Новосибирск.

**Стек:** Astro 6 + Tailwind CSS v4  
**Хостинг:** Timeweb (shared)  
**Деплой:** GitHub Actions (автоматически при пуше в `main`)  
**Домен:** [альянсвк.рф](https://альянсвк.рф)

---

## Быстрый старт

```bash
npm install
npm run dev
```

Сайт запустится на `http://localhost:4321`

## Сборка

```bash
npm run build    # собрать в /dist
npm run preview  # посмотреть собранную версию
```

## Деплой

Деплой происходит автоматически: любой `git push origin main` запускает GitHub Actions workflow, который:

1. Устанавливает зависимости (`npm ci`)
2. Собирает сайт (`npm run build`)
3. Загружает `/dist` на сервер через SCP

Для работы деплоя нужны три секрета в настройках репозитория:

| Секрет | Значение |
| ------ | -------- |
| `SSH_HOST` | хост сервера |
| `SSH_USER` | логин SSH |
| `SSH_PRIVATE_KEY` | приватный ключ ED25519 |

## Структура проекта

```text
src/
├── components/     # Astro-компоненты (Header, Footer, Chatbot и др.)
├── data/
│   └── site.ts    # весь контент сайта — тексты, цены, контакты
├── layouts/
│   └── BaseLayout.astro
└── pages/
    └── index.astro
public/
└── furniture/     # фотографии работ (добавляются вручную)
```

## Контент

Весь текстовый контент и данные находятся в `src/data/site.ts` — контакты, цены, секции, акции.

## Требования

- Node.js >= 22.12.0 (требование Astro 6)
