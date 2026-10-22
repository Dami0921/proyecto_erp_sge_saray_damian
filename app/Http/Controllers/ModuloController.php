<?php

namespace App\Http\Controllers;

use App\Models\Modulo;

class ModuloController extends Controller
{
    // El permiso se exige en la ruta (middleware permission:gestionar-modulos)
    public function index()
    {
        return view('modulos.index', [
            'modulos' => Modulo::orderByDesc('es_nucleo')->orderBy('nombre')->get(),
        ]);
    }

    public function toggle(Modulo $modulo)
    {
        // Protección del núcleo: aunque alguien envíe la petición a mano, no se apaga
        abort_if($modulo->es_nucleo, 403, 'El núcleo no se puede desactivar.');

        // update() dispara el evento del modelo, que limpia la caché de módulos
        $modulo->update(['activo' => ! $modulo->activo]);

        $estado = $modulo->activo ? 'activado' : 'desactivado';

        return redirect()->route('modulos.index')
            ->with('success', "Módulo «{$modulo->nombre}» {$estado}.");
    }
}
