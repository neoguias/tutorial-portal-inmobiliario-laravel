<?php

use App\Enums\Operation;
use App\Enums\PropertyType;
use App\Meili\Listing;
use EduLazaro\Larameili\MeiliQuery;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * El buscador del portal: los filtros, la lista de anuncios y el mapa. Si se marca «Buscar al
 * mover el mapa», solo salen los anuncios del trozo de mapa visible.
 */
new class extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $operation = 'sale';

    #[Url]
    public string $type = '';

    #[Url]
    public string $zone = '';

    #[Url]
    public string $minPrice = '';

    #[Url]
    public string $maxPrice = '';

    #[Url]
    public int $bedrooms = 0;

    #[Url]
    public array $extras = [];

    #[Url]
    public string $sort = 'recent';

    /** El trozo de mapa visible [norte, este, sur, oeste], o null si se busca en toda la ciudad. */
    public ?array $area = null;

    public function render()
    {
        return $this->view()->title(($this->operation === 'rent' ? 'Alquiler' : 'Venta').' de pisos y casas en Valencia');
    }

    /** Cualquier cambio en los filtros vuelve a la primera página y redibuja el mapa. */
    public function updated(): void
    {
        $this->resetPage();
        $this->dispatch('points-updated', points: $this->points());
    }

    public function searchArea(?array $area): void
    {
        $this->area = is_array($area) && count($area) === 4 ? array_map('floatval', array_values($area)) : null;
        $this->updated();
    }

    /** Los filtros elegidos, convertidos en una consulta a Meilisearch. */
    private function search(bool $byZone = true): MeiliQuery
    {
        $query = Listing::query()->where('operation', Operation::tryFrom($this->operation)?->value ?? 'sale');

        // Los valores que acaban en el filtro de Meilisearch se comprueban contra listas cerradas.
        if (PropertyType::tryFrom($this->type)) {
            $query->where('type', $this->type);
        }
        if ($byZone && array_key_exists($this->zone, config('portal.zones'))) {
            $query->where('zone', $this->zone);
        }
        if ((int) $this->minPrice > 0) {
            $query->where('price', '>=', (int) $this->minPrice);
        }
        if ((int) $this->maxPrice > 0) {
            $query->where('price', '<=', (int) $this->maxPrice);
        }
        if ($this->bedrooms > 0) {
            $query->where('bedrooms', '>=', $this->bedrooms);
        }
        foreach (array_intersect($this->extras, ['terrace', 'elevator', 'parking']) as $extra) {
            $query->where($extra, true);
        }
        if ($this->area) {
            [$north, $east, $south, $west] = $this->area;
            $query->withinBox([$north, $east], [$south, $west]);
        }

        return $query;
    }

    #[Computed]
    public function listings()
    {
        $query = $this->search();

        match ($this->sort) {
            'cheap' => $query->orderBy('price', 'asc'),
            'expensive' => $query->orderBy('price', 'desc'),
            'big' => $query->orderBy('area', 'desc'),
            default => $query->orderBy('published_at', 'desc'),
        };

        return $query->with('agency')->paginate(12, query: $this->q);
    }

    /** Cuántos anuncios hay en cada barrio con el resto de filtros, para el desplegable. */
    #[Computed]
    public function zones(): array
    {
        $facets = $this->search(byZone: false)->facets(['zone'])->limit(0)->raw($this->q)->getFacetDistribution();

        return $facets['zone'] ?? [];
    }

    /** Los puntos del mapa: posición y precio de todos los anuncios que encajan. */
    public function points(): array
    {
        $hits = $this->search()->retrieve(['id', 'price', '_geo'])->limit(500)->raw($this->q)->getHits();

        return array_map(fn (array $hit) => [
            'id' => $hit['id'],
            'lat' => $hit['_geo']['lat'],
            'lng' => $hit['_geo']['lng'],
            'label' => number_format($hit['price'], 0, ',', '.').' €',
        ], $hits);
    }
};
?>

