# Diccionario de Datos — AgroInsumos El Cultivador S.A.S.

_Actualizado en el segundo corte (avance-2). Las tablas marcadas **(nueva)** se agregaron o ampliaron después del primer corte. Las tablas de roles y permisos las crea el paquete Spatie Laravel Permission._

### Tablas del primer corte

## Tabla: categorias

Agrupa los productos (semillas, fertilizantes, agroquímicos, herramientas, riego).

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| nombre | VARCHAR(100) | No | Nombre de la categoría |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |
| deleted_at | TIMESTAMP | Sí | Soft Delete: si tiene fecha, el registro está en la papelera |

## Tabla: productos

Catálogo de productos. El stock NO se guarda aquí: es la suma de `lotes.cantidad_actual` vigentes.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| nombre | VARCHAR(150) | No | Nombre del producto |
| categoria_id | BIGINT FK → categorias | No | Categoría del producto |
| proveedor_id | BIGINT FK → proveedores | Sí | Proveedor habitual (opcional desde el segundo corte) |
| unidad_medida | VARCHAR(30) | No | kg, litro, bulto, unidad… |
| precio_venta | DECIMAL(12,2) | No | Precio de venta por unidad (nuevo). Con 0 no se puede vender |
| iva_porcentaje | DECIMAL(5,2) | No | IVA del producto, 19 por defecto; 0 = sin IVA (nuevo) |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |
| deleted_at | TIMESTAMP | Sí | Soft Delete: si tiene fecha, el registro está en la papelera |

## Tabla: lotes

Remesa de un producto con su propia fecha de vencimiento y existencias. Es la unidad real de inventario.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| producto_id | BIGINT FK → productos | No | Producto del lote |
| numero_lote | VARCHAR(50) | No | Identificador del lote |
| fecha_ingreso | DATE | No | Fecha de recepción |
| fecha_vencimiento | DATE | No | Vencimiento. Un lote vencido no se vende ni se recibe |
| cantidad_inicial | INT | No | Cantidad recibida originalmente |
| cantidad_actual | INT | No | Existencias actuales (baja con ventas, sube al anular) |
| costo_unitario | DECIMAL(12,2) | No | Costo de compra por unidad |
| detalle_compra_id | BIGINT FK → detalle_compras | Sí | Línea de compra que originó el lote (trazabilidad) (nuevo) |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |

## Tabla: proveedores **(ampliada)**

Proveedores de mercancía. Se envía a la papelera, nunca se borra si tiene compras.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| nombre | VARCHAR(150) | No | Nombre o razón social |
| nit | VARCHAR(30) | Sí | NIT (único entre los activos) |
| telefono | VARCHAR(20) | Sí | Teléfono |
| direccion | VARCHAR(200) | Sí | Dirección |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |
| deleted_at | TIMESTAMP | Sí | Soft Delete: si tiene fecha, el registro está en la papelera |

## Tabla: clientes **(ampliada)**

Compradores: personas naturales, fincas o cooperativas.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| nombre | VARCHAR(150) | No | Nombre o razón social |
| tipo_cliente | VARCHAR(30) | No | Natural, finca o cooperativa |
| documento | VARCHAR(30) | Sí | Cédula o NIT |
| telefono | VARCHAR(20) | Sí | Teléfono |
| municipio | VARCHAR(100) | Sí | Municipio |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |
| deleted_at | TIMESTAMP | Sí | Soft Delete: si tiene fecha, el registro está en la papelera |

### Tablas nuevas del segundo corte

## Tabla: ventas **(nueva)**

Cabecera de la venta (comprobante CV-xxxxx). Anular = cambiar estado + Soft Delete.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| numero_factura | VARCHAR(20) UNIQUE | No | Número secuencial CV-00001, CV-00002… |
| cliente_id | BIGINT FK → clientes | Sí | Cliente (venta de mostrador si es nulo) |
| user_id | BIGINT FK → users | No | Usuario que registró la venta |
| fecha | DATE | No | Fecha de la venta |
| subtotal | DECIMAL(14,2) | No | Suma de líneas sin IVA |
| iva | DECIMAL(14,2) | No | IVA total |
| total | DECIMAL(14,2) | No | Subtotal + IVA |
| estado | VARCHAR(20) | No | completada o anulada |
| observaciones | TEXT | Sí | Notas |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |
| deleted_at | TIMESTAMP | Sí | Soft Delete: si tiene fecha, el registro está en la papelera |

