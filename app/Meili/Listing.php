<?php

namespace App\Meili;

use App\Enums\Operation;
use App\Enums\PropertyType;
use App\Models\Agency;
use EduLazaro\Larameili\Meili;
use EduLazaro\Larameili\Relations\BelongsToEloquent;

/**
 * Un anuncio del portal. Los anuncios viven solo en Meilisearch. Llegan de las agencias, nadie
 * los edita uno a uno y la pregunta que siempre les hacemos es cuáles encajan con unos filtros
 * y con el trozo de mapa que se está viendo.
 */
class Listing extends Meili
{
    protected static string $index = 'listings';

    // Cada anuncio guarda su posición en _geo, para filtrarlo por zona del mapa.
    protected static bool $geo = true;

    protected static array $searchable = ['title', 'zone', 'reference', 'description'];

    // El id también, para poder dejar fuera el propio anuncio en «Cerca de aquí».
    protected static array $filterable = [
        'id', 'operation', 'type', 'zone', 'price', 'bedrooms', 'area',
        'terrace', 'elevator', 'parking', 'agency_id',
    ];

    protected static array $sortable = ['price', 'area', 'published_at'];

    protected static array $casts = [
        'operation' => Operation::class,
        'type' => PropertyType::class,
        'price' => 'int',
        'bedrooms' => 'int',
        'bathrooms' => 'int',
        'area' => 'int',
        'terrace' => 'bool',
        'elevator' => 'bool',
        'parking' => 'bool',
    ];

    public function agency(): BelongsToEloquent
    {
        return $this->belongsToEloquent(Agency::class, foreignKey: 'agency_id');
    }

    /** 289.000 € en venta y 850 €/mes en alquiler. */
    public function priceLabel(): string
    {
        $amount = number_format($this->price, 0, ',', '.').' €';

        return $this->operation === Operation::Rent ? $amount.'/mes' : $amount;
    }

    /** El precio por metro cuadrado, que es como se comparan los pisos en venta. */
    public function pricePerMeter(): string
    {
        return number_format($this->price / max($this->area, 1), 0, ',', '.').' €/m²';
    }

    public function photoUrl(): string
    {
        return asset("fotos/{$this->photo}");
    }
}
