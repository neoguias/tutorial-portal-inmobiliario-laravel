import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import * as maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';

// MapLibre dibuja las teselas en un worker. Vite lo compila aparte y nos da su dirección.
maplibregl.setWorkerUrl(workerUrl);

// El mapa base de OpenFreeMap, con datos de OpenStreetMap: gratis, sin registro y sin clave.
const STYLE = 'https://tiles.openfreemap.org/styles/liberty';

/**
 * El mapa del buscador. Agrupa los anuncios cercanos en círculos con su número y, al acercarse,
 * enseña el precio de cada uno. Con la casilla marcada, le pasa a Livewire el trozo de mapa
 * visible cada vez que se mueve.
 */
Alpine.data('searchMap', ({ center, points }) => {
    // El mapa va fuera del estado de Alpine: si Alpine lo observara, envolvería cada objeto
    // de MapLibre y el mapa iría a tirones.
    let map;

    return {
        following: false,

        init() {
            map = new maplibregl.Map({ container: this.$refs.map, style: STYLE, center: [center.lng, center.lat], zoom: 12.5 });
            map.addControl(new maplibregl.NavigationControl({ showCompass: false }));

            map.on('load', () => {
                // cluster agrupa los puntos que quedan a menos de 50 píxeles entre sí.
                map.addSource('listings', { type: 'geojson', data: this.geojson(points), cluster: true, clusterRadius: 50, clusterMaxZoom: 14 });

                map.addLayer({
                    id: 'clusters', type: 'circle', source: 'listings', filter: ['has', 'point_count'],
                    paint: { 'circle-color': '#0f766e', 'circle-radius': ['step', ['get', 'point_count'], 16, 10, 20, 30, 26], 'circle-stroke-width': 3, 'circle-stroke-color': '#ffffff' },
                });
                map.addLayer({
                    id: 'cluster-count', type: 'symbol', source: 'listings', filter: ['has', 'point_count'],
                    layout: { 'text-field': ['get', 'point_count_abbreviated'], 'text-font': ['Noto Sans Bold'], 'text-size': 13 },
                    paint: { 'text-color': '#ffffff' },
                });
                map.addLayer({
                    id: 'prices', type: 'symbol', source: 'listings', filter: ['!', ['has', 'point_count']],
                    layout: { 'text-field': ['get', 'label'], 'text-font': ['Noto Sans Bold'], 'text-size': 12, 'text-allow-overlap': true },
                    paint: { 'text-color': '#0f172a', 'text-halo-color': '#ffffff', 'text-halo-width': 2 },
                });

                // Un clic en un grupo acerca el mapa hasta separarlo, y uno en un precio abre la ficha.
                map.on('click', 'clusters', async (event) => {
                    const cluster = event.features[0];
                    const zoom = await map.getSource('listings').getClusterExpansionZoom(cluster.properties.cluster_id);
                    map.easeTo({ center: cluster.geometry.coordinates, zoom });
                });
                map.on('click', 'prices', (event) => Livewire.navigate(`/inmueble/${event.features[0].properties.id}`));

                for (const layer of ['clusters', 'prices']) {
                    map.on('mouseenter', layer, () => (map.getCanvas().style.cursor = 'pointer'));
                    map.on('mouseleave', layer, () => (map.getCanvas().style.cursor = ''));
                }
            });

            // Livewire avisa con este evento cada vez que cambian los resultados.
            this.$wire.$on('points-updated', ({ points }) => map.getSource('listings')?.setData(this.geojson(points)));

            map.on('moveend', () => {
                if (this.following) {
                    this.$wire.searchArea(this.area());
                }
            });
        },

        toggle() {
            this.following = ! this.following;
            this.$wire.searchArea(this.following ? this.area() : null);
        },

        /** El trozo de mapa visible como [norte, este, sur, oeste]. */
        area() {
            const bounds = map.getBounds();

            return [bounds.getNorth(), bounds.getEast(), bounds.getSouth(), bounds.getWest()];
        },

        /** Los puntos en el formato GeoJSON que entiende MapLibre. */
        geojson(points) {
            return {
                type: 'FeatureCollection',
                features: points.map((point) => ({
                    type: 'Feature',
                    geometry: { type: 'Point', coordinates: [point.lng, point.lat] },
                    properties: { id: point.id, label: point.label },
                })),
            };
        },
    };
});

/** El mapa pequeño de la ficha, con un solo marcador. */
Alpine.data('listingMap', ({ lat, lng }) => ({
    init() {
        const map = new maplibregl.Map({ container: this.$el, style: STYLE, center: [lng, lat], zoom: 15, interactive: false });
        new maplibregl.Marker({ color: '#0f766e' }).setLngLat([lng, lat]).addTo(map);
    },
}));

Livewire.start();
