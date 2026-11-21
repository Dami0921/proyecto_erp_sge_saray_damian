<?php

// Valores INICIALES de la identidad de la empresa (los toma OrganizacionSeeder). Después se editan desde
// Configuración → Organización. No se inventa el NIT: si EMPRESA_NIT está vacío, no se muestra.
return [
    'nombre' => env('EMPRESA_NOMBRE', 'AgroInsumos El Cultivador S.A.S.'),
    'nit' => env('EMPRESA_NIT', ''),
    'direccion' => env('EMPRESA_DIRECCION', ''),
    'telefono' => env('EMPRESA_TELEFONO', ''),
];
