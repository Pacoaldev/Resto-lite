# Resto Lite — Ecosistema Digital de Sala y Comandas

Este repositorio contiene la implementación de **Resto Lite**, un MVP transversal y resiliente orientado a la digitalización de la operativa diaria en restaurantes independientes del mercado español y latinoamericano (México, Chile, Colombia, Argentina y Perú).

---

## 🏗️ Arquitectura del Sistema (Backend DDD & Hexagonal)

El backend de Resto Lite está construido en **Laravel 10** bajo el paradigma de **Arquitectura Hexagonal (Domain-Driven Design)**. Esta separación garantiza que el dominio del negocio esté aislado de los detalles de infraestructura (base de datos, llamadas HTTP externas, framework).

### Estructura de Capas
- **Domain (Núcleo):** Contiene las entidades, valor de objetos e interfaces de abstracción que rigen las reglas de negocio (ej. [`TaxCalculatorInterface.php`](file:///c:/Users/spano/Documents/PROYECTOS/Resto-lite/app/Orders/Domain/TaxCalculatorInterface.php)). No tiene dependencias de librerías externas.
- **Application (Casos de Uso):** Orquesta los flujos de la aplicación y despacha eventos (ej. creación de órdenes).
- **Infrastructure (Persistencia y Adaptadores):** Detalles de implementación técnica. Resuelve las consultas a base de datos (Eloquent), interactúa con colas (Redis), provee controladores HTTP y consume APIs de terceros (ERP Maestro).

```
   [ Cliente / API HTTP ] ────────> [ Adaptador API / Controllers ]
                                                │
                                                ▼ (puerto de entrada)
                                      [ Casos de Uso / App ]
                                                │
                                                ▼
                                    [ Dominio de Negocio ]
                                                │
                          ┌─────────────────────┴─────────────────────┐
                          ▼ (puerto de salida)                        ▼ (puerto de salida)
             [ Repositorio persistencia ]               [ Cliente ERP Maestro / Worker ]
                          │                                           │
                          ▼                                           ▼
                    [ Eloquent / DB ]                           [ Redis / Queue ]
```

---

## ⚡ Resiliencia y Flujo Asíncrono (Eventos y Colas)

El flujo de pedidos utiliza un sistema asíncrono para garantizar que el restaurante nunca se detenga:

1. **Creación de Orden:** El controlador registra la comanda en base de datos de manera transaccional e inmediatamente dispara el evento `OrderCreatedEvent`.
2. **Listener Asíncrono:** `SyncOrderToErpMaestroListener` captura el evento y lo encola en **Redis** (`QUEUE_CONNECTION=redis`).
3. **Resiliencia de Workers:** El worker procesa la llamada al ERP Maestro simulado. Si el servidor externo está offline, un bloque `try-catch` robusto captura la excepción, escribe un log de advertencia y gestiona reintentos con retraso progresivo (`$tries = 5`, `$backoff = 10`), evitando bloqueos en el hilo de ejecución principal.

---

## 🛡️ Estrategia de Impuestos (España y LATAM)

Utilizamos el patrón de diseño **Estrategia (Strategy Pattern)** para resolver dinámicamente el cálculo de impuestos de cada comanda según la localización del local, controlado por la variable de entorno `TAX_COUNTRY`:

| País | Estrategia | Tasa Aplicada |
|:---|:---|:---:|
| **España** (Defecto) | `SpainTaxCalculator` | 21% IVA |
| **México** | `MexicoTaxCalculator` | 16% IVA |
| **Chile** | `ChileTaxCalculator` | 19% IVA |
| **Colombia** | `ColombiaTaxCalculator` | 19% IVA |
| **Argentina** | `ArgentinaTaxCalculator` | 21% IVA |
| **Perú** | `PeruTaxCalculator` | 18% IGV |

La resolución de la estrategia se inyecta dinámicamente a través del Service Container en [`AppServiceProvider.php`](file:///c:/Users/spano/Documents/PROYECTOS/Resto-lite/app/Providers/AppServiceProvider.php).

---

## 📱 Frontend Offline-First (Angular + Ionic)

El ecosistema cuenta con dos interfaces frontend construidas sobre **Angular 17+**:

### 1. Panel de Control de Sala (Web / PrimeNG)
- Tablero interactivo de mesas con actualización y toma de comandas dinámica.
- **Resiliencia Offline:** El servicio intercepta pérdidas de conexión a red. Si el backend no responde, almacena temporalmente los pedidos en `LocalStorage` con estado `offline_pending`.
- **Auto-Sync:** Un temporizador periódico en segundo plano monitorea la recuperación del canal y sincroniza secuencialmente las comandas pendientes sin intervención humana.

### 2. Monitor de Sala Móvil (Ionic / Capacitor)
- Inicializado en la carpeta `/mobile`.
- Diseñado específicamente para smartphones de sala. Lista el estado en tiempo real de las mesas del restaurante con una interfaz táctil limpia y ágil.

---

## 🛠️ Guía Rápida de Comandos y Calidad (QA)

### Iniciar el entorno de desarrollo (Docker)
```bash
# Levantar contenedores (Laravel, MySQL, Redis)
docker-compose up -d

# Instalar dependencias backend
docker-compose exec app composer install
```

### Ejecutar Suite de Calidad y Pruebas del Backend
```bash
# Pruebas Unitarias de Impuestos (Pest)
docker-compose exec app ./vendor/bin/pest

# Análisis Estático de Código (PHPStan Nivel 5)
docker-compose exec app ./vendor/bin/phpstan analyse
```

### Ejecutar Suite del Frontend Web
```bash
# Instalar e iniciar servidor de desarrollo Angular
cd frontend
npm install
npm run start

# Ejecutar tests Cypress E2E en consola
npm run cypress:run
```

### Ejecutar Suite de la App Móvil
```bash
cd mobile
npm install
npm run build
```
