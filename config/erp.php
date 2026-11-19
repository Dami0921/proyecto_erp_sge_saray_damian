<?php

/*
| Parámetros de negocio del ERP (Clase 14). Se leen con config('erp.clave').
| Tenerlos aquí evita «números mágicos» repartidos por el código.
*/
return [
    // Por debajo (o igual) de esta cantidad se considera stock bajo y se alerta (igual que el panel de inicio)
    'umbral_stock_bajo' => (int) env('ERP_STOCK_BAJO', 10),

    // Un lote con existencias que vence dentro de estos días se avisa a bodega
    'dias_alerta_vencimiento' => (int) env('ERP_DIAS_ALERTA_VENCIMIENTO', 30),

    // Hora (24 h, hora de Bogotá) a la que se revisan los lotes por vencer
    'revision_vencimientos_a_las' => env('ERP_REVISION_VENCIMIENTOS', '07:00'),
];
