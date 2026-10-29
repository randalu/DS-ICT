# Sri Lanka route map assets

`sri-lanka.json` contains the Sri Lanka feature from Natural Earth 5.1.2 Admin 0 countries at 1:50m. Natural Earth data is public domain: https://www.naturalearthdata.com/about/terms-of-use/

The feature was projected once with D3 7.9.0 `geoMercator().fitExtent([[135,25],[465,710]], feature)`. The SVG path, scale, translation, original geometry and source URL are retained together. The PHP renderer uses the same Mercator formula and fixed viewBox for destination coordinates. No mapping service, browser geolocation, D3 library or network request is needed at runtime.

`locations.json` contains suggested WGS84 points from GeoNames, retrieved 25 October 2026. GeoNames is licensed CC BY 4.0: https://www.geonames.org/about.html. Public configured maps include attribution. These are city/airport reference points, not entrance or hotel coordinates. Suggestions are not database seeds: an administrator must review and explicitly save/verify them. Destination records are linked only by explicit admin selection.

Route arrows are illustrative connections, not road routes, distances or driving-time estimates.

`destination-locations.json` is the explicitly requested destination import catalog, separate from the original manual suggestions. It contains 41 reviewed GeoNames Sri Lanka reference points, individual source IDs/URLs, feature codes and point descriptions. The original country dump URL, retrieval date and SHA256 are retained. The importer creates verified, active locations only for exact recognized destination identities; it preserves all existing linked locations and reports conflicts/unmatched names. Ussangoda and the combined Minneriya / Kaudulla entry require manual review. Geographic-area points are approximate map references, not entrances. No runtime geocoding service or API key is required. Use the CLI dry run and private-backed transactional apply described in `references/destination-map-seed-2026-10-25.md`.
