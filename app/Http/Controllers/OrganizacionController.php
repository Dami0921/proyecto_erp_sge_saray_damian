<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizacionRequest;
use App\Models\Organizacion;
use Illuminate\Support\Arr;

/** Identidad corporativa: lo que hace que el ERP «sea» de cada empresa. El permiso se exige en la ruta. */
class OrganizacionController extends Controller
{
    public function edit()
    {
        return view('organizacion.edit', [
            'org' => Organizacion::firstOrNew([], Organizacion::porDefecto()),
            'paletas' => Organizacion::PALETAS,
        ]);
    }

    public function update(UpdateOrganizacionRequest $request)
    {
        $org = Organizacion::firstOrNew([], Organizacion::porDefecto());
        $cambioLogo = null;

        if ($request->hasFile('logo')) {
            $archivo = $request->file('logo');
            $org->logo = 'data:'.$archivo->getMimeType().';base64,'.base64_encode($archivo->get());
            $cambioLogo = 'logo actualizado';
        } elseif ($request->boolean('quitar_logo') && $org->logo) {
            $org->logo = null;
            $cambioLogo = 'logo eliminado';
        }

        $org->fill(Arr::except($request->validated(), ['logo', 'quitar_logo']))->save();

        // El logo no entra en el antes → después (es muy pesado): su cambio queda anotado aparte
        if ($cambioLogo) {
            activity('organizacion')->performedOn($org)->log($cambioLogo);
        }

        return redirect()->route('organizacion.edit')->with('success', 'La identidad corporativa quedó actualizada.');
    }
}
