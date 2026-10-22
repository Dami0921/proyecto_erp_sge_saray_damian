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
