<?php

// Datos de la empresa que aparecen en los PDFs (no se inventa el NIT: si EMPRESA_NIT está vacío, no se muestra)
return [
    'nombre' => env('EMPRESA_NOMBRE', 'AgroInsumos El Cultivador S.A.S.'),
    'nit' => env('EMPRESA_NIT', ''),
    'direccion' => env('EMPRESA_DIRECCION', ''),
    'telefono' => env('EMPRESA_TELEFONO', ''),
];