## Tabla: detalle_ventas **(nueva)**

Líneas de la venta. Una línea por cada lote del que salió mercancía (FEFO).

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| venta_id | BIGINT FK → ventas | No | Venta a la que pertenece |
| lote_id | BIGINT FK → lotes | No | Lote del que se descontó (permite devolver el stock al anular) |
| producto_id | BIGINT FK → productos | No | Producto vendido |
| cantidad | INT UNSIGNED | No | Unidades vendidas |
| precio_unitario | DECIMAL(12,2) | No | Precio al momento de la venta |
| iva_porcentaje | DECIMAL(5,2) | No | IVA aplicado en la línea |
| subtotal | DECIMAL(14,2) | No | cantidad × precio_unitario |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |

## Tabla: compras **(nueva)**

Cabecera de la orden de compra (OC-xxxxx). Crearla NO mueve inventario.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| numero_orden | VARCHAR(20) UNIQUE | No | Número secuencial OC-00001… |
| proveedor_id | BIGINT FK → proveedores | Sí | Proveedor |
| user_id | BIGINT FK → users | No | Usuario que creó la orden |
| fecha | DATE | No | Fecha de la orden |
| subtotal | DECIMAL(14,2) | No | Suma de líneas sin IVA |
| iva | DECIMAL(14,2) | No | IVA total |
| total | DECIMAL(14,2) | No | Subtotal + IVA |
| estado | VARCHAR(20) | No | pendiente, parcial, recibida o anulada |
| observaciones | TEXT | Sí | Notas |
| recibida_at | TIMESTAMP | Sí | Fecha en que se completó la recepción |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |
| deleted_at | TIMESTAMP | Sí | Soft Delete: si tiene fecha, el registro está en la papelera |

## Tabla: detalle_compras **(nueva)**

Líneas de la orden de compra. Un producto una sola vez por orden.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| compra_id | BIGINT FK → compras | No | Orden a la que pertenece |
| producto_id | BIGINT FK → productos | No | Producto pedido |
| cantidad | INT UNSIGNED | No | Cantidad pedida |
| cantidad_recibida | INT UNSIGNED | No | Acumulado recibido (nunca puede superar `cantidad`) |
| costo_unitario | DECIMAL(12,2) | No | Costo pactado |
| iva_porcentaje | DECIMAL(5,2) | No | IVA de la línea |
| subtotal | DECIMAL(14,2) | No | cantidad × costo_unitario |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |

## Tabla: modulos **(nueva)**

Módulos que se pueden encender o apagar sin afectar al resto. El núcleo no se apaga.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| clave | VARCHAR(50) UNIQUE | No | inventario, proveedores, clientes, ventas, compras, nucleo |
| nombre | VARCHAR(100) | No | Nombre visible |
| activo | TINYINT(1) | No | 1 = encendido |
| es_nucleo | TINYINT(1) | No | 1 = no se puede apagar |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |

## Tabla: users **(ampliada)**

Usuarios del sistema. Sus roles y permisos directos viven en las tablas de Spatie.

| Campo | Tipo | Nulo | Descripción |
|---|---|:-:|---|
| id | BIGINT PK | No | Identificador único |
| name | VARCHAR(255) | No | Nombre |
| email | VARCHAR(255) UNIQUE | No | Correo (usuario de acceso) |
| password | VARCHAR(255) | No | Contraseña con hash |
| email_verified_at | TIMESTAMP | Sí | Verificación de correo |
| remember_token | VARCHAR(100) | Sí | Token de «recordarme» |
| created_at / updated_at | TIMESTAMP | Sí | Fechas de registro y modificación |
| deleted_at | TIMESTAMP | Sí | Soft Delete: usuario en la papelera, no puede iniciar sesión (nuevo) |

### Tablas de seguridad (paquete Spatie Laravel Permission)

| Tabla | Descripción |
|---|---|
| roles | admin, vendedor, almacenista (y los que cree el administrador) |
| permissions | 33 permisos con formato `<acción>-<módulo>` (ver-ventas, recibir-compras…) |
| role_has_permissions | Permisos de cada rol (la matriz de seguridad) |
| model_has_roles | Roles de cada usuario (un usuario puede tener varios) |
| model_has_permissions | **Permisos directos** asignados a un usuario, además de los de su rol |

El **rol activo** no se guarda en la base de datos: vive en la sesión (`rol_activo`) y limita los permisos que vienen de roles mientras dure el inicio de sesión.
