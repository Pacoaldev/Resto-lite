<p align="center">
  <img src="./frontend/public/Logo_RepoLite.svg" alt="RestoLite" width="600" />
</p>

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

## API

Base: `/api`. Spec completa: `openapi.yaml`.

| Método | Ruta | Descripción | Códigos |
|--------|------|-------------|---------|
| `GET` | `/tables` | Listado de mesas y estado | 200 |
| `POST` | `/tables/{id}/request-bill` | Pedir / ver cuenta (con IVA) | 200, 404, 422 |
| `POST` | `/tables/{id}/settle` | Cobrar y liberar mesa | 204, 404 |
| `GET` | `/products` | Catálogo / stock | 200 |
| `GET` | `/orders?limit=N` | Pedidos recientes (default 10, máx 50) | 200 |
| `POST` | `/orders` | Crear pedido (`tableId`, `items[]`) | 201, 404, 409, 422 |
| `PATCH` | `/orders/{id}/status` | Cambiar estado de pedido | 204, 404, 422 |
| `GET` | `/establishment` | País, moneda, tasa e impuesto actuales | 200 |
| `PUT` | `/establishment` | Cambiar país fiscal del local (`country`) | 200, 422 |

### Mapeo de excepciones → HTTP

Centralizado en `app/Exceptions/Handler.php`:

| Excepción | HTTP |
|-----------|------|
| `InsufficientStockException` | 409 Conflict |
| `DomainException` (genérica) | 422 Unprocessable Entity |
| `ValidationException` | 422 Unprocessable Entity |
| Recurso inexistente | 404 Not Found |

### Reglas de input

- `POST /api/orders`: el cliente envía solo `name` + `quantity` por item. El precio se resuelve **server-side** desde `products` (`exists:products,name`). Esto previene manipulación de precios en el cliente.
- `PATCH /api/orders/{id}/status`: solo transiciones permitidas. No se puede reabrir un pedido `cancelled` (lanza `DomainException` → 422).

### Concurrencia

- `CreateOrderUseCase` y `SettleTableUseCase` ejecutan dentro de `DB::transaction()`.
- `RequestBillUseCase` y `ChangeOrderStatusUseCase` también transaccionales, con `lockForUpdate()` sobre la mesa para evitar condiciones de carrera en doble `request-bill` o cambios de estado concurrentes.
- `CreateOrderUseCase` emite `OrderCreatedEvent` **fuera** del closure de la transacción, garantizando que el listener solo vea el estado ya commiteado (afterCommit semantics).

### Middleware

- `ForceJsonResponse` en el grupo `api`: fuerza `Accept: application/json` para que `ValidationException` y `AuthenticationException` devuelvan JSON en vez de redirigir.
- `ThrottleRequests::api` (60 req/min por IP) ya configurado.
- **Auth deshabilitada en el PoC** (sin Sanctum). `PUT /api/establishment` está marcada en `routes/api.php` con un comentario `ponytail` indicando dónde agregar `->middleware('auth')` cuando se instale Sanctum.

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

### Capas

- **Domain** (`app/Orders/Domain/`): `Order`, enums (`OrderStatus`, `TableStatus`), `OrderCreatedEvent`, `InsufficientStockException`, puertos (`OrderRepositoryInterface`, `TableRepositoryInterface`, `ProductRepositoryInterface`, `TaxCalculatorInterface`). Sin dependencias de Laravel/Eloquent.
- **Application** (`app/Orders/Application/`): casos de uso (`CreateOrderUseCase`, `RequestBillUseCase`, `SettleTableUseCase`, `ChangeOrderStatusUseCase`, `ListTablesUseCase`, `ListProductsUseCase`, `ListRecentOrdersUseCase`). Orquestan dominio + infraestructura.
- **Infrastructure**:
  - `Http/`: controllers (delgados, inyectan use cases).
  - `Persistence/`: repositorios Eloquent.
  - `Tax/`: calculadoras concretas + `TaxCountryConfig`.
  - `Queue/`: listeners async.

