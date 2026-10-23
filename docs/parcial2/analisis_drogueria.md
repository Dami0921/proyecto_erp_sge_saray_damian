# Análisis de la Droguería Vida Sana

## 1. Flujo integrado

1. **Detección de la necesidad:** el regente de farmacia revisa el stock vendible de cada producto (lotes no vencidos) y lo compara con su stock mínimo (`min_stock`). Si está por debajo, solicita la reposición al administrador.
2. **Orden de compra:** el administrador crea la orden de compra al proveedor (por ejemplo, Distribuidora Farmacéutica del Valle) con el detalle de producto y cantidad. Pasa de "creada" a "enviada". Se guarda quién la creó (`created_by`); esa persona no puede recibirla (segregación de funciones).
3. **Recepción por lote:** cuando llega la mercancía, el auxiliar de bodega la descarga y la ubica, y **el regente la revisa y la registra**, por cada producto: número de lote, fecha de vencimiento, cantidad recibida, fecha y hora de llegada. Se crea el lote y se suma la cantidad al stock total del producto. Se guarda quién recibió (`received_by`).
4. **Entrega incompleta:** si llega menos de lo pedido, la orden queda en "parcialmente recibida", se envía una petición formal al proveedor para que entregue el faltante, y cada entrega posterior se registra por separado hasta completar la orden.
5. **Inventario:** el producto maneja dos cantidades. El **stock total** (`products.stock`) es la suma de todos sus lotes. El **stock vendible** es la suma de los lotes no vencidos. Los lotes vencidos no se venden: se retiran del inventario con una baja registrada.
6. **Venta con FEFO:** el cajero agrega productos a la venta. Todo el proceso ocurre en **una sola transacción**: el sistema descarta los lotes vencidos, verifica que el stock vendible alcance (si no alcanza, rechaza la venta completa y no cambia nada) y descuenta primero del lote que vence antes (FEFO), repartiendo entre varios lotes si hace falta. Si el producto es de control especial, la venta debe llevar la autorización de un regente (`authorized_by`).
7. **Documento:** al confirmar, se genera el **comprobante de venta** (cabecera y detalle), guardando de qué lote salió cada unidad (una fila de detalle por lote consumido).
8. **Indicador:** las ventas y las existencias alimentan los indicadores del dueño (más vendidos, stock mínimo, rendimiento por cajero y próximos a vencer).

## 2. Reglas de negocio

| # | Regla | Módulo | Dónde se implementa |
|---|---|---|---|
| 1 | No se vende un lote vencido | Ventas / inventario | **Servicio** `DispensingService`: filtra lotes con `expiration_date >= hoy` |
| 2 | Un medicamento de control especial requiere autorización de un regente. El cajero registra la venta, pero debe indicar qué regente autoriza | Ventas | **Form Request** (exige `authorized_by` si el producto es de control especial) y **servicio** de venta (verifica que ese usuario tenga el permiso `dispensar-control-especial`). En la vista, `@can` solo muestra el campo de autorización |
| 3 | Quien crea la orden de compra no puede recibirla | Compras / recepción | **Servicio** de recepción: compara `created_by` de la orden con el usuario actual y rechaza si coinciden. Refuerzo: permisos distintos (`crear-compras` y `recibir-lotes`) por **middleware** de ruta |
| 4 | No se puede recibir más de lo pedido | Recepción | **Servicio**: consulta lo ya recibido de la orden y valida el tope dentro de una transacción |
| 5 | Las cantidades deben ser mayores a cero | Compras, ventas, lotes | **Form Request** (`integer\|min:1`), y `min="1"` en la vista como ayuda visual |
| 6 | El número de lote es único por producto | Lotes | **Migración** (índice único `product_id + lot_number`) y **Form Request** (`Rule::unique` filtrado por producto) |
| 7 | Si el stock vendible no alcanza, la venta se rechaza completa y no cambia nada | Ventas | **Servicio** `DispensingService`: valida dentro de `DB::transaction` y lanza `ValidationException` |

Nota: la vista (`@can`, `min`) solo oculta o guía; la seguridad real siempre se valida en el servidor (middleware, Form Request o servicio).

## 3. Estados

### Orden de compra

| Estado | Significado | Efecto en el inventario |
|---|---|---|
| Creada | El administrador la registró | No cambia |
| Enviada | Se envió al proveedor | No cambia |
| Parcialmente recibida | Llegó solo una parte de lo pedido | Sube solo lo efectivamente recibido |
| Recibida | Llegó todo lo pedido | Sube lo efectivamente recibido (queda completa la orden) |
| Cancelada | Se anuló la orden | No cambia (lo ya recibido se conserva) |

