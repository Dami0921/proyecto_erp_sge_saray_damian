# AgroInsumos El Cultivador S.A.S. — ERP (Laravel 12)

![Tests](https://github.com/Dami0921/proyecto_erp_sge_saray_damian/actions/workflows/tests.yml/badge.svg)

Sistema de gestión empresarial para un almacén de insumos agrícolas: inventario por lotes con vencimiento (FEFO), proveedores, compras con recepción parcial, clientes, ventas, kardex, auditoría, dashboard con KPIs, PDF, API REST, intercambio con Excel, notificaciones automáticas y un módulo de Configuración con roles, permisos y rol activo.

**Etiquetas del repositorio:** `avance-2` (segundo corte) · `final` (entrega final) · **Documentos y capturas:** `docs/`

## Módulos

| Módulo | Qué hace |
|---|---|
| Inventario | Productos, categorías y lotes con vencimiento; papelera (Soft Delete); ajustes con motivo |
| Kardex | Historial de entradas y salidas por producto con saldo; el stock solo cambia por movimientos |
| Proveedores / Clientes | CRUD con papelera y restaurar |
| Compras | Orden de compra → recepción total o parcial (crea lotes y sube el stock) → PDF |
| Ventas | Cabecera + detalle, descuento FEFO, número secuencial, anulación con devolución de stock, comprobante PDF |
| Dashboard | KPIs filtrados por permisos, Top 5 de productos y tabla de productos para reabastecer |
| Auditoría | Bitácora de cambios (antes → después) en 7 modelos y bitácora de seguridad |
| API REST v1 | Login con token (Sanctum) y rol activo, productos, lotes por vencer y ventas |
| Excel | Exportar productos y ventas; importar productos con validación fila por fila |
| Notificaciones | Alertas por correo y campana: stock bajo, mercancía recibida y lotes por vencer |
| Organización | Identidad corporativa de la empresa: nombre, NIT, logo, color, eslogan; se aplica a menú, login, portada, PDF y correos |
| Cargas masivas | Importar productos, clientes y proveedores desde Excel/CSV con validación fila por fila |
| Configuración | Usuarios con varios roles y permisos directos (con vista de permisos efectivos), matriz de permisos por rol, panel de módulos activables |

## Instalación y ejecución paso a paso

> Todo el proyecto corre dentro de contenedores Docker (Laravel Sail). **No hace falta instalar PHP, Composer, Node ni MySQL** en el computador. Esta guía fue validada en Windows 11 + WSL2 (Ubuntu) + Docker Desktop; en Linux y macOS los pasos son los mismos.

### 1. Programas que deben estar instalados antes de empezar

| Programa | Versión / detalle | Cómo comprobarlo |
|---|---|---|
| **Docker Desktop** | Abierto y en estado "Running" (ballena fija en la barra de tareas) | `docker --version` y `docker compose version` |
| **WSL2 + Ubuntu** (solo Windows) | Docker Desktop → Settings → Resources → WSL integration → activar la distro Ubuntu | `wsl -l -v` (VERSION = 2) |
| **Git** | Cualquier versión reciente | `git --version` |
| Conexión a internet | La primera instalación descarga imágenes y paquetes (varios minutos) | — |

Requisitos del equipo: ~8 GB de RAM libres y ~10 GB de disco.

**Puertos libres:** 80 (aplicación), 3306 (MySQL), 8025 y 1025 (Mailpit) y 5173 (Vite). Si alguno está ocupado, agregue en `.env` (paso 3) el que cambie, por ejemplo `APP_PORT=8080`, `FORWARD_DB_PORT=3307`, `FORWARD_MAILPIT_DASHBOARD_PORT=8026`, y use ese puerto en la URL.

**Importante en Windows:** ejecute todos los comandos desde la terminal de **Ubuntu (WSL)** y clone el proyecto dentro del sistema de archivos de Linux (por ejemplo `~/`), **no** en `/mnt/c/...` (es muy lento y da errores de permisos).

### 2. La base de datos

No hay que instalar ni crear nada: **MySQL 8.4 se levanta en un contenedor** junto con la aplicación y la base se crea sola con estos datos (ya vienen en `.env.example`):

| Dato | Valor |
|---|---|
| Servidor (`DB_HOST`) | `mysql` (nombre del contenedor; desde su PC: `127.0.0.1:3306`) |
| Base de datos | `laravel` (y `testing` para las pruebas) |
| Usuario / contraseña | `sail` / `password` |

Las tablas y los datos de demostración los crea el comando `migrate:fresh --seed` del paso 6. La primera vez MySQL tarda ~30 s en estar listo.

### 3. Instalación

Ejecute en orden, sin saltar pasos:

```bash
# 1) Descargar el proyecto
git clone https://github.com/Dami0921/proyecto_erp_sge_saray_damian.git
cd proyecto_erp_sge_saray_damian
git checkout final                 # versión entregada

# 2) Crear el archivo de configuración (no requiere editar nada)
cp .env.example .env

# 3) Instalar las dependencias PHP (carpeta vendor/) usando Docker
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest composer install --ignore-platform-reqs

# 4) Levantar los contenedores (app, MySQL, Mailpit). Usar sail, no "docker compose" directo
./vendor/bin/sail up -d
./vendor/bin/sail ps               # los 3 servicios deben decir "Up" (mysql: "healthy")

# 5) Generar la llave de la aplicación
./vendor/bin/sail artisan key:generate

# 6) Crear las tablas y cargar los datos de demostración (esperar ~30 s tras el paso 4)
./vendor/bin/sail artisan migrate:fresh --seed

# 7) Instalar y compilar el frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Resultado esperado: el paso 6 termina listando migraciones en `DONE` y los seeders sin errores; el paso 7 termina con `✓ built in ...`.

**Comodidad (opcional):** `alias sail='./vendor/bin/sail'` para escribir `sail artisan ...` en lugar de la ruta larga (para dejarlo fijo, agregue esa línea a `~/.bashrc`).

### 4. Ejecución

Abra <http://localhost> e ingrese con uno de los usuarios de la tabla "Usuarios de prueba" (contraseña `password`), por ejemplo `admin@test.com`.

| Servicio | URL |
|---|---|
| Aplicación | <http://localhost> |
| Mailpit (correos de prueba) | <http://localhost:8025> |

**Correos y campana de notificaciones:** se envían por cola. Deje corriendo en una **segunda terminal**:

```bash
./vendor/bin/sail artisan queue:work
```

Sin el worker la aplicación funciona igual; solo quedan pendientes los correos.

### 5. Uso diario

```bash
./vendor/bin/sail up -d            # encender
./vendor/bin/sail stop             # apagar (conserva los datos)
./vendor/bin/sail down             # apagar y borrar contenedores (conserva los datos)
./vendor/bin/sail down -v          # apagar y BORRAR la base de datos (reinicio total; repetir pasos 4 a 6)
./vendor/bin/sail artisan migrate:fresh --seed   # reiniciar solo los datos de demostración
```

### 6. Verificación rápida de que todo quedó bien

1. `sail artisan test` termina en verde (más de 100 pruebas).
2. <http://localhost> muestra la pantalla de inicio y el login entra con `admin@test.com` / `password`.
3. El Dashboard muestra datos (productos, ventas, lotes).
4. En Configuración → Organización se puede cambiar nombre, color y logo.
5. Con `queue:work` activo, una venta que deja stock bajo genera un correo en Mailpit y una notificación en la campana.

### 7. Solución de problemas

| Síntoma | Causa y solución |
|---|---|
| `docker: command not found` o `Cannot connect to the Docker daemon` | Docker Desktop no está abierto o falta activar la integración WSL (Settings → Resources → WSL integration). |
| `port is already allocated` / `address already in use` | Otro programa usa ese puerto (80, 3306, 8025...). Cambie el puerto en `.env` (ver punto 1) y ejecute `sail up -d` de nuevo. |
| `permission denied: ./vendor/bin/sail` | `chmod +x vendor/bin/sail`. |
| `sail: vendor/autoload.php not found` o `vendor/bin/sail: No such file` | Faltó el paso 3 (composer install). |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL aún está arrancando: espere 30 s, verifique `sail ps` y repita el comando. |
| `SQLSTATE[HY000] [1045] Access denied` | Quedó un volumen de MySQL antiguo: `sail down -v`, `sail up -d`, espere 30 s y repita `migrate:fresh --seed`. |
| `No application encryption key has been specified` | Falta el paso 5: `sail artisan key:generate`. |
| `Vite manifest not found` / página sin estilos | Falta `sail npm run build` (paso 7). |
| `Permission denied` en `storage/logs` o `bootstrap/cache` | `sail exec -u root laravel.test chmod -R 777 storage bootstrap/cache`. |
| No llegan correos a Mailpit | El worker no está corriendo: `sail artisan queue:work`. |
| `Base table or view already exists` | La base tenía datos a medias: `sail artisan migrate:fresh --seed`. |

## Cola de correos y tareas programadas

Las notificaciones se envían por **cola** (`QUEUE_CONNECTION=database`): si el worker no está corriendo, los correos quedan esperando en la tabla `jobs` y la venta no se afecta. Para ver los correos y la campana funcionando:

```bash
sail artisan queue:work          # terminal aparte: procesa la cola y envía los correos a Mailpit
sail artisan schedule:work       # opcional en desarrollo: ejecuta las tareas programadas cada minuto
sail artisan schedule:list       # muestra las tareas programadas
```

## Pruebas, estilo y CI

```bash
sail artisan test                # más de 100 pruebas de reglas de negocio, seguridad, API, Excel y notificaciones
sail bin pint --test             # estilo de código (Laravel Pint); sin --test corrige los archivos
```

GitHub Actions (`.github/workflows/tests.yml`) corre en cada push a `main` y en cada Pull Request: instala dependencias, revisa el estilo con Pint, compila el frontend y ejecuta las pruebas contra un MySQL real.

## Usuarios de prueba

Contraseña de **todos**: `password`

| Correo | Rol(es) | Para probar |
|---|---|---|
| admin@test.com | admin | Todo, incluida la Configuración, la auditoría y el kardex |
| vendedor@test.com | vendedor | Clientes y ventas; recibe 403 en compras, kardex y configuración |
| almacenista@test.com | almacenista | Productos, lotes, proveedores, compras con recepción, kardex y ajustes; 403 en ventas |
| contador@test.com | contador | Solo consulta: ventas, compras, kardex, auditoría y reportes; exporta a Excel; 403 en todo lo que escriba |
| multirol@test.com | vendedor + almacenista | Al iniciar sesión **elige el rol activo**; sus permisos cambian según el rol elegido |
| supervisor@test.com | vendedor + permiso directo `eliminar-ventas` | Puede anular ventas aunque el rol vendedor no lo permita |

Las cuentas creadas desde «Registrarse» **no tienen rol** (mínimo privilegio): ven un aviso hasta que un administrador les asigne uno en Configuración.

## Datos de ejemplo (seeders)

`sail artisan db:seed` (o `migrate:fresh --seed`) carga, en este orden: módulos del sistema, **4 roles con 42 permisos**, la identidad corporativa inicial de la empresa, **6 usuarios** (uno por rol, un multirol y uno con permiso directo), 10 categorías, 10 proveedores, 10 clientes, 10 productos con precio e IVA, los lotes iniciales (cada uno entra al kardex como «inventario inicial») y movimientos de demostración: órdenes de compra (recibida, parcial y pendiente) y ventas (una anulada). Los seeders se pueden repetir sin duplicar roles ni usuarios.

## Permisos por rol

| Módulo | admin | vendedor | almacenista | contador |
|---|:-:|:-:|:-:|:-:|
| Productos | todo | ver | ver, crear, editar | ver |
| Categorías | todo | — | ver | ver |
| Proveedores | todo | — | ver, crear, editar | ver |
| Clientes | todo | ver, crear, editar | — | ver |
| Lotes | todo | — | ver, crear, editar | ver |
| Compras | todo (anular) | — | ver, crear, recibir | ver |
| Ventas | todo (anular) | ver, crear | — | ver |
| Kardex | ver, ajustar | — | ver, ajustar | ver |
| Auditoría y reportes | ✔ | — | — | ✔ |
| Excel | exportar e importar todo | importar clientes | productos y proveedores: importar; productos: exportar | exportar productos y ventas |
| Organización (identidad) | ✔ | — | — | — |
| Configuración | ✔ | — | — | — |

La matriz completa se edita en Configuración → Roles. Un permiso **por rol** vale para todos los usuarios con ese rol; un permiso **directo** se le da a una persona concreta (ej.: `supervisor@test.com` y `eliminar-ventas`).

## Personalización: un ERP para cualquier empresa

El sistema no está atado a la distribuidora de insumos. Una empresa nueva lo adapta **sin tocar código**, con cuatro herramientas (todas en el menú Configuración):

| Herramienta | Qué permite | Dónde |
|---|---|---|
| **Módulos activables** | Apagar lo que la empresa no usa (Proveedores, Clientes, Compras, Ventas, Inventario). El módulo desaparece del menú, del dashboard, de la API, de las alertas y de la tarea programada; sus rutas responden 404. Respeta dependencias (Ventas y Compras requieren Inventario) y el núcleo no se apaga | Configuración → Módulos del sistema |
| **Organización (identidad corporativa)** | Nombre, NIT, dirección, teléfono, correo, eslogan, mensaje de bienvenida, **logo** y **color principal** (6 paletas). Se aplica en el menú, el login, la portada, los PDF y los correos | Configuración → Organización |
| **Roles y permisos a la medida** | Crear roles por puesto de trabajo, marcar permisos por módulo y acción, dar permisos directos y revisar los **permisos efectivos de cada usuario** | Configuración → Roles / Usuarios → Permisos |
| **Carga inicial de datos** | Importar productos, clientes y proveedores desde Excel/CSV con plantilla, validación fila por fila y actualización por documento/NIT | Listados → Importar desde Excel |

**Ejemplo (ferretería sin proveedores):** apagar el módulo Proveedores, cambiar el nombre, el logo y el color en Organización, cargar su cartera de clientes con la plantilla de Excel y crear un rol «cajero» con solo ver productos y crear ventas. Las compras siguen funcionando porque el proveedor de una orden es opcional, y las ventas funcionan con o sin el módulo Clientes (cliente general).

**Alcance:** cada empresa usa **su propia instalación y base de datos** (una instalación por empresa). Compartir una misma base entre varias empresas (multiempresa con `empresa_id` en cada tabla) queda como trabajo futuro.

## Reglas de negocio principales

- Crear una orden de compra **no** mueve el inventario; el stock sube solo al recibir.
- No se recibe más de lo pedido, ni mercancía vencida, ni una recepción en cero.
- La venta descuenta primero el lote que vence antes (FEFO) y no vende lotes vencidos.
- Sin stock suficiente la venta se rechaza y no se guarda nada (`DB::transaction`).
- Anular una venta devuelve el stock a los mismos lotes; solo se anula una compra sin mercancía recibida.
- Números secuenciales `CV-00001` y `OC-00001`.
- **El stock solo cambia por movimientos:** únicamente `InventarioService` modifica `lotes.cantidad_actual` y cada cambio deja una fila en el kardex.
- Los ajustes de inventario exigen un motivo y quedan en el kardex con el usuario que los hizo.
- La contraseña nunca se registra en la auditoría.
- Alerta de stock bajo **solo cuando el producto cruza el umbral** (no se repite en cada venta) y nunca si la venta falló.
- Protecciones de seguridad: no se elimina al único administrador, nadie se quita a sí mismo el rol admin, todo usuario conserva al menos un rol, los roles base no se eliminan.

Detalle completo, matriz de seguridad y evidencias: `docs/avance2/`. Modelo de datos: `docs/diagrama_mer_v2.png` y `docs/diccionario.md`.

## Arquitectura

MVC + capa de servicios. Los controladores solo reciben la petición, validan con Form Requests y delegan; las reglas de negocio y las transacciones viven en `app/Services/` (`VentaService`, `CompraService`, `InventarioService`, `SeguridadService`), de modo que la web y la API usan exactamente las mismas reglas. Los hechos importantes del negocio se publican como eventos (`VentaRegistrada`, `CompraRecibida`) y los listeners reaccionan sin que el servicio sepa quién escucha.

## Flujo de demostración

1. Entrar como `almacenista@test.com`: crear una orden de compra y recibirla (total o parcial) → el stock sube y queda en el **kardex**; llega el correo con el PDF adjunto.
2. Entrar como `vendedor@test.com`: registrar una venta → descuenta por FEFO, genera el comprobante PDF; si el producto queda bajo el umbral, llega la alerta.
3. Ver la **campana** y el correo en Mailpit; abrir la notificación.
4. Como `admin@test.com`: dashboard con KPIs, **auditoría** (antes → después) y kardex.
5. Entrar como `multirol@test.com` y elegir el rol activo.
6. Petición a la API (colección Postman en `docs/api/`) y exportar/importar Excel.
7. Configuración → **Organización**: cambiar nombre, color y logo, y apagar un módulo para mostrar que el resto sigue funcionando.
8. `sail artisan test` en verde y la pestaña Actions de GitHub.

---

# Historial por clase

## Actividad: Personalización Visual del Sistema de Autenticación

Se personalizó la interfaz de autenticación para la empresa **AgroInsumos El Cultivador S.A.S.**, un almacén de insumos agrícolas.

### Cambios realizados
- Logo propio (ícono de hoja) reemplazando el logo de Laravel.
- Textos traducidos y adaptados al negocio (login, registro, dashboard).
- Paleta de colores verde institucional en botones, enlaces y fondo.
- Landing page nueva con hero, características del sistema y CTA de registro/login.
- Dashboard con tarjetas de indicadores (KPIs): productos, clientes, ventas, bajo stock.

### Paleta de colores
- Verde principal: `#15803d` (green-700)
- Fondo: `#f0fdf4` (green-50)
- Texto oscuro: `#166534` (green-800)

### Fuente
- Figtree (la fuente por defecto de Laravel Breeze)

### Capturas
Ver carpeta `docs/visual/`:
- `landing.png` — Página de inicio
- `login.png` — Inicio de sesión
- `register.png` — Registro de usuario
- `dash.png` — Panel principal (dashboard)
---

## Clase 10 – Compras y PDF

Módulos nuevos: **Proveedores** y **Compras** (ambos se pueden apagar desde el panel de módulos sin afectar al resto del sistema; Compras depende de Inventario).

### Tablas nuevas
| Tabla | Descripción |
|---|---|
| `proveedores` (+ `deleted_at`) | Nombre, NIT único, teléfono y dirección. Papelera con restaurar; no se elimina si tiene compras. |
| `compras` | Orden de compra `OC-00001…`: proveedor (opcional), fecha, subtotal, IVA, total y estado (`pendiente`, `parcial`, `recibida`, `anulada`). |
| `detalle_compras` | Producto, cantidad pedida, `cantidad_recibida`, costo unitario e IVA por línea. Un producto solo una vez por orden. |
| `lotes.detalle_compra_id` | Cada recepción crea un lote ligado a la línea de la orden (trazabilidad). |

### Reglas de negocio
- Crear una orden **no mueve el inventario**: el stock aumenta solo al recibir mercancía.
- **Recepción parcial:** se indica cuánto llega ahora, la fecha de vencimiento y el lote (opcional); la orden pasa a `parcial` hasta completar lo pedido y luego a `recibida`.
- No se puede recibir más de lo pedido ni mercancía ya vencida; debe recibirse al menos una unidad.
- Solo se anula una orden si no se ha recibido nada.
- El IVA es configurable por producto (`iva_porcentaje`, 0 = sin IVA) y se guarda en cada línea.
- Todo el proceso corre en transacción (`DB::transaction`) con bloqueo de filas.

### PDFs (barryvdh/laravel-dompdf)
- **Orden de compra:** `/compras/{id}/pdf` (permiso `ver-compras`).
- **Comprobante de venta:** `/ventas/{id}/pdf` (permiso `ver-ventas`; el almacenista recibe 403).
- Los datos de la empresa salen de `config/empresa.php` (variables `EMPRESA_NIT`, `EMPRESA_DIRECCION`, `EMPRESA_TELEFONO` en `.env`).

### Dashboard
KPIs nuevos: Compras del mes, Órdenes por recibir, Proveedores activos y Ventas − Compras del mes (solo admin, permiso `ver-reportes`), más la tabla «Productos para reabastecer» (stock ≤ 10) con acceso directo a nueva orden de compra.

### Permisos por rol
| Permiso | admin | vendedor | almacenista |
|---|:-:|:-:|:-:|
| ver / crear / editar proveedores | ✔ | — | ver, crear, editar |
| ver-compras, crear-compras | ✔ | — | ✔ |
| recibir-compras | ✔ | — | ✔ |
| eliminar-compras (anular) | ✔ | — | — |
| ver / crear-ventas y PDF del comprobante | ✔ | ✔ | — |

### Comandos
```bash
sail composer require barryvdh/laravel-dompdf
sail php artisan migrate
sail php artisan db:seed --class=RolePermissionSeeder
sail php artisan permission:cache-reset
sail npm run build
```

### Capturas
Ver `docs/clase_10/capturas/`.

---

## Clase 12 – Auditoría y kardex

### Tabla nueva
| Tabla | Descripción |
|---|---|
| `movimientos_inventario` | Kardex: producto, lote, tipo (entrada/salida), concepto, cantidad, **saldo** del producto, costo, documento de origen (morph) y usuario |
| `activity_log` | Bitácora de auditoría (spatie/laravel-activitylog) |

Conceptos del kardex: `inventario_inicial`, `compra`, `venta`, `anulacion_venta`, `ajuste`, `baja_vencimiento`. El saldo de un producto es la suma de todos sus lotes.

### Cómo se audita
- Trait `Auditable` en 7 modelos: Producto, Categoría, Cliente, Proveedor, Venta, Compra y Usuario. Cada cambio guarda **antes → después**; la contraseña se excluye.
- Bitácora de **seguridad** (`activity('seguridad')`): creación y cambio de usuarios, cambios de roles y de la matriz de permisos.
- Pantallas: Inventario → Kardex (filtros por producto, fechas y concepto) y Auditoría (solo `ver-auditoria`).

### Comandos
```bash
sail artisan migrate
sail artisan inventario:inicializar-kardex   # crea el movimiento inicial de lotes que existían antes del kardex
```

### Capturas
Ver `docs/clase_12/capturas/`.

---

## Clase 13 – API REST y Excel

### API v1 (`/api/v1`, Laravel Sanctum)
| Método y ruta | Descripción | Permiso |
|---|---|---|
| `POST /login` | Entrega un token Bearer con el **rol activo** (`email`, `password`, `device_name`, `rol` si tiene varios). Máximo 5 intentos por minuto | pública |
| `GET /me` | Usuario, rol activo y permisos efectivos | token |
| `POST /logout` | Revoca el token | token |
| `GET /productos`, `GET /productos/{id}` | Catálogo con stock vigente (paginado) | `ver-productos` |
| `GET /lotes/por-vencer` | Lotes que vencen pronto | `ver-lotes` |
| `GET /ventas/{id}` | Detalle de una venta | `ver-ventas` |
| `POST /ventas` | Registra una venta con **las mismas reglas** que la web (FEFO, transacción, kardex) | `crear-ventas` |

Respuestas siempre en JSON: sin token **401**, sin permiso **403**, datos inválidos o sin stock **422**. Colección de pruebas: `docs/api/ERP_API.postman_collection.json` y entorno `ERP_Local.postman_environment.json`.

### Excel (maatwebsite/excel)
- **Exportar:** productos y ventas por rango de fechas (incluye las anuladas, marcadas).
- **Importar productos:** plantilla descargable, validación **fila por fila** (se informan las filas con error y se importan las válidas) y **nunca toca el stock**.

### Capturas
Ver `docs/clase_13/capturas/`.

---

## Clase 14 – Eventos, notificaciones y tareas programadas

| Pieza | Descripción |
|---|---|
| Evento `VentaRegistrada` → listener `RevisarStockBajo` | Si la venta deja el producto en stock bajo (≤ `ERP_STOCK_BAJO`, por defecto 10) **cruzando el umbral**, avisa a quienes pueden crear compras |
| Evento `CompraRecibida` → listener `NotificarCompraRecibida` | Avisa a quien creó la orden; el correo lleva el **PDF de la orden adjunto** |
| Notificaciones `StockBajo`, `CompraRecibida`, `LotesPorVencer` | Canales **correo + base de datos (campana)**, en cola (`ShouldQueue`) |
| Tarea `erp:lotes-por-vencer` | Cada día a las 7:00 (hora de Bogotá): resumen de lotes con existencias que vencen en los próximos `ERP_DIAS_ALERTA_VENCIMIENTO` (30) días, a quienes ven lotes |
| Campana | Contador de no leídas en el menú; pantalla de notificaciones con «Abrir» y «Marcar todas»; cada usuario solo ve las suyas |

Los eventos usan `ShouldDispatchAfterCommit`: salen solo si la transacción se confirmó, así una venta que falla no genera alertas falsas. Parámetros en `config/erp.php` (variables `ERP_*` en `.env`).

### Comandos
```bash
sail artisan migrate                                    # tablas notifications y jobs
sail artisan queue:work                                 # worker (terminal aparte)
sail artisan erp:lotes-por-vencer --dias=365            # forzar el aviso con los datos de prueba
sail artisan schedule:list
```
Correos de prueba en Mailpit: <http://localhost:8025>.

### Capturas
Ver `docs/clase_14/capturas/`.

---

## Clase 15 – Pruebas, CI y producción

- **Pruebas:** más de 100 pruebas automatizadas (`tests/Feature/Reglas/`), una clase por tema: seguridad, ventas, compras, dashboard/PDF y Clases 12 a 15.
- **CI:** GitHub Actions (`.github/workflows/tests.yml`) con Pint, build del frontend y pruebas sobre MySQL.
- **Cuarto rol (`contador`):** rol de consulta; separa quien registra de quien audita.

### Puesta en producción (resumen)
```bash
cp .env.example .env                 # APP_ENV=production, APP_DEBUG=false, APP_URL, credenciales reales de BD y de correo
php artisan key:generate
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
npm ci && npm run build
```
Además: un **worker** permanente de la cola (`php artisan queue:work`, administrado con Supervisor o systemd) y una entrada de cron para las tareas programadas: `* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1`.

### Capturas
Ver `docs/clase_15/capturas/`.

---

## Personalización: organización, módulos, permisos e importaciones

Respuesta a las recomendaciones del profesor para que el ERP sirva a cualquier empresa.

### Organización (identidad corporativa)
- Tabla `organizacion` (una sola fila): nombre, NIT, dirección, teléfono, correo, eslogan, descripción, color y logo.
- El logo (PNG o JPG, máximo 300 KB) se guarda como *data URI* en la base de datos: viaja con ella y no requiere `storage:link`. No se admite SVG (puede traer scripts).
- El color elige una paleta; el verde original no agrega ajustes y las otras redefinen las clases de color del sistema (`resources/views/layouts/tema.blade.php`).
- Permiso `gestionar-organizacion` (admin). Los cambios quedan auditados (antes → después; el logo se anota aparte).
- `config/empresa.php` (variables `EMPRESA_*`) solo da los valores iniciales que carga `OrganizacionSeeder`.

### Módulos apagables de punta a punta
Además del menú y las rutas web, un módulo apagado también desactiva: el recurso equivalente de la API (404 en JSON), la alerta de stock bajo (sin Compras no hay a quién invitar a comprar) y la tarea de lotes por vencer (sin Inventario no hay lotes). Cubierto por `ModulosTest`.

### Permisos efectivos por usuario
Configuración → Usuarios → **Permisos**: tabla de módulos con una columna por rol del usuario y otra de permisos directos, módulos sin acceso y aviso de módulos desactivados.

### Cargas masivas
Productos (Clase 13), **clientes** (por documento) y **proveedores** (por NIT): plantilla descargable, validación fila por fila (se omiten y se reportan las filas con error), conversión de celdas numéricas de Excel a texto, y no se duplican ni se reviven registros de la papelera. Permisos `importar-clientes` e `importar-proveedores`.

### Comandos al actualizar una base existente
```bash
sail artisan migrate
sail artisan db:seed --class=OrganizacionSeeder
sail artisan db:seed --class=RolePermissionSeeder
sail npm run build
```

### Capturas
Ver `docs/personalizacion/capturas/`.
