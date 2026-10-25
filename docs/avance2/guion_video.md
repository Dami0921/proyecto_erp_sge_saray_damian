# Guion del video de sustentación (máx. 10:00)

**Antes de grabar** (5 min de preparación):

1. `sail down -v && sail up -d && sail artisan migrate:fresh --seed` → deja la base con los datos de ejemplo.
2. Abrir un cliente MySQL (`sail mysql` o la extensión de VS Code) en una ventana aparte, con esta consulta lista para repetir:
   `select p.nombre, l.numero_lote, l.cantidad_actual from productos p join lotes l on l.producto_id=p.id where p.nombre='Aspersor giratorio';`
3. Tener abiertas 2 ventanas del navegador: una normal y una de incógnito (para dos usuarios a la vez).
4. Zoom del navegador al 110–125 % y del editor al 150 %. Resolución mínima 720p. Cerrar notificaciones.
5. Ensayar una vez con cronómetro. **Todos los integrantes deben hablar**: marcamos quién dice cada bloque.

Usuarios (contraseña de todos: `password`): `admin@test.com`, `vendedor@test.com`, `almacenista@test.com`, `multirol@test.com` (vendedor + almacenista), `supervisor@test.com` (vendedor + permiso directo `eliminar-ventas`).

| Tiempo | Quién | Qué se dice | Qué se muestra |
|---|---|---|---|
| 0:00 – 1:00 | Int. 1 | Nos presentamos; la empresa es AgroInsumos El Cultivador, un almacén de insumos agrícolas con productos que vencen; el ERP controla compras, inventario por lote y ventas. | Landing / login y el Dashboard del admin. |
| 1:00 – 2:30 | Int. 2 | Modelo de datos: tablas nuevas (ventas, compras y sus detalles). 3 reglas clave: **(1)** la orden de compra no mueve stock, solo la recepción; **(2)** la venta descuenta primero el lote que vence antes (FEFO); **(3)** si no hay stock, no se guarda nada (transacción). | `docs/avance2/figura_er_actualizado.png` y, en el editor, `CompraService` / `VentaService` solo de pasada. |
| 2:30 – 6:30 | Int. 1 y 2 (alternan) | **Flujo completo en vivo** (admin): crear proveedor → orden de compra de «Aspersor giratorio» (50 u) → recepción **parcial** de 20 (la orden queda «parcial») → mostrar MySQL: stock 94 → 114 → venta de 15 (baja) → intentar vender más de lo que hay (error de stock) → anular la venta (el stock vuelve) → abrir el PDF del comprobante y el de la orden. | Navegador + ventana de MySQL al lado; repetir la consulta tras cada paso. Mostrar también el intento de recibir de más (error). |
| 6:30 – 8:30 | Int. 2 | Seguridad: entrar como `vendedor@test.com` (solo ve clientes y ventas; escribir `/compras` en la URL → **403**). Cerrar sesión, entrar como `multirol@test.com` y **elegir el rol activo**; cambiar de rol y ver que el menú cambia. Mostrar `supervisor@test.com` con el permiso directo (ve «Anular»). Como admin: Configuración → Usuarios → editar un usuario → **Matriz de permisos**. Intentar eliminar al único admin (regla de protección). | Ventana normal + incógnito. |
| 8:30 – 9:30 | Int. 1 | Código: abrir `app/Services/VentaService.php`, mostrar `DB::transaction`, el `lockForUpdate` y la validación de stock; explicar la regla «sin stock suficiente → ValidationException → no se guarda nada». Mostrar la prueba que la verifica (`VentasTest`) y correr `sail artisan test` (64 pruebas). | Editor con zoom + terminal. |
| 9:30 – 10:00 | Int. 2 | Cierre: qué falta para el corte final — auditoría de movimientos, API con Sanctum, alertas de vencimiento automáticas, más pruebas y el informe APA. | Diapositiva o el README. |

**Consejos:** si algo falla, se muestra y se explica qué se intentó (se valora el análisis). No leer: hablar sobre lo que se ve. Verificar el enlace en ventana de incógnito antes de entregar. Lo que pase del minuto 10 no se evalúa.
