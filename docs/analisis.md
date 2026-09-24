# Análisis de la Empresa: AgroInsumos El Cultivador S.A.S.

## 1. Datos Generales

- **Nombre:** AgroInsumos El Cultivador S.A.S.
- **Giro del negocio:** Almacén de venta de insumos agrícolas: semillas, fertilizantes, agroquímicos, herramientas y equipos de riego.
- **Tamaño:** Pequeña/mediana empresa.
- **Clientes:** Agricultores independientes, fincas y cooperativas agrícolas.

## 2. Procesos Clave

- **Ventas:** Cotización → Pedido → Factura → Entrega.
- **Compras:** Solicitud → Orden de Compra → Recepción (genera un lote nuevo) → Pago.
- **Inventario:** Entradas, Salidas, Ajustes — controlados por lote, no solo por producto.
- **Otros:** Manejo de proveedores, categorías de productos y clientes (personas naturales, fincas o cooperativas).

## 3. Entidades Identificadas (Tablas)

- **Usuarios** (vendedores, administradores).
- **Clientes** (agricultores, fincas, cooperativas).
- **Proveedores** (distribuidores/fabricantes de insumos).
- **Categorías** (semillas, fertilizantes, agroquímicos, herramientas, riego).
- **Productos** (catálogo general del insumo, sin precio ni stock fijo).
- **Lotes** — cada remesa recibida de un producto, con su propio vencimiento y costo.
- **Precios por Temporada** — precio de un producto según la época del año.
- **Alertas de Vencimiento** — histórico de avisos generados por lotes próximos a vencer.
- **Inventario / Movimientos de Inventario** (entradas, salidas, ajustes, ligados a un lote).
- **Ventas** (Cabecera y Detalle).
- **Compras** (Cabecera y Detalle).

> Nota de alcance: en este primer corte se implementaron las entidades núcleo (Categorías, Proveedores, Clientes, Productos y Lotes). El resto del modelo (Ventas, Compras, Precios por Temporada, Alertas e Inventario) forma parte del diseño completo del ERP y se desarrollará en los siguientes cortes.

## 4. Preguntas Clave

**¿Qué información se guarda de un cliente?**
Nombre o razón social, tipo de cliente (persona natural, finca, cooperativa), documento (cédula o NIT), teléfono y municipio.

**¿Qué información se guarda de un producto?**
Nombre, categoría, unidad de medida (kg, litro, bulto, unidad) y proveedor asociado. El precio y el stock no se almacenan directamente en el producto: el precio depende de la temporada vigente y el stock se calcula sumando la cantidad actual de sus lotes.

**¿Cómo se relaciona una venta con el inventario?**
Cada venta genera una cabecera (`ventas`) con uno o más registros en `detalle_ventas`; cada detalle descuenta cantidad de un **lote** específico (no del producto en general), priorizando el lote más próximo a vencer (método FEFO: *First Expired, First Out*). Esto genera un movimiento de salida en `inventario_movimientos`. De forma simétrica, al **recibir** una compra se crea un lote nuevo y un movimiento de entrada.

## 5. Funcionalidades Adicionales del Diseño

### a) Manejo de lotes
Cada recepción de compra crea un registro en `lotes` con su propio número de lote, fecha de ingreso, fecha de vencimiento, cantidad y costo unitario. Esto permite:
- Saber exactamente qué remesa se está vendiendo o venció.
- Aplicar la lógica FEFO al despachar ventas (se vende primero lo que vence primero).
- Trazabilidad: ante un reclamo de un cliente, saber de qué lote salió el producto.

### b) Control de vencimientos con alertas
Un proceso programado (ej. `php artisan schedule`) revisa diariamente los lotes activos y, cuando faltan `N` días para su vencimiento (parametrizable, p. ej. 30 días) o ya vencieron, genera un registro en `alertas_vencimiento`. Esto evita:
- Vender producto vencido (agroquímicos y semillas pierden efectividad o son ilegales de vender vencidos).
- Pérdidas por vencimiento no detectado a tiempo, permitiendo ofrecer descuentos antes de que el lote venza.

### c) Precios por temporada
Un mismo producto puede tener distintos precios según la época del año (ej. fertilizantes más costosos en temporada de siembra, semillas con descuento fuera de temporada). Se define en `precios_temporada` por rango de fechas o nombre de temporada, y el sistema toma el precio vigente al momento de la venta.

## 6. Diagrama Entidad-Relación (MER)

Ver `diagrama_mer.png` en esta misma carpeta.
