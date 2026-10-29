# Black Multimarca

Tienda online en español para catálogo de indumentaria, hogar y productos multimarca. Incluye catálogo con categorías y marcas, variantes por talle/color, stock, carrito de sesión, descuentos por cantidad, checkout con retiro o envío, pedidos con pago por transferencia y comprobantes privados, y un panel administrativo.

## Stack comprobado

- Laravel `13.39.0` en `composer.lock`; la aplicación declara Laravel `^13.17` y PHP `^8.3`.
- PHP `8.5` en las imágenes Docker y CI; la copia local se verificó con PHP `8.5.12`.
- MySQL `8.4` para el entorno Docker del proyecto; SQLite en memoria para la configuración local de PHPUnit.
- Tailwind CSS `4`, Vite `8` y Node.js `22` para frontend y build.
- PHPUnit `^12.5` y Laravel Pint `^1.27` declarados como dependencias de desarrollo.

## Requisitos

Para el flujo Docker: Docker Engine y Docker Compose. Para trabajar sin Docker: Node.js y npm compatibles con el lockfile, PHP 8.3 o superior, Composer y una base compatible; se recomienda MySQL 8.4, que es el motor configurado por `docker-compose.yml`.

## Desarrollo con Docker

1. Copiá `.env.example` como `.env`.
2. Ejecutá `docker compose up --build -d`.
3. Ejecutá `docker compose exec app php artisan key:generate` y `docker compose exec app php artisan migrate --seed --no-interaction`.
4. Abrí `http://localhost:8000`; Vite corre en el contenedor `frontend`.

La app instala Composer al iniciar cuando `vendor_data` está vacío; Vite instala npm cuando `node_modules_data` está vacío. El servicio `scheduler` ejecuta las expiraciones y limpiezas programadas. Si cambian los lockfiles, ejecutá `docker compose exec app composer install --no-interaction --prefer-dist` y `docker compose run --rm frontend npm ci`. Detené el entorno con `docker compose stop`, reconstruí con `docker compose up --build -d` y cerralo con `docker compose down`. MySQL, Composer y Node persisten en volúmenes Docker; `docker compose down` los conserva. No uses `docker compose down -v` salvo que quieras borrar deliberadamente esos datos.

El usuario administrativo de muestra no tiene una contraseña predeterminada. Para datos de desarrollo, definí `ADMIN_EMAIL` y `ADMIN_PASSWORD` en `.env` antes de ejecutar `db:seed`. El seeder crea una cuenta nueva con esa contraseña y no promueve cuentas existentes. En producción, usá `php artisan admin:create correo@dominio` desde una terminal interactiva; el comando pide la contraseña sin mostrarla, la valida y solicita confirmación antes de promover una cuenta existente. No uses datos de demostración ni credenciales de desarrollo en un entorno real.

## Imagen de producción

`Dockerfile.production` tiene objetivos separados para PHP-FPM y Nginx. Instala dependencias PHP con `composer.lock`, compila el frontend con `npm ci`, ejecuta PHP-FPM como `www-data` y usa Nginx sin privilegios. `docker-compose.production.yml` usa nombres de imágenes y proyecto separados de desarrollo, no monta el código del host ni inicia una base de datos: requiere un MySQL externo, un archivo local con credenciales y `APP_KEY`, y un proxy HTTPS en el host. El puerto HTTP publicado queda ligado a `127.0.0.1`; `SESSION_SECURE_COOKIE` se fuerza a verdadero. `TRUSTED_PROXIES=*` es apropiado solo porque PHP-FPM no publica puertos y Nginx es el único contenedor que puede alcanzarlo; el proxy HTTPS externo debe sobrescribir `X-Forwarded-Proto` y no aceptar ese valor directamente del cliente.

Pasos de preparación local:

```bash
cp .env.production.example .env.production
# Configurá APP_URL, credenciales de MySQL, APP_KEY y el proxy HTTPS antes de iniciar servicios.
export PRODUCTION_ENV_FILE=.env.production
docker compose -f docker-compose.production.yml build
docker compose -f docker-compose.production.yml run --rm app php artisan key:generate --show
# Copiá esa clave directamente a APP_KEY dentro de .env.production; no la compartas ni la guardes en la imagen.
# Hacé y verificá un backup de MySQL y storage antes de migrar.
docker compose -f docker-compose.production.yml run --rm app php artisan migrate --force
docker compose -f docker-compose.production.yml up -d
docker compose -f docker-compose.production.yml exec app php artisan admin:create admin@tu-dominio
docker compose -f docker-compose.production.yml ps
docker compose -f docker-compose.production.yml logs -f app web scheduler
```

El volumen `storage_data` conserva comprobantes privados, sesiones y archivos públicos; Nginx lo monta solo lectura. TLS, MySQL, secretos, backups/restauración y supervisión del host son externos y deben configurarse y probarse antes de recibir tráfico real. No hay despliegue automatizado ni rollback de base de datos. Conservá una imagen anterior y un backup consistente antes de migrar; revertir las migraciones de historial y movimientos elimina esos datos.

## Flujo de pedidos y stock

