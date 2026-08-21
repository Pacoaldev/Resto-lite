# Resto Lite — MVP de sala y comandas (1 local)

Boceto ejecutable de un módulo **Lite** para un restaurante independiente: mesas, pedidos, cuenta con IVA LATAM/ES, sync asíncrono a un ERP Maestro simulado. Stack: **Laravel 10 (hexagonal) + Angular + Redis + MySQL**.

---

## Ciclo de mesa

Estado de sala (no confundir con estado del pedido):

```
libre → ocupada (al crear pedido) → cuenta pedida (pedir cuenta) → libre (cobrar)
```

| Acción UI | Efecto |
|-----------|--------|
| Tomar / añadir pedido | Crea orden; mesa → `occupied` |
| Pedir cuenta | Genera cuenta con desglose fiscal; mesa → `billRequested` |
| Ver cuenta | Misma cuenta (subtotal + IVA/IGV + total) |
| Cobrar | Marca pedidos `paid`; mesa → `free` |

---

## Impuestos y establecimiento

Un local Lite = **un país fiscal + una moneda de presentación** (sin conversión FX).

| País | Calculadora | Impuesto |
|------|-------------|----------|
| España (default) | `SpainTaxCalculator` | IVA 21% · EUR |
| México | `MexicoTaxCalculator` | IVA 16% · MXN |
| Chile | `ChileTaxCalculator` | IVA 19% · CLP |
| Colombia | `ColombiaTaxCalculator` | IVA 19% · COP |
| Argentina | `ArgentinaTaxCalculator` | IVA 21% · ARS |
| Perú | `PeruTaxCalculator` | IGV 18% · PEN |

- Default: `TAX_COUNTRY` en entorno.
- En runtime (demo): selector del sidebar → `PUT /api/establishment` con `{ "country": "mx" }`.
- La cuenta (`POST .../request-bill`) devuelve `subtotal`, `taxLabel`, `taxRate`, `taxAmount`, `total`, `currency`, `country`.

Patrón: **Strategy** vía `TaxCalculatorInterface` + `TaxCountryConfig`.

---

## API (corta)

Base: `/api`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/tables` | Listado de mesas y estado |
| `POST` | `/tables/{id}/request-bill` | Pedir / ver cuenta (con IVA) |
| `POST` | `/tables/{id}/settle` | Cobrar y liberar mesa |
| `GET` | `/products` | Catálogo / stock |
| `POST` | `/orders` | Crear pedido (`tableId`, `items[]`) |
| `PATCH` | `/orders/{id}/status` | Cambiar estado de pedido |
| `GET` | `/establishment` | País, moneda, tasa e impuesto actuales |
| `PUT` | `/establishment` | Cambiar país fiscal del local (`country`) |

---

## Arquitectura (backend)

Laravel hexagonal: Domain (reglas + puertos) → Application (casos de uso) → Infrastructure (HTTP, Eloquent, Redis, ERP).

```
[ API Controllers ] → [ Use cases ] → [ Domain ]
                           │
              ┌────────────┴────────────┐
              ▼                         ▼
        [ Eloquent / DB ]      [ Redis queue → ERP Maestro ]
```

### Sync asíncrono

1. Crear pedido → `OrderCreatedEvent`
2. Listener encola sync en Redis
3. Worker reintenta si el ERP simulado falla (`$tries` / `$backoff`)

### Frontend

- **Web (Angular):** tablero de mesas, comanda, inventario, offline → `localStorage` + re-sync.
- **Mobile (Ionic):** monitor de estado de mesas.

---

## Problema encontrado: PHP host 8.4 vs Docker 8.3

**Síntoma:** `artisan` dentro del contenedor fallaba con  
`Composer dependencies require a PHP version ">= 8.4.1". You are running 8.3.33`.

**Causa:** Composer en el host (PHP 8.4) resolvió `symfony/css-selector` v8.x (≥ 8.4.1). La imagen Docker es `php:8.3-fpm`.

**Solución:**

1. Pin de plataforma en `composer.json`: `"platform": { "php": "8.3.33" }`
2. Downgrade a `symfony/css-selector` 7.x compatible con 8.3

Así el lockfile apunta al runtime de Docker aunque el host sea más nuevo.

---

## Comandos útiles

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan migrate:fresh --seed

docker-compose exec app ./vendor/bin/pest
docker-compose exec app ./vendor/bin/phpstan analyse

cd frontend && npm install && npm run start
# E2E: npm run cypress:run

cd mobile && npm install && npm run build
```

Web habitual: frontend `http://localhost:4200` · API vía nginx `http://localhost:8080`.
