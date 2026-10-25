<?php

namespace App\Http\Requests;

// Mismas reglas que al crear; la contraseña pasa a ser opcional (solo se cambia si se escribe una nueva)
class UpdateUsuarioRequest extends StoreUsuarioRequest
{
}