Los precios se almacenan y calculan como enteros en guaraníes (₲), sin `float`. El descuento se configura en Ajustes como un mínimo de unidades y un porcentaje único; por ejemplo, con el valor inicial, tres o más unidades habilitan un 10% sobre el subtotal. El costo de envío se agrega después del descuento. La compra vuelve a cargar y bloquear las variantes al crear el pedido, toma el precio y los ajustes vigentes en ese momento y descuenta stock junto con la creación de pedido y líneas.

Las claves de checkout se guardan en el pedido con una restricción única para evitar duplicados por reintentos; los pedidos anteriores a la migración quedan sin clave. Una nueva modificación del carrito crea un intento distinto. Los pedidos conservan nombre, SKU, etiqueta de variante, precio, cantidad y total de línea como instantáneas históricas. Al adjuntar un comprobante, el pedido queda en revisión; el archivo se guarda en el disco privado `local` y solo lo ve el dueño del pedido o un administrador.

| Estado actual | Transiciones permitidas por el flujo administrativo |
| --- | --- |
| Esperando transferencia | En revisión, pago confirmado o cancelado |
| Comprobante en revisión | Pago confirmado o cancelado |
| Pago confirmado | Enviado / listo para retirar |
| Enviado / listo para retirar | Entregado |
| Entregado | Terminal |
| Cancelado | Terminal |

Los pedidos impagos vencen según `reserve_hours` (48 horas por defecto); el scheduler cancela los vencidos y libera stock en una transacción bajo bloqueo del pedido y las variantes. Los pedidos con comprobante en revisión se cancelan manualmente si se rechazan. Los movimientos de devolución son únicos por línea y no se permite cancelar por el flujo ordinario pedidos pagados, enviados o entregados. No existe un flujo de reembolsos. Los comprobantes huérfanos sin referencia en pedidos se eliminan tras 48 horas por el proceso programado `receipts:prune-orphans`.

El historial de estados y los movimientos de inventario comienzan con las operaciones posteriores a estas migraciones; no se inventan eventos pasados. Los pedidos ya cancelados reciben una marca de stock devuelto para impedir nuevas devoluciones, pero no se reconstruye un libro de movimientos histórico.

## Roles y rutas

- Visitantes: inicio, catálogo, fichas, carrito, checkout, registro e inicio de sesión.
- Clientes autenticados: cuenta y pedidos propios. Las compras de invitado se muestran únicamente desde la sesión que las creó.
- Administradores: panel, catálogo, categorías, marcas, ajustes, pedidos y comprobantes; las rutas administrativas usan `auth` y `admin`.

El registro público no acepta el campo `is_admin`. La cuenta se eleva manualmente mediante datos de administración controlados; el seeder no incluye contraseñas por defecto.

## Pruebas y calidad

La suite general de PHPUnit usa SQLite en memoria. Ejecutá:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
composer audit --locked
composer analyse
npm ci
npm audit
npm run build
```

La suite incluye pruebas de competencia de checkout y cancelación que usan procesos paralelos y requieren MySQL; se omiten con SQLite. CI corre toda la suite contra MySQL 8.4 desechable, ejecuta Composer/npm audit, Pint, Larastan/PHPStan y el build frontend.

## Estructura relevante

- `app/Actions`: checkout, comprobantes y transición/cancelación de pedidos.
- `app/Support/Cart.php`: carrito de sesión y cálculo de resumen/descuentos.
- `app/Enums/OrderStatus.php`: estados y transiciones permitidas.
- `app/Http/Controllers`: controladores web de tienda, cuenta y administración.
- `database/migrations`, `database/factories`, `database/seeders`: esquema y datos de desarrollo.
- `resources/views`: interfaz Blade.
- `routes/web.php`: rutas web y middleware.
- `tests/Feature`: pruebas de flujos de tienda y administración.

## Almacenamiento, operación y limitaciones

El disco Laravel `local` apunta a `storage/app/private`; los comprobantes no tienen una URL pública y se sirven mediante una ruta autorizada. Los formatos aceptados son JPG, PNG, WebP y PDF; el límite predeterminado de 5 MB se puede ajustar con `RECEIPT_MAX_KILOBYTES` (entre 1 KB y 50 MB). Las subidas fallidas se compensan borrando el archivo recién creado y los comprobantes reemplazados se eliminan luego de guardar la referencia nueva. Una terminación abrupta puede dejar un archivo huérfano temporal; la tarea diaria lo elimina si sigue sin referencia tras la retención configurada. Las imágenes de catálogo usan el disco `public`. Configurá copias de seguridad tanto de MySQL como de `storage/app/private` y `storage/app/public` antes de operar datos reales; probá la restauración fuera del entorno de producción.

El Dockerfile sin sufijo es solo para desarrollo y ejecuta `php artisan serve`; el objetivo de producción usa PHP-FPM y Nginx. Ambos objetivos usan PHP 8.5. CI queda preparado en GitHub Actions, pero no se ejecutó remotamente. Antes de producción, verificá de forma operativa el proxy TLS y sus encabezados, MySQL externo, almacenamiento persistente, alertas de healthcheck, backups y restauración. No se desplegó esta copia ni se validó contra infraestructura real.
