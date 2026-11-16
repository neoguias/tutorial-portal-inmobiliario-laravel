# Portal inmobiliario con Laravel

Un portal inmobiliario de ejemplo al estilo de Idealista, con los anuncios de varias agencias de Valencia, un buscador con filtros, búsqueda por texto, un mapa que agrupa los anuncios y se puede usar para buscar, y una ficha por anuncio con su posición y los inmuebles cercanos.

Está hecho con Laravel 13 y Livewire 4. Los anuncios viven en [Meilisearch](https://www.meilisearch.com), al que se accede con [Larameili](https://github.com/edulazaro/larameili), y el mapa es [MapLibre](https://maplibre.org) con las teselas gratuitas de [OpenFreeMap](https://openfreemap.org).

El tutorial completo, paso a paso, está en Neoguias: [Cómo crear un portal inmobiliario como Idealista con Laravel](https://www.neoguias.com/crear-portal-inmobiliario-laravel/).

## Cómo arrancarlo

```bash
composer install
cp .env.example .env && php artisan key:generate
```

Genera una clave para Meilisearch con `openssl rand -hex 16` y ponla en `MEILISEARCH_KEY`. Después arranca Meilisearch, carga los datos y arranca la aplicación:

```bash
docker compose up -d
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Abre `http://localhost:8000`. El seeder crea cuatro agencias inventadas y 240 anuncios repartidos por diez barrios de Valencia. Los diez primeros son los inmuebles del [CRM inmobiliario con Laravel](https://github.com/neoguias/tutorial-crm-inmobiliario-laravel). Las fotos de ejemplo están generadas con IA y son de uso libre.

Los tests usan el Meilisearch de Docker, cada uno con su propio índice, y se saltan si no está en marcha:

```bash
php artisan test
```

Requiere PHP 8.3, Node 20 o superior y Docker.

## Dónde está cada cosa

- [`app/Meili/Listing.php`](app/Meili/Listing.php): el modelo de los anuncios, con los campos por los que se busca, filtra y ordena en Meilisearch.
- [`app/Models/Agency.php`](app/Models/Agency.php): las agencias, que viven en la base de datos.
- [`database/seeders/ListingSeeder.php`](database/seeders/ListingSeeder.php): los anuncios de ejemplo.
- [`resources/views/pages`](resources/views/pages): el buscador y la ficha de cada anuncio.
- [`resources/js/app.js`](resources/js/app.js): los dos mapas de MapLibre, como componentes de Alpine.
- [`config/portal.php`](config/portal.php): el nombre del portal y sus barrios.
- [`tests/Feature/PortalTest.php`](tests/Feature/PortalTest.php): los filtros, la búsqueda por texto, el mapa y la ficha.

## Autor

Creado por [Edu Lázaro](https://edulazaro.com) para [Neoguias](https://www.neoguias.com).

## Licencia

Este ejemplo es software libre con [licencia MIT](LICENSE).
