<?php

use App\Enums\Operation;
use App\Meili\Listing;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * La ficha de un anuncio: su foto, sus datos, la agencia que lo publica, su posición en el
 * mapa y otros anuncios cercanos.
 */
new class extends Component
{
    public int $id;

    public function mount(int $id): void
    {
        abort_unless(Listing::find($id), 404);
    }

    public function render()
    {
        return $this->view()->title($this->listing->title);
    }

    #[Computed]
    public function listing(): Listing
    {
        return Listing::find($this->id);
    }

    /** Hasta cuatro anuncios de la misma operación a menos de un kilómetro, los más cercanos primero. */
    #[Computed]
    public function nearby()
    {
        $geo = $this->listing->_geo;

        return Listing::query()
            ->where('operation', $this->listing->operation->value)
            ->whereNot('id', $this->id)
            ->near($geo['lat'], $geo['lng'], 1000)
            ->orderByDistance($geo['lat'], $geo['lng'])
            ->limit(4)
            ->get();
    }
};
?>

<div class="mx-auto max-w-5xl px-4 py-6">
    <a href="{{ route('search', ['operation' => $this->listing->operation->value]) }}" wire:navigate class="text-sm text-slate-500 hover:text-teal-700">← Volver a la búsqueda</a>

    <div class="mt-4 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <img src="{{ $this->listing->photoUrl() }}" alt="{{ $this->listing->title }}" class="aspect-[3/2] w-full rounded-lg object-cover">

            <h1 class="mt-5 text-2xl font-semibold">{{ $this->listing->title }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $this->listing->zone }}, Valencia · Ref. {{ $this->listing->reference }}</p>

            <p class="mt-4 text-3xl font-bold">{{ $this->listing->priceLabel() }}</p>
            @if ($this->listing->operation === Operation::Sale)
                <p class="text-sm text-slate-500">{{ $this->listing->pricePerMeter() }}</p>
            @endif

            <ul class="mt-4 flex flex-wrap gap-2 text-sm">
                <li class="rounded bg-white px-3 py-1 ring-1 ring-slate-200">{{ $this->listing->type->label() }}</li>
                <li class="rounded bg-white px-3 py-1 ring-1 ring-slate-200">{{ $this->listing->area }} m²</li>
                @if ($this->listing->bedrooms)
                    <li class="rounded bg-white px-3 py-1 ring-1 ring-slate-200">{{ $this->listing->bedrooms }} {{ $this->listing->bedrooms === 1 ? 'dormitorio' : 'dormitorios' }}</li>
                @endif
                <li class="rounded bg-white px-3 py-1 ring-1 ring-slate-200">{{ $this->listing->bathrooms }} {{ $this->listing->bathrooms === 1 ? 'baño' : 'baños' }}</li>
                @if ($this->listing->elevator) <li class="rounded bg-white px-3 py-1 ring-1 ring-slate-200">Ascensor</li> @endif
                @if ($this->listing->terrace) <li class="rounded bg-white px-3 py-1 ring-1 ring-slate-200">Terraza</li> @endif
                @if ($this->listing->parking) <li class="rounded bg-white px-3 py-1 ring-1 ring-slate-200">Garaje</li> @endif
            </ul>

            <p class="mt-5 leading-relaxed text-slate-700">{{ $this->listing->description }}</p>

            <div wire:ignore x-data="listingMap(@js($this->listing->_geo))" class="mt-6 h-64 overflow-hidden rounded-lg"></div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Anunciado por</p>
                <p class="mt-1 font-semibold">{{ $this->listing->agency->name }}</p>
                <p class="mt-3 text-sm">{{ $this->listing->agency->phone }}</p>
                <a href="mailto:{{ $this->listing->agency->email }}?subject={{ rawurlencode('Ref. '.$this->listing->reference) }}"
                    class="mt-4 block rounded bg-teal-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-teal-800">
                    Contactar
                </a>
            </div>

            @if ($this->nearby->isNotEmpty())
                <div>
                    <h2 class="mb-3 font-semibold">Cerca de aquí</h2>
                    <div class="space-y-3">
                        @foreach ($this->nearby as $other)
                            <a href="{{ route('listing', $other->id) }}" wire:navigate wire:key="near-{{ $other->id }}" class="flex gap-3 rounded-lg border border-slate-200 bg-white p-2 hover:border-teal-700">
                                <img src="{{ $other->photoUrl() }}" alt="" class="h-16 w-24 shrink-0 rounded object-cover">
                                <div class="min-w-0 text-sm">
                                    <p class="font-semibold">{{ $other->priceLabel() }}</p>
                                    <p class="truncate text-slate-600">{{ $other->title }}</p>
                                    <p class="text-xs text-slate-500">a {{ number_format($other->_geoDistance, 0, ',', '.') }} m</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>
