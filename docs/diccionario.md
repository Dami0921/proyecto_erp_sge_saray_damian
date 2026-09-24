# Diccionario de Datos — AgroInsumos El Cultivador S.A.S.

## Tabla: categorias

| Campo | Tipo | Descripción |
|---|---|---|
| id | BIGINT | Identificador único |
| nombre | VARCHAR(100) | Nombre de la categoría (semillas, fertilizantes, agroquímicos, herramientas, riego) |
| created_at / updated_at | TIMESTAMP | Fechas de registro y modificación |

## Tabla: proveedores

| Campo | Tipo | Descripción |
|---|---|---|
| id | BIGINT | Identificador único |
| nombre | VARCHAR(150) | Nombre del proveedor/distribuidor |
| nit | VARCHAR(30) | NIT del proveedor |
| telefono | VARCHAR(20) | Teléfono de contacto |
| direccion | VARCHAR(200) | Dirección |
| created_at / updated_at | TIMESTAMP | Fechas de registro y modificación |

## Tabla: clientes

| Campo | Tipo | Descripción |
|---|---|---|
| id | BIGINT | Identificador único |
| nombre | VARCHAR(150) | Nombre o razón social |
| tipo_cliente | VARCHAR(30) | Natural, finca o cooperativa |
| documento | VARCHAR(30) | Cédula o NIT |
| telefono | VARCHAR(20) | Teléfono de contacto |
| municipio | VARCHAR(100) | Municipio del cliente |
| created_at / updated_at | TIMESTAMP | Fechas de registro y modificación |

## Tabla: productos

| Campo | Tipo | Descripción |
|---|---|---|
| id | BIGINT | Identificador único |
| nombre | VARCHAR(150) | Nombre del producto |
| categoria_id | BIGINT (FK → categorias) | Categoría a la que pertenece |
| proveedor_id | BIGINT (FK → proveedores) | Proveedor que lo suministra |
| unidad_medida | VARCHAR(30) | kg, litro, bulto, unidad, etc. |
| created_at / updated_at | TIMESTAMP | Fechas de registro y modificación |

## Tabla: lotes

| Campo | Tipo | Descripción |
|---|---|---|
| id | BIGINT | Identificador único |
| producto_id | BIGINT (FK → productos) | Producto al que pertenece el lote |
| numero_lote | VARCHAR(50) | Identificador del lote/remesa |
| fecha_ingreso | DATE | Fecha en que se recibió el lote |
| fecha_vencimiento | DATE | Fecha de vencimiento del lote |
| cantidad_inicial | INT | Cantidad recibida originalmente |
| cantidad_actual | INT | Cantidad disponible actualmente |
| costo_unitario | DECIMAL(12,2) | Costo de compra por unidad |
| created_at / updated_at | TIMESTAMP | Fechas de registro y modificación |

---

### Tablas planeadas para próximos cortes (parte del diseño completo, aún no implementadas)

| Entidad | Campos principales |
|---|---|
| usuarios | id, nombre, email, password, rol (vendedor/administrador) |
| precios_temporada | id, producto_id (FK), temporada, fecha_inicio, fecha_fin, precio_unitario |
| alertas_vencimiento | id, lote_id (FK), fecha_alerta, dias_restantes, estado (pendiente/atendida) |
| inventario_movimientos | id, lote_id (FK), tipo_movimiento (entrada/salida/ajuste), cantidad, fecha, referencia |
| ventas | id, cliente_id (FK), usuario_id (FK), fecha, total, estado |
| detalle_ventas | id, venta_id (FK), lote_id (FK), cantidad, precio_unitario_aplicado, subtotal |
| compras | id, proveedor_id (FK), usuario_id (FK), fecha, total, estado |
| detalle_compras | id, compra_id (FK), producto_id (FK), cantidad, precio_unitario, subtotal |
