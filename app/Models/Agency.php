<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una agencia que publica anuncios en el portal. Las agencias viven en la base de datos y
 * los anuncios en Meilisearch, cada uno con el id de su agencia.
 */
class Agency extends Model
{
    protected $fillable = ['name', 'phone', 'email'];
}
