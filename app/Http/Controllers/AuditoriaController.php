<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditoriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-auditoria');
    }

    /** Bitácora de auditoría: quién hizo qué, cuándo y qué cambió (antes → después). */
    public function index(Request $request)
    {
        $actividades = Activity::query()
            ->with(['causer', 'subject' => function (MorphTo $morphTo) {
                // Los registros en la papelera (por ejemplo, una venta anulada) también muestran su nombre o número
                $morphTo->constrain([
                    Categoria::class => fn ($q) => $q->withTrashed(),
                    Producto::class => fn ($q) => $q->withTrashed(),
                    Cliente::class => fn ($q) => $q->withTrashed(),
                    Proveedor::class => fn ($q) => $q->withTrashed(),
                    Venta::class => fn ($q) => $q->withTrashed(),
                    Compra::class => fn ($q) => $q->withTrashed(),
                    User::class => fn ($q) => $q->withTrashed(),
                ]);
            }])
            ->when($request->filled('modulo'), fn ($q) => $q->where('log_name', $request->input('modulo')))
            ->when($request->filled('evento'), fn ($q) => $q->where('event', $request->input('evento')))
            ->when($request->filled('usuario'), fn ($q) => $q->where('causer_type', (new User)->getMorphClass())
                ->where('causer_id', $request->integer('usuario')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('hasta')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('auditoria.index', [
            'actividades' => $actividades,
            'modulos' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name'),
            'usuarios' => User::withTrashed()->orderBy('name')->get(['id', 'name']),
            'eventos' => ['created' => 'Creado', 'updated' => 'Actualizado', 'deleted' => 'Eliminado', 'restored' => 'Restaurado'],
        ]);
    }
}