<div class="flex flex-col lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
    <section class="px-4 py-6">
        <div class="flex flex-wrap items-center gap-2">
            @foreach (Operation::cases() as $case)
                <button wire:click="$set('operation', '{{ $case->value }}')"
                    @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-teal-700 text-white' => $operation === $case->value, 'bg-white ring-1 ring-slate-300 hover:bg-slate-100' => $operation !== $case->value])>
                    {{ $case === Operation::Sale ? 'Comprar' : 'Alquilar' }}
                </button>
            @endforeach
            <input type="search" wire:model.live.debounce.300ms="q" placeholder="Barrio, tipo o referencia"
                class="ml-auto w-full rounded border border-slate-300 bg-white px-3 py-1.5 text-sm sm:w-64">
        </div>

        <div class="mt-3 flex flex-wrap gap-2 text-sm">
            <select wire:model.live="zone" class="rounded border border-slate-300 bg-white px-2 py-1.5">
                <option value="">Toda Valencia</option>
                @foreach ($this->zones as $name => $count)
                    <option value="{{ $name }}">{{ $name }} ({{ $count }})</option>
                @endforeach
            </select>
            <select wire:model.live="type" class="rounded border border-slate-300 bg-white px-2 py-1.5">
                <option value="">Cualquier tipo</option>
                @foreach (PropertyType::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
            <input type="number" wire:model.live.debounce.500ms="minPrice" placeholder="Precio mín." class="w-28 rounded border border-slate-300 bg-white px-2 py-1.5">
            <input type="number" wire:model.live.debounce.500ms="maxPrice" placeholder="Precio máx." class="w-28 rounded border border-slate-300 bg-white px-2 py-1.5">
            <select wire:model.live="bedrooms" class="rounded border border-slate-300 bg-white px-2 py-1.5">
                <option value="0">Dormitorios</option>
                @foreach ([1, 2, 3, 4] as $n)
                    <option value="{{ $n }}">{{ $n }} o más</option>
                @endforeach
            </select>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
            @foreach (['terrace' => 'Terraza', 'elevator' => 'Ascensor', 'parking' => 'Garaje'] as $value => $text)
                <label class="flex items-center gap-1.5">
                    <input type="checkbox" wire:model.live="extras" value="{{ $value }}" class="accent-teal-700"> {{ $text }}
                </label>
            @endforeach
            <select wire:model.live="sort" class="ml-auto rounded border border-slate-300 bg-white px-2 py-1.5">
                <option value="recent">Más recientes</option>
                <option value="cheap">Más baratos</option>
                <option value="expensive">Más caros</option>
                <option value="big">Más grandes</option>
            </select>
        </div>

        <p class="mt-5 text-sm text-slate-600">
            <strong>{{ number_format($this->listings->total(), 0, ',', '.') }}</strong>
            {{ $this->listings->total() === 1 ? 'inmueble' : 'inmuebles' }} en {{ $operation === 'rent' ? 'alquiler' : 'venta' }}{{ $area ? ' en esta zona del mapa' : ' en Valencia' }}
        </p>

        <div class="mt-3 space-y-3" wire:loading.class="opacity-60">
            @forelse ($this->listings as $listing)
                <article wire:key="listing-{{ $listing->id }}" class="flex overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <img src="{{ $listing->photoUrl() }}" alt="{{ $listing->title }}" class="w-40 shrink-0 object-cover sm:w-56" loading="lazy">
                    <div class="min-w-0 p-4">
                        <p class="text-lg font-semibold">
                            {{ $listing->priceLabel() }}
                            @if ($listing->operation === Operation::Sale)
                                <span class="ml-1 text-xs font-normal text-slate-500">{{ $listing->pricePerMeter() }}</span>
                            @endif
                        </p>
                        <a href="{{ route('listing', $listing->id) }}" wire:navigate class="block truncate font-medium text-teal-700 hover:underline">{{ $listing->title }}</a>
                        <p class="mt-1 text-sm text-slate-600">
                            @if ($listing->bedrooms) {{ $listing->bedrooms }} hab. · @endif {{ $listing->area }} m² · {{ $listing->bathrooms }} {{ $listing->bathrooms === 1 ? 'baño' : 'baños' }}
                            @if ($listing->elevator) · ascensor @endif
                            @if ($listing->terrace) · terraza @endif
                            @if ($listing->parking) · garaje @endif
                        </p>
                        <p class="mt-2 text-xs text-slate-500">{{ $listing->agency->name }} · {{ \Illuminate\Support\Carbon::createFromTimestamp($listing->published_at)->diffForHumans() }}</p>
                    </div>
                </article>
            @empty
                <p class="rounded-lg border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">No hay inmuebles con esos filtros. Prueba a quitar alguno o a mover el mapa.</p>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->listings->links() }}</div>
    </section>

    <aside wire:ignore x-data="searchMap({ center: @js(config('portal.center')), points: @js($this->points()) })"
        class="relative order-first h-80 lg:sticky lg:top-14 lg:order-none lg:h-[calc(100vh-3.5rem)]">
        <div x-ref="map" class="h-full w-full"></div>
        <label class="absolute left-3 top-3 flex items-center gap-2 rounded bg-white px-3 py-1.5 text-sm shadow">
            <input type="checkbox" x-on:change="toggle()" :checked="following" class="accent-teal-700"> Buscar al mover el mapa
        </label>
    </aside>
</div>