### Venta

| Estado | Significado | Efecto en el inventario |
|---|---|---|
| Borrador | El cajero arma el carrito | No cambia |
| Confirmada | La venta se cobró | Baja por FEFO, descontando de los lotes que vencen primero |
| Anulada | Se canceló después de cobrar | Devuelve las unidades a los lotes de origen |

Regla clave: el inventario solo sube al **recibir** mercancía y solo baja al **confirmar** una venta. Nunca cambia al crear la orden ni al armar el borrador.

## 4. Indicadores (KPIs)

| # | KPI | Cómo se mide | Fuente de datos |
|---|---|---|---|
| 1 | Producto más vendido | Unidades vendidas por producto en el mes (top 5), solo ventas confirmadas | `sale_details` unido con `sales` (estado confirmada) |
| 2 | Productos bajo el stock mínimo (alerta) | Cantidad de productos cuyo stock vendible es menor o igual a su `min_stock`, con la lista | Suma de `lots.quantity` no vencidos frente a `products.min_stock` |
| 3 | Cajero con mejor rendimiento | Total vendido en dinero y número de ventas por cajero en el mes; las ventas anuladas no cuentan | `sales.user_id` agrupado, solo confirmadas |
| 4 | Unidades próximas a vencer en 30 días | Suma de `quantity` de lotes con `expiration_date` entre hoy y hoy + 30 días | `lots` |

## 5. Diccionario de datos

Nota: `lots` está implementada (migración ejecutada). `purchase_details` y `sale_details` pertenecen al modelo diseñado en el diagrama ER; el parcial solo exige migrar `lots`.

### Tabla `lots` (implementada)

| Campo | Tipo | Descripción | Restricciones |
|---|---|---|---|
| id | BIGINT UNSIGNED | Identificador del lote | PK, AUTO_INCREMENT |
| product_id | BIGINT UNSIGNED | Producto al que pertenece el lote | FK → `products.id`, NOT NULL, ON DELETE CASCADE |
| lot_number | VARCHAR(255) | Número de lote que trae la caja del laboratorio | NOT NULL; UNIQUE junto con `product_id` |
| expiration_date | DATE | Fecha de vencimiento del lote | NOT NULL |
| quantity | INT UNSIGNED | Unidades que quedan en el lote (baja con cada venta) | NOT NULL, no admite negativos |
| created_at | TIMESTAMP | Fecha de creación | NULL |
| updated_at | TIMESTAMP | Fecha de última modificación | NULL |

### Tabla `purchase_details` (diseño)

| Campo | Tipo | Descripción | Restricciones |
|---|---|---|---|
| id | BIGINT UNSIGNED | Identificador de la línea de compra | PK, AUTO_INCREMENT |
| purchase_id | BIGINT UNSIGNED | Orden de compra (cabecera) a la que pertenece | FK → `purchases.id`, NOT NULL, ON DELETE CASCADE |
| product_id | BIGINT UNSIGNED | Producto pedido | FK → `products.id`, NOT NULL |
| quantity_ordered | INT UNSIGNED | Unidades pedidas al proveedor | NOT NULL, mayor a 0 |
| quantity_received | INT UNSIGNED | Unidades recibidas hasta ahora (acumulado de entregas) | NOT NULL, DEFAULT 0, no puede superar `quantity_ordered` (regla 4, validada en el servicio) |
| unit_cost | DECIMAL(12,2) | Costo por unidad acordado con el proveedor | NOT NULL, mayor o igual a 0 |

### Tabla `sale_details` (diseño)

| Campo | Tipo | Descripción | Restricciones |
|---|---|---|---|
| id | BIGINT UNSIGNED | Identificador de la línea de venta | PK, AUTO_INCREMENT |
| sale_id | BIGINT UNSIGNED | Venta (cabecera) a la que pertenece | FK → `sales.id`, NOT NULL, ON DELETE CASCADE |
| product_id | BIGINT UNSIGNED | Producto vendido | FK → `products.id`, NOT NULL |
| lot_id | BIGINT UNSIGNED | Lote del que salieron las unidades de esta línea | FK → `lots.id`, NOT NULL |
| quantity | INT UNSIGNED | Unidades tomadas de ese lote | NOT NULL, mayor a 0 |
| unit_price | DECIMAL(12,2) | Precio de venta por unidad en el momento de la venta | NOT NULL, mayor o igual a 0 |

## 6. Matriz de seguridad

### Matriz Rol × Módulo × Acción

