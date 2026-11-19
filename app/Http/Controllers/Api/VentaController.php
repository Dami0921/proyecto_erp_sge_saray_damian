<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Resources\VentaResource;
use App\Models\Venta;
use App\Services\VentaService;

class VentaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-ventas')->only('show');
        $this->middleware('permission:crear-ventas')->only('store');
    }

    // GET /api/v1/ventas/{id}
    public function show(Venta $venta)
    {
        return new VentaResource($venta->load(['cliente', 'usuario', 'detalles.producto', 'detalles.lote']));
    }

    /**
     * POST /api/v1/ventas
     * Reutiliza EXACTAMENTE la misma validación (StoreVentaRequest) y la misma lógica de negocio
     * (VentaService) que la pantalla web: stock FEFO, IVA por producto, kardex y número de factura.
     */
    public function store(StoreVentaRequest $request, VentaService $service)
    {
        $venta = $service->registrar($request->validated(), $request->user()->id);

        return (new VentaResource($venta->load(['cliente', 'usuario', 'detalles.producto', 'detalles.lote'])))
            ->response()
            ->setStatusCode(201);
    }
}
