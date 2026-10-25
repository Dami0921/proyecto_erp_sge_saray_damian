# AgroInsumos El Cultivador S.A.S. — ERP (Laravel 10)

Sistema de gestión empresarial para un almacén de insumos agrícolas: inventario por lotes con vencimiento, proveedores, compras con recepción parcial, clientes, ventas con descuento FEFO, dashboard con KPIs, PDF, y un módulo de Configuración con roles, permisos y rol activo.

**Tag de la entrega del segundo corte:** `avance-2` · **Documentos y capturas:** `docs/avance2/`

## Módulos

| Módulo | Qué hace |
|---|---|
| Inventario | Productos, categorías y lotes; papelera (Soft Delete) |
| Proveedores / Clientes | CRUD con papelera y restaurar |
| Compras | Orden de compra → recepción total o parcial (crea lotes y sube el stock) → PDF |
| Ventas | Cabecera + detalle, descuento de stock FEFO, número secuencial, anulación con devolución de stock, comprobante PDF |
| Dashboard | KPIs filtrados por permisos y Top 5 de productos más vendidos |
| Configuración | Usuarios con varios roles y permisos directos, matriz de permisos por rol, panel de módulos |

## Levantar el proyecto desde cero (Docker + Laravel Sail)

Requisitos: Docker Desktop (con WSL2 en Windows), Git.

```bash
git clone https://github.com/Dami0921/proyecto_erp_sge_saray_damian.git
cd proyecto_erp_sge_saray_damian
git checkout avance-2          # opcional: la versión entregada

cp .env.example .env           # en Windows: copy .env.example .env

# Instalar dependencias PHP sin tener PHP instalado (la carpeta vendor/ no está en el repo)
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php83-composer:latest composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Abrir <http://localhost>. Para repetir la carga de datos desde cero: `sail artisan migrate:fresh --seed`.

Pruebas automáticas (64 pruebas de las reglas de negocio y seguridad): `sail artisan test`.

## Usuarios de prueba

Contraseña de **todos**: `password`

| Correo | Rol(es) | Para probar |
|---|---|---|
| admin@test.com | admin | Todo, incluida la Configuración (usuarios, matriz de permisos, módulos) |
| vendedor@test.com | vendedor | Clientes y ventas; recibe 403 en compras y configuración |
| almacenista@test.com | almacenista | Productos, lotes, proveedores y compras con recepción; 403 en ventas |
| multirol@test.com | vendedor + almacenista | Al iniciar sesión **elige el rol activo**; sus permisos cambian según el rol elegido |
| supervisor@test.com | vendedor + permiso directo `eliminar-ventas` | Puede anular ventas aunque el rol vendedor no lo permita |

Las cuentas creadas desde «Registrarse» **no tienen rol** (mínimo privilegio): ven un aviso hasta que un administrador les asigne uno en Configuración.

## Datos de ejemplo (seeders)

`php artisan db:seed` carga: 6 módulos, 3 roles con 33 permisos, 5 usuarios, 10 categorías, 10 proveedores, 10 clientes, 10 productos con precio e IVA, 13 lotes, 3 órdenes de compra (recibida, parcial y pendiente) y 8 ventas (una anulada).

## Reglas de negocio principales

- Crear una orden de compra **no** mueve el inventario; el stock sube solo al recibir.
- No se recibe más de lo pedido, ni mercancía vencida, ni una recepción en cero.
- La venta descuenta primero el lote que vence antes (FEFO) y no vende lotes vencidos.
- Sin stock suficiente la venta se rechaza y no se guarda nada (`DB::transaction`).
- Anular una venta devuelve el stock a los mismos lotes; solo se anula una compra sin mercancía recibida.
- Números secuenciales `CV-00001` y `OC-00001`.
- Protecciones de seguridad: no se elimina al único administrador, nadie se quita a sí mismo el rol admin, todo usuario conserva al menos un rol, los roles base no se eliminan.

Detalle completo, matriz de seguridad y evidencias: `docs/avance2/`. Modelo de datos: `docs/diagrama_mer_v2.png` y `docs/diccionario.md`.

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