Roles: **A** = admin, **R** = regente, **C** = cajero, **X** = auxiliar-bodega. `✔` = permitido, `—` = no permitido (el servidor responde 403).

| Módulo | Acción (permiso) | A | R | C | X |
|---|---|:-:|:-:|:-:|:-:|
| Productos | ver-productos | ✔ | ✔ | ✔ | ✔ |
| | crear-productos | ✔ | — | — | — |
| | editar-productos | ✔ | ✔ | — | — |
| | eliminar-productos | ✔ | — | — | — |
| Lotes | ver-lotes | ✔ | ✔ | ✔ | ✔ |
| | crear-lotes | ✔ | ✔ | — | — |
| | editar-lotes | ✔ | ✔ | — | — |
| | dar-baja-lotes (retirar vencidos) | ✔ | ✔ | — | — |
| Proveedores | ver-proveedores | ✔ | ✔ | — | — |
| | crear-proveedores | ✔ | — | — | — |
| | editar-proveedores | ✔ | — | — | — |
| Clientes | ver-clientes | ✔ | — | ✔ | — |
| | crear-clientes | ✔ | — | ✔ | — |
| | editar-clientes | ✔ | — | — | — |
| Compras | ver-compras | ✔ | ✔ | — | ✔ |
| | crear-compras | ✔ | — | — | — |
| | editar-compras | ✔ | — | — | — |
| | eliminar-compras | ✔ | — | — | — |
| Recepción | **recibir-lotes** | — | ✔ | — | — |
| Ventas | ver-ventas | ✔ | ✔ | ✔ | — |
| | crear-ventas | ✔ | — | ✔ | — |
| | anular-ventas | ✔ | — | — | — |
| Control especial | **dispensar-control-especial** | ✔ | ✔ | — | — |
| Reportes | ver-reportes | ✔ | ✔ | — | — |

Los lotes, proveedores y clientes no se eliminan: tienen movimientos asociados y se desactivan o se conservan para no perder trazabilidad.

### Cómo la matriz cumple la segregación de funciones (quien compra no recibe)

1. **Separación por permisos:** solo el admin tiene `crear-compras` y solo el regente tiene `recibir-lotes`. Ningún rol tiene los dos permisos, así que la separación ya se cumple en la matriz. El auxiliar de bodega descarga y ubica la mercancía (puede ver las compras para saber qué llega), pero no registra la recepción. El cajero no tiene acceso a compras ni a recepción.
2. **Separación entre áreas:** el auxiliar de bodega no puede vender y el cajero no puede recibir mercancía, así que quien mueve el dinero no toca el inventario recibido.
3. **Control especial aparte:** `dispensar-control-especial` solo lo tienen el regente y el admin; el cajero vende, pero no puede despachar esos medicamentos por sí solo.
4. **Defensa adicional en el servidor:** aunque ningún rol tenga ambos permisos, un usuario con dos roles sí podría acumularlos (por ejemplo, un admin al que se le asigne también el rol regente). Por eso se mantiene la regla 3 del análisis: el servicio de recepción compara el usuario que creó la orden (`created_by`) con el usuario actual y rechaza la operación si coinciden.

### El regente cubre la caja los sábados: ¿segundo rol o permiso directo?

**Decisión: asignarle un segundo rol (`cajero`).**

- **Por qué un rol:** el cajero necesita un conjunto de permisos (ver y crear ventas, ver y crear clientes, ver productos y lotes). Asignar el rol es una sola acción y queda claro en la auditoría que está actuando como cajero. Con permisos directos habría que dar varios permisos sueltos a una sola persona, difíciles de revisar y fáciles de olvidar quitar.
- **Cuándo usar permiso directo:** solo para una excepción puntual de un único permiso a una persona. No es el caso, porque el cajero implica varios permisos.
- **Comportamiento de Spatie:** con varios roles, los permisos se suman. El regente con el rol `cajero` conserva los suyos y gana los de caja, sin conflicto con la segregación (el cajero no compra ni recibe).
- **Rol que elegiría al iniciar sesión:** los sábados entraría como **cajero** para su turno de caja, y de lunes a viernes como **regente**. Así cada venta queda registrada bajo el rol correcto. Spatie no trae un selector de rol activo: con dos roles asignados, los permisos de ambos valen todo el tiempo. Si se quiere que solo valga el rol elegido, se implementa guardando el rol activo en la sesión y restringiendo los permisos con un middleware o un `Gate::before` propio. La alternativa más simple es asignar el rol `cajero` solo los sábados (`assignRole`) y retirarlo después (`removeRole`).
