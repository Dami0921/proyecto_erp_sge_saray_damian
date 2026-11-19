<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoteResource;
use App\Models\Lote;
use Illuminate\Http\Request;

class LoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-lotes');
    }

    // GET /api/v1/lotes/por-vencer?dias=30 → lotes con existencias que vencen dentro de N días (sin vencer aún)
    public function porVencer(Request $request)
    {
        $datos = $request->validate(['dias' => ['nullable', 'integer', 'min:1', 'max:365']]);
        $dias = (int) ($datos['dias'] ?? 30);

        $lotes = Lote::query()
            ->with('producto')
            ->where('cantidad_actual', '>', 0)
            ->whereDate('fecha_vencimiento', '>=', today())
            ->whereDate('fecha_vencimiento', '<=', today()->addDays($dias))
            ->orderBy('fecha_vencimiento')
            ->paginate(min($request->integer('per_page', 15), 100));

        return LoteResource::collection($lotes);
    }
}
