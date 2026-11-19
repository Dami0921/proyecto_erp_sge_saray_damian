<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Campana de notificaciones: cada usuario ve y marca SOLO las suyas. */
class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        return view('notificaciones.index', [
            'notificaciones' => $request->user()->notifications()->paginate(15),
            'sinLeer' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    // «Abrir»: marca como leída y lleva a la pantalla relacionada
    public function abrir(Request $request, string $id)
    {
        $notificacion = $request->user()->notifications()->findOrFail($id);
        $notificacion->markAsRead();

        return redirect($notificacion->data['url'] ?? route('notificaciones.index'));
    }

    public function leerTodas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->route('notificaciones.index')->with('success', 'Todas las notificaciones quedaron como leídas.');
    }
}
