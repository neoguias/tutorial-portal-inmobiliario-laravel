<?php

namespace Database\Seeders;

use App\Meili\Listing;
use App\Models\Agency;
use Illuminate\Database\Seeder;

/**
 * Los anuncios de ejemplo del portal. Los diez primeros son la cartera de Atalaya Inmobiliaria,
 * la agencia del tutorial del CRM, y el resto se generan con cifras parecidas a las reales de
 * cada barrio. Usamos siempre la misma semilla, así que cada carga produce los mismos anuncios.
 */
class ListingSeeder extends Seeder
{
    public const TOTAL = 240;

    // Las fotos de ejemplo de public/fotos, según el tipo de inmueble.
    private const PHOTOS = [
        'flat' => ['at-101', 'at-103', 'at-106', 'at-108', 'at-108-cocina', 'at-108-dormitorio', 'p-01', 'p-04', 'p-06', 'p-07', 'p-12'],
        // La foto con vistas al mar solo para los barrios de la playa.
        'beach' => ['p-03'],
        'studio' => ['at-105', 'p-10'],
        'penthouse' => ['at-102', 'p-02'],
        'ground' => ['at-110'],
        'house' => ['at-104', 'p-05', 'p-11'],
        'commercial' => ['at-107', 'p-08'],
        'office' => ['at-109', 'p-09'],
    ];

    public function run(): void
    {
        mt_srand(2026);

        $agencies = Agency::orderBy('id')->pluck('id')->all();
        $listings = $this->atalaya($agencies[0]);

        for ($id = count($listings) + 1; $id <= self::TOTAL; $id++) {
            $listings[] = $this->generate($id, $agencies);
        }

        // Crea el índice con sus ajustes, lo vacía y lo vuelve a llenar.
        Listing::sync();
        Listing::waitForTask(Listing::truncate());
        Listing::import($listings);
    }

    /** Los diez inmuebles del CRM de Atalaya, con sus mismos datos y fotos. */
    private function atalaya(int $agencyId): array
    {
        $crm = [
            ['AT-101', 'Piso luminoso junto al mercado', 'sale', 'flat', 'Ruzafa', 289000, 3, 92],
            ['AT-102', 'Ático con terraza', 'sale', 'flat', 'El Carmen', 415000, 2, 78],
            ['AT-103', 'Piso para reformar', 'sale', 'flat', 'Benimaclet', 168000, 3, 85],
            ['AT-104', 'Casa de pueblo con patio', 'sale', 'house', 'Malvarrosa', 352000, 4, 140],
            ['AT-105', 'Estudio reformado', 'rent', 'flat', 'Ruzafa', 850, 1, 45],
            ['AT-106', 'Piso familiar con garaje', 'rent', 'flat', 'Campanar', 1250, 3, 110],
            ['AT-107', 'Local a pie de calle', 'rent', 'commercial', 'Patraix', 1100, 0, 120],
            ['AT-108', 'Piso exterior de cuatro dormitorios', 'sale', 'flat', 'Campanar', 325000, 4, 124],
            ['AT-109', 'Oficina con vistas', 'rent', 'office', 'El Carmen', 1450, 0, 95],
            ['AT-110', 'Bajo con jardín', 'sale', 'flat', 'Patraix', 239000, 2, 88],
        ];

        return array_map(fn (array $row, int $i) => $this->document(
            id: $i + 1,
            reference: $row[0],
            title: $row[1],
            operation: $row[2],
            type: $row[3],
            zone: $row[4],
            price: $row[5],
            bedrooms: $row[6],
            area: $row[7],
            terrace: in_array($row[0], ['AT-102', 'AT-104', 'AT-110']),
            elevator: in_array($row[0], ['AT-101', 'AT-102', 'AT-106', 'AT-108']),
            parking: $row[0] === 'AT-106',
            photo: strtolower($row[0]),
            agencyId: $agencyId,
        ), $crm, array_keys($crm));
    }

    private function generate(int $id, array $agencies): array
    {
        $zone = array_rand(config('portal.zones'));
        $operation = $this->chance(0.7) ? 'sale' : 'rent';
        $type = $this->pick(['flat' => 75, 'house' => 8, 'commercial' => 9, 'office' => 8]);

        [$title, $kind, $bedrooms, $area, $terrace] = match ($type) {
            'flat' => $this->flat($zone),
            'house' => $this->house($zone),
            'commercial' => [$this->chance(0.5) ? "Local comercial en {$zone}" : "Local a pie de calle en {$zone}", 'commercial', 0, mt_rand(40, 220), false],
            'office' => [$this->chance(0.5) ? "Oficina en {$zone}" : "Oficina luminosa en {$zone}", 'office', 0, mt_rand(50, 260), false],
        };

        $elevator = $type === 'flat' && $kind !== 'ground' && $this->chance(0.7);
        $parking = in_array($type, ['flat', 'house']) && $this->chance($type === 'house' ? 0.5 : 0.25);

        return $this->document(
            id: $id,
            reference: sprintf('%s-%d', ['ATA', 'FAN', 'BRI', 'MAR'][$id % 4], 2000 + $id),
            title: $title,
            operation: $operation,
            type: $type,
            zone: $zone,
            price: $this->price($zone, $operation, $type, $area, $terrace, $elevator),
            bedrooms: $bedrooms,
            area: $area,
            terrace: $terrace,
            elevator: $elevator,
            parking: $parking,
            photo: $this->pickPhoto($kind, $zone),
            agencyId: $agencies[$id % count($agencies)],
        );
    }