### Sync asíncrono

1. Crear pedido → `OrderCreatedEvent`
2. `SyncOrderToErpMaestroListener` (implements `ShouldQueue`) encola en Redis
3. Worker ejecuta: si `services.erpMaestro.url` está vacío, corto-circuito (dev/demo). Si no, `Http::timeout(3)->throw()->post(...)`. Solo `ConnectionException` se re-lanza para que la cola reintente (`$tries=5`, `$backoff=10`). Tras 5 fallos → `failed_jobs`.

### Frontend

- **Web (Angular):** tablero de mesas, comanda, inventario, **panel "Pedidos recientes"** (carga `GET /api/orders` tras cada pedido), selector de país fiscal, offline → `localStorage` + re-sync cada 30s.
- **Mobile (Ionic):** monitor de estado de mesas.

---

## Tests

- **Pest**: 17 tests, 54 assertions, todos verdes.
  - `tests/Unit/`: dominio (Order, InsufficientStock, calculadoras de impuesto).
  - `tests/Feature/`: ciclo de mesa end-to-end (`TableLifecycleTest`) — cubre `CreateOrder` + `RequestBill` + `Settle` + cambio de país.
- **PHPStan**: nivel 5, baseline gestionado en `phpstan-baseline.neon`. Ejecutar con `--memory-limit=1G` por defecto del proyecto.
- **Cypress**: configurado pero sin specs (esqueleto en `cypress/`).

### Cobertura de tests manual (no automatizada)

- Race conditions: la lógica de `lockForUpdate` está cubierta por análisis estático + tests, no por tests de concurrencia (no hay infraestructura para eso en el PoC).
- ERP sync real: solo se valida el path "URL vacía" en la lógica del listener. El path de HTTP real requiere endpoint mockeado.

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

## Pendientes técnicos (no bugs, trabajo futuro)

- **Auth real**: instalar `laravel/sanctum`, agregar `->middleware('auth:sanctum')` a los endpoints mutantes (mínimo `POST /api/orders`, `PATCH /api/orders/{id}/status`, `PUT /api/establishment`).
- **FormRequests**: actualmente inline en controllers. Extraer cuando aparezca un 5º endpoint con reglas compartidas.
- **Resource classes**: las respuestas se construyen inline en controllers. Pasar a `JsonResource` cuando crezca el shape.
- **Circuit breaker / DLQ**: el listener reintenta 5 veces. Para producción, agregar backoff exponencial o dead-letter queue.
- **Cache TTL en `recentOrders`**: actualmente se recarga tras cada `onOrderPlaced`. Si crece el tráfico, polling cada N segundos con `Cache::remember`.

---

## Comandos útiles

```bash
# Backend
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan migrate:fresh --seed

# Tests
docker-compose exec app ./vendor/bin/pest
docker-compose exec app ./vendor/bin/phpstan analyse --memory-limit=1G

# Frontend
cd frontend && npm install && npm run start
# build dev: npx ng build --configuration=development
# E2E: npm run cypress:run

# Mobile
cd mobile && npm install && npm run build
```

Web habitual: frontend `http://localhost:4200` · API vía nginx `http://localhost:8080`.

### Probar el switcher de país fiscal

```bash
# Estado actual
curl http://localhost:8080/api/establishment

# Cambiar a México
curl -X PUT http://localhost:8080/api/establishment \
  -H "Content-Type: application/json" \
  -d '{"country":"mx"}'

# Pedir cuenta con IVA mexicano
curl -X POST http://localhost:8080/api/tables/1/request-bill
```

### Probar la cola del ERP

```bash
# Ver jobs pendientes
docker-compose exec app php artisan queue:size

# Worker en foreground
docker-compose exec app php artisan queue:work redis --tries=5 --backoff=10

# Purgar jobs fallidos
docker-compose exec app php artisan queue:flush
docker-compose exec app php artisan queue:prune-failed --hours=0
```
