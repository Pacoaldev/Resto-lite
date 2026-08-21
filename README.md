# Resto Lite - setup inicial

Modulo "Orders" con arquitectura hexagonal, primer paso del boceto.

## Estructura

- `app/Orders/Domain`: entidad `Order`, enum `OrderStatus` e interfaz `OrderRepositoryInterface`. Sin dependencias de Laravel.
- `app/Orders/Infrastructure/Persistence`: `OrderModel` (Eloquent) y `EloquentOrderRepository`, que implementa la interfaz del dominio.
- `app/Orders/Application`: `CreateOrderUseCase` y `ChangeOrderStatusUseCase`, orquestan el dominio a traves de la interfaz del repositorio.
- `app/Orders/Infrastructure/Http`: `OrderController` (POST /api/orders, PATCH /api/orders/{id}/status), valida y delega en los casos de uso.
- `routes/orders.php`: rutas de la API, incluir con `require` desde `routes/api.php`.
- `app/Orders/Domain/OrderCreatedEvent.php`: evento de dominio disparado al crear un pedido.
- `app/Orders/Infrastructure/Queue/SyncOrderToErpMaestroListener.php`: listener en cola (`ShouldQueue`) que envia el pedido al ERP maestro via HTTP. Reintentos (5) y backoff configurados para tolerar caidas de red, pensado para el contexto LATAM de conectividad irregular.

Registrar en `EventServiceProvider`:

```php
protected $listen = [
    OrderCreatedEvent::class => [
        SyncOrderToErpMaestroListener::class,
    ],
];
```

Y anhadir en `config/services.php`:

```php
'erpMaestro' => [
    'url' => env('ERP_MAESTRO_URL'),
],
```

## Tests

`tests/Unit/Orders/OrderTest.php`: tests Pest sobre la logica de dominio (total, cambio de estado, regla de pedido cancelado). Correr con `./vendor/bin/pest`.

## Frontend (Angular 17+ standalone, PrimeNG)

En `frontend/src/app`:

- `tables-board/`: tablero de mesas (libre/ocupada/cuenta pedida) con `p-table` y `p-tag`.
- `order-taking/`: formulario de toma de pedidos por mesa, con `p-inputNumber` para cantidades.
- `core/orders.service.ts`: llamadas HTTP a `/api/orders`.

Falta el `package.json`/`angular.json` reales — aqui hay acceso a npm pero no a packagist, asi que se prioriza el backend. Estos componentes son para copiar dentro de un proyecto Angular ya inicializado con `ng new` + `npm install primeng primeicons`.
- `database/migrations`: tablas `tables` y `orders` (items como json por simplicidad).

## Arrancar el entorno

```
docker-compose up -d
```

## Pendiente

Instalar Laravel real con composer (aqui no hay acceso a packagist), copiar estos archivos dentro y registrar el binding en un ServiceProvider:

```php
$this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
```
