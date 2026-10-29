<?php
declare(strict_types=1);

$adminTitle = 'Weather API / Destination Weather';
$adminPage = 'weather_api';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
ensure_destination_weather_schema();

$settings = destination_weather_settings();
$destinationRows = destination_weather_admin_rows();
$selectedDestinationId = (int) get_input('destination_id', 0);
if ($selectedDestinationId <= 0 && $destinationRows) {
    $selectedDestinationId = (int) $destinationRows[0]['id'];
}

$selectedDestination = null;
foreach ($destinationRows as $row) {
    if ((int) $row['id'] === $selectedDestinationId) {
        $selectedDestination = $row;
        break;
    }
}
$monthlyNotes = $selectedDestinationId > 0 ? destination_weather_monthly_notes($selectedDestinationId) : [];
$apiKeySet = trim((string) ($settings['weather_api_key'] ?? '')) !== '';

require __DIR__ . '/_top.php';
?>

<div class="row g-3">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Google Weather API Settings</h2>
                <p class="text-secondary small mb-3">Live weather uses Google Weather API server-side only. Monthly weather is admin-managed travel guidance, not a live month-ahead forecast.</p>

                <form method="post" action="<?= e(base_url('admin/weather_api_save.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="settings">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Google Weather API Key</label>
                            <input type="password" name="weather_api_key" class="form-control" placeholder="<?= $apiKeySet ? 'Leave blank to keep current key' : 'Paste API key' ?>">
                            <div class="form-text"><?= $apiKeySet ? 'An API key is saved. Enter a new key only to replace it.' : 'No API key is saved yet.' ?></div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Units</label>
                            <select name="weather_units_system" class="form-select">
                                <option value="METRIC" <?= ($settings['weather_units_system'] ?? 'METRIC') === 'METRIC' ? 'selected' : '' ?>>Metric</option>
                                <option value="IMPERIAL" <?= ($settings['weather_units_system'] ?? '') === 'IMPERIAL' ? 'selected' : '' ?>>Imperial</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Language</label>
                            <input type="text" name="weather_language_code" class="form-control" value="<?= e((string) ($settings['weather_language_code'] ?? 'en')) ?>" placeholder="en">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Current Cache</label>
                            <input type="number" min="10" max="1440" name="weather_current_cache_minutes" class="form-control" value="<?= e((string) ($settings['weather_current_cache_minutes'] ?? '60')) ?>">
                            <div class="form-text">Minutes</div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Forecast Cache</label>
                            <input type="number" min="60" max="2880" name="weather_forecast_cache_minutes" class="form-control" value="<?= e((string) ($settings['weather_forecast_cache_minutes'] ?? '360')) ?>">
                            <div class="form-text">Minutes</div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Timeout</label>
                            <input type="number" min="3" max="20" name="weather_timeout_seconds" class="form-control" value="<?= e((string) ($settings['weather_timeout_seconds'] ?? '8')) ?>">
                            <div class="form-text">Seconds</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="weather_api_enabled" value="1" id="weather_api_enabled" <?= ($settings['weather_api_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <label for="weather_api_enabled" class="form-check-label">Enable live destination weather</label>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-success">Save Weather Settings</button>
                            <button type="submit" name="test_api" value="1" class="btn btn-outline-dark">Save &amp; Test First Configured Destination</button>
                        </div>
                    </div>

                    <div class="alert alert-light border small mt-3 mb-0">
                        Google Cloud recommendation: restrict the key to the Weather API and to the VPS server IP when possible.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Destination Weather Locations</h2>
                <p class="text-secondary small mb-3">Enter accurate coordinates for each destination. Weather will show live data only for enabled destinations with latitude and longitude.</p>

                <form method="post" action="<?= e(base_url('admin/weather_api_save.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="destinations">

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>Destination</th>
                                <th>Enabled</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Display Label</th>
                                <th>Admin Note</th>
                                <th>Last Fetch</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($destinationRows as $row): ?>
                                <?php $rowId = (int) $row['id']; ?>
                                <tr>
                                    <td>
                                        <strong><?= e((string) $row['name']) ?></strong><br>
                                        <code><?= e((string) $row['slug']) ?></code>
                                    </td>
                                    <td>
                                        <input type="checkbox" class="form-check-input" name="destinations[<?= $rowId ?>][weather_enabled]" value="1" <?= (int) ($row['weather_enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    </td>
                                    <td style="min-width:130px;">
                                        <input type="text" name="destinations[<?= $rowId ?>][weather_latitude]" class="form-control form-control-sm" value="<?= e((string) ($row['weather_latitude'] ?? '')) ?>" placeholder="7.8731">
                                    </td>
                                    <td style="min-width:130px;">
                                        <input type="text" name="destinations[<?= $rowId ?>][weather_longitude]" class="form-control form-control-sm" value="<?= e((string) ($row['weather_longitude'] ?? '')) ?>" placeholder="80.7718">
                                    </td>
                                    <td style="min-width:180px;">
                                        <input type="text" name="destinations[<?= $rowId ?>][weather_location_label]" class="form-control form-control-sm" value="<?= e((string) ($row['weather_location_label'] ?? '')) ?>" placeholder="<?= e((string) $row['name']) ?>">
                                    </td>
                                    <td style="min-width:220px;">
                                        <input type="text" name="destinations[<?= $rowId ?>][weather_admin_note]" class="form-control form-control-sm" value="<?= e((string) ($row['weather_admin_note'] ?? '')) ?>" placeholder="Internal note">
                                    </td>
                                    <td class="small">
                                        <?= e((string) ($row['last_weather_fetch'] ?? 'Never')) ?>
                                        <?php if (!empty($row['last_weather_error'])): ?>
                                            <div class="text-danger"><?= e((string) $row['last_weather_error']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-success">Save Destination Weather Locations</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-2">Monthly Destination Weather Guidance</h2>
                        <p class="text-secondary small mb-0">This appears when visitors select a month. It is typical travel-climate guidance, not a live forecast.</p>
                    </div>
                    <form method="get" class="d-flex gap-2">
                        <select name="destination_id" class="form-select">
                            <?php foreach ($destinationRows as $row): ?>
                                <option value="<?= (int) $row['id'] ?>" <?= (int) $row['id'] === $selectedDestinationId ? 'selected' : '' ?>><?= e((string) $row['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-dark">Load</button>
                    </form>
                </div>

                <?php if ($selectedDestination): ?>
                    <form method="post" action="<?= e(base_url('admin/weather_api_save.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="monthly_notes">
                        <input type="hidden" name="destination_id" value="<?= (int) $selectedDestinationId ?>">

                        <div class="accordion" id="monthlyWeatherAccordion">
                            <?php foreach ($monthlyNotes as $monthKey => $note): ?>
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="heading-weather-<?= e($monthKey) ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-weather-<?= e($monthKey) ?>" aria-expanded="false" aria-controls="collapse-weather-<?= e($monthKey) ?>">
                                            <?= e((string) $note['label']) ?><?= !empty($note['has_custom']) ? ' - custom' : ' - using global fallback' ?>
                                        </button>
                                    </h3>
                                    <div id="collapse-weather-<?= e($monthKey) ?>" class="accordion-collapse collapse" aria-labelledby="heading-weather-<?= e($monthKey) ?>" data-bs-parent="#monthlyWeatherAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-12">
                                                    <label class="form-label">Climate Summary</label>
                                                    <textarea name="months[<?= e($monthKey) ?>][climate_summary]" rows="3" class="form-control"><?= e(!empty($note['has_custom']) ? (string) $note['summary'] : '') ?></textarea>
                                                    <div class="form-text">Leave blank to use the global Sri Lanka monthly guide fallback.</div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Temperature Note</label>
                                                    <input type="text" name="months[<?= e($monthKey) ?>][temperature_note]" class="form-control" value="<?= e((string) $note['temperature_note']) ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Rain Note</label>
                                                    <input type="text" name="months[<?= e($monthKey) ?>][rain_note]" class="form-control" value="<?= e((string) $note['rain_note']) ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Sea / Outdoor Note</label>
                                                    <input type="text" name="months[<?= e($monthKey) ?>][sea_note]" class="form-control" value="<?= e((string) $note['sea_note']) ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Best For</label>
                                                    <input type="text" name="months[<?= e($monthKey) ?>][best_for]" class="form-control" value="<?= e(!empty($note['has_custom']) ? (string) $note['best_for'] : '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-success mt-3">Save Monthly Notes for <?= e((string) $selectedDestination['name']) ?></button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-light border mb-0">Create a destination first.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Cache / Refresh</h2>
                <p class="text-secondary small mb-3">Manual refresh fetches current weather and the next 7 days for configured destinations. It requires the API to be enabled and a valid key saved.</p>

                <form method="post" action="<?= e(base_url('admin/weather_api_save.php')) ?>" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="refresh_destination">
                    <div class="col-md-5">
                        <label class="form-label">Destination</label>
                        <select name="destination_id" class="form-select">
                            <?php foreach ($destinationRows as $row): ?>
                                <option value="<?= (int) $row['id'] ?>"><?= e((string) $row['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-dark">Refresh Selected</button>
                    </div>
                </form>

                <form method="post" action="<?= e(base_url('admin/weather_api_save.php')) ?>" class="mt-2" onsubmit="return confirm('Refresh all configured destination weather?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="refresh_all">
                    <button type="submit" class="btn btn-outline-danger">Refresh All Configured Destinations</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
