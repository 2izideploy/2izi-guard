# 2IZI Guard 0.4.10 — Responsive Layout Hotfix

## Что исправлено

В 0.4.9 при `IZIGuard.preview(..., {state:'challenge'})` временная `<form>` могла стать shrink-to-fit элементом внутри `display:flex`. На 320/390 px это приводило к схлопыванию containing block: сам Guard оставался в Shadow DOM, но визуально превращался в узкую вертикальную полоску.

0.4.10 исправляет именно layout-контракт:

- preview-form: `display:block`, `width:100%`, `min-width:0`, `min-inline-size:0`;
- безопасное поведение как flex/grid item;
- challenge-host: `min-width:0`, `min-inline-size:0`, `flex:0 1 420px`;
- preview mount: grid centering вместо shrink-to-fit flex;
- сохранены `width:100%` и `max-width:420px` у challenge.

## Обновление 0.4.9 → 0.4.10

SQL не нужен. Миграции не нужны. Ключи не ротировать. `config/guard.php` не заменять.

Обновить frontend assets:

```html
<script
    src="/guard/public/assets/guard.js?v=0.4.10"
    data-guard-base="/guard/public"
    data-guard-css="/guard/public/assets/guard.css?v=0.4.10"
    data-guard-worker="/guard/public/assets/guard-worker.js?v=0.4.10"
    data-guard-isolation="shadow"
    defer></script>
```

Проверка:

```javascript
IZIGuard.version
```

Ожидается `0.4.10`.

## Требования к host layout

Guard самостоятельно защищён от типичных flex/grid конфликтов, но интеграционный slot всё равно рекомендуется делать нормальным block/grid container:

```html
<div data-guard-ui-slot></div>
<div data-guard-badge-slot></div>
```

Не задавайте самому slot `width:0`, `display:none`, `position:absolute` без размеров или другие намеренно разрушающие ограничения.

## Preview

```javascript
IZIGuard.preview(document.getElementById('guard-preview'), {
    state: 'challenge',
    locale: 'ru',
    modality: 'touch',
    isolation: 'shadow',
    holdMs: 1200,
    resetAfterMs: 2200
});
```

На 320/390 px challenge должен занимать доступную ширину canvas, но не больше `420px`, без вертикального схлопывания.

## Тесты

Добавлен `tests/responsive-layout-hotfix.php`. Он проверяет sizing-contract preview form, Shadow host и flex/grid-safe параметры.
