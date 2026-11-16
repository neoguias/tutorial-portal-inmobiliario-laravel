<?php

// El portal de ejemplo del tutorial. Es inventado, igual que sus agencias y sus anuncios.
return [

    'name' => 'Llar',

    // El centro del mapa al abrir el buscador: la ciudad de Valencia.
    'center' => ['lat' => 39.4745, 'lng' => -0.3725],

    // Los barrios del portal con un punto central, que el seeder usa para situar los anuncios,
    // y el precio orientativo del metro cuadrado en venta y en alquiler.
    'zones' => [
        'Algirós' => ['lat' => 39.4745, 'lng' => -0.3540, 'sale' => 2900, 'rent' => 12.5],
        'Benicalap' => ['lat' => 39.4955, 'lng' => -0.3925, 'sale' => 2300, 'rent' => 11],
        'Benimaclet' => ['lat' => 39.4870, 'lng' => -0.3590, 'sale' => 2800, 'rent' => 12.5],
        'Cabanyal' => ['lat' => 39.4690, 'lng' => -0.3330, 'sale' => 3100, 'rent' => 14],
        'Campanar' => ['lat' => 39.4810, 'lng' => -0.3970, 'sale' => 3200, 'rent' => 13],
        'El Carmen' => ['lat' => 39.4785, 'lng' => -0.3805, 'sale' => 4300, 'rent' => 16.5],
        'Extramurs' => ['lat' => 39.4690, 'lng' => -0.3880, 'sale' => 3400, 'rent' => 14],
        'Malvarrosa' => ['lat' => 39.4785, 'lng' => -0.3335, 'sale' => 3300, 'rent' => 14],
        'Patraix' => ['lat' => 39.4600, 'lng' => -0.3960, 'sale' => 2500, 'rent' => 11.5],
        'Ruzafa' => ['lat' => 39.4615, 'lng' => -0.3740, 'sale' => 4200, 'rent' => 16],
    ],

];