    private function flat(string $zone): array
    {
        $bedrooms = $this->pick([1 => 15, 2 => 30, 3 => 35, 4 => 20]);
        $area = 30 + $bedrooms * 22 + mt_rand(-8, 18);

        return match (true) {
            $bedrooms === 1 && $area < 55 => ["Estudio en {$zone}", 'studio', 1, $area, false],
            $this->chance(0.12) => ["Ático con terraza en {$zone}", 'penthouse', $bedrooms, $area, true],
            $this->chance(0.08) => ["Bajo con patio en {$zone}", 'ground', $bedrooms, $area, true],
            default => [
                $this->pickOne(["Piso de {$bedrooms} dormitorios en {$zone}", "Piso reformado en {$zone}", "Piso exterior en {$zone}"]),
                'flat', $bedrooms, $area, $this->chance(0.25),
            ],
        };
    }

    private function house(string $zone): array
    {
        $bedrooms = mt_rand(3, 5);
        $garden = $this->chance(0.5);

        return [$garden ? "Casa con jardín en {$zone}" : "Casa de pueblo en {$zone}", 'house', $bedrooms, 90 + $bedrooms * 20 + mt_rand(0, 40), true];
    }

    private function price(string $zone, string $operation, string $type, int $area, bool $terrace, bool $elevator): int
    {
        $perMeter = config("portal.zones.{$zone}.{$operation}") * mt_rand(85, 120) / 100;
        $perMeter *= in_array($type, ['commercial', 'office']) ? 0.75 : 1;
        $perMeter *= ($terrace ? 1.08 : 1) * ($elevator ? 1.05 : 1);

        // Las ventas se anuncian en miles de euros y los alquileres en múltiplos de 25.
        return $operation === 'sale'
            ? (int) round($area * $perMeter, -3)
            : (int) (round($area * $perMeter / 25) * 25);
    }

    private function document(int $id, string $reference, string $title, string $operation, string $type, string $zone, int $price, int $bedrooms, int $area, bool $terrace, bool $elevator, bool $parking, string $photo, int $agencyId): array
    {
        $center = config("portal.zones.{$zone}");
        $bathrooms = $bedrooms >= 3 ? 2 : 1;

        $extras = array_keys(array_filter(['ascensor' => $elevator, 'terraza' => $terrace, 'plaza de garaje' => $parking]));
        $description = match ($type) {
            'commercial', 'office' => "{$title}, de {$area} m², con baño y buena luz natural.",
            default => "{$title}, de {$area} m², con {$bedrooms} ".($bedrooms === 1 ? 'dormitorio' : 'dormitorios')." y {$bathrooms} ".($bathrooms === 1 ? 'baño' : 'baños').'.',
        };
        if ($extras) {
            $description .= ' Tiene '.preg_replace('/, ([^,]+)$/', ' y $1', implode(', ', $extras)).'.';
        }

        return [
            'id' => $id,
            'reference' => $reference,
            'title' => $title,
            'description' => $description,
            'operation' => $operation,
            'type' => $type,
            'zone' => $zone,
            'price' => $price,
            'bedrooms' => $bedrooms,
            'bathrooms' => $bedrooms === 0 ? 1 : $bathrooms,
            'area' => $area,
            'terrace' => $terrace,
            'elevator' => $elevator,
            'parking' => $parking,
            'photo' => "{$photo}.jpg",
            'agency_id' => $agencyId,
            // Fecha de publicación en segundos, para poder ordenar por los más recientes.
            'published_at' => now()->subMinutes(mt_rand(0, 60 * 24 * 60))->timestamp,
            // Un punto a unos cientos de metros del centro del barrio.
            '_geo' => [
                'lat' => round($center['lat'] + mt_rand(-40, 40) / 10000, 6),
                'lng' => round($center['lng'] + mt_rand(-35, 35) / 10000, 6),
            ],
        ];
    }

    private function chance(float $probability): bool
    {
        return mt_rand(1, 1000) <= $probability * 1000;
    }

    /** Elige una clave según su peso: ['flat' => 75, 'house' => 8] sale piso tres de cada cuatro veces. */
    private function pick(array $weights): int|string
    {
        $roll = mt_rand(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            if (($roll -= $weight) <= 0) {
                return $value;
            }
        }

        return array_key_first($weights);
    }

    private function pickOne(array $values): string
    {
        return $values[mt_rand(0, count($values) - 1)];
    }

    private function pickPhoto(string $kind, string $zone): string
    {
        $photos = self::PHOTOS[$kind];

        if ($kind === 'flat' && in_array($zone, ['Cabanyal', 'Malvarrosa'])) {
            $photos = [...$photos, ...self::PHOTOS['beach']];
        }

        return $this->pickOne($photos);
    }
}
