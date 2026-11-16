<?php

namespace Tests\Feature;

use App\Meili\Listing;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;
use Throwable;

/**
 * El portal contra un Meilisearch real, arrancado con `docker compose up -d`. Cada test carga
 * los anuncios de ejemplo en un índice con prefijo propio, que se borra al terminar.
 */
class PortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Las páginas usan @vite: sin esto los tests pedirían haber compilado antes los assets.
        $this->withoutVite();

        config(['larameili.prefix' => 'test_'.Str::lower(Str::random(6)).'_']);

        try {
            Listing::client()->health();
        } catch (Throwable) {
            $this->markTestSkipped('Meilisearch no está en marcha: arráncalo con docker compose up -d.');
        }

        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        try {
            Listing::waitForTask(Listing::client()->deleteIndex(Listing::indexName()), 10000);
        } catch (Throwable) {
        }

        parent::tearDown();
    }

    public function test_the_filters_narrow_the_results(): void
    {
        $all = Livewire::test('pages::search')->get('listings')->total();

        $flatsInRuzafa = Livewire::test('pages::search')
            ->set('zone', 'Ruzafa')
            ->set('type', 'flat')
            ->get('listings');

        $this->assertGreaterThan(0, $flatsInRuzafa->total());
        $this->assertLessThan($all, $flatsInRuzafa->total());
        $this->assertTrue($flatsInRuzafa->every(fn (Listing $listing) => $listing->zone === 'Ruzafa' && $listing->type->value === 'flat'));
    }

    public function test_prices_rooms_and_extras_are_respected(): void
    {
        $listings = Livewire::test('pages::search')
            ->set('maxPrice', '300000')
            ->set('bedrooms', 3)
            ->set('extras', ['elevator'])
            ->get('listings');

        $this->assertGreaterThan(0, $listings->total());
        $this->assertTrue($listings->every(fn (Listing $listing) => $listing->price <= 300000 && $listing->bedrooms >= 3 && $listing->elevator));
    }

    public function test_the_text_search_ignores_accents(): void
    {
        $listings = Livewire::test('pages::search')->set('q', 'atico')->get('listings');

        $this->assertGreaterThan(0, $listings->total());
        $this->assertStringContainsString('Ático', $listings->first()->title);
    }

    public function test_the_map_area_only_returns_what_is_inside(): void
    {
        // Un rectángulo alrededor de Ruzafa: [norte, este, sur, oeste].
        $area = [39.4665, -0.3690, 39.4565, -0.3790];

        $component = Livewire::test('pages::search')->call('searchArea', $area);

        $this->assertGreaterThan(0, $component->get('listings')->total());
        foreach ($component->get('listings') as $listing) {
            $this->assertTrue($listing->_geo['lat'] <= $area[0] && $listing->_geo['lat'] >= $area[2]);
            $this->assertTrue($listing->_geo['lng'] <= $area[1] && $listing->_geo['lng'] >= $area[3]);
        }

        $component->assertDispatched('points-updated');
    }

    public function test_the_zone_counts_add_up(): void
    {
        $zones = Livewire::test('pages::search')->get('zones');

        $this->assertSame(Livewire::test('pages::search')->get('listings')->total(), array_sum($zones));
    }

    public function test_the_listing_page_shows_the_agency_and_nearby_listings(): void
    {
        $this->get(route('listing', 1))
            ->assertOk()
            ->assertSee('Piso luminoso junto al mercado')
            ->assertSee('Atalaya Inmobiliaria')
            ->assertSee('Cerca de aquí');

        $this->get(route('listing', 99999))->assertNotFound();
    }
}
