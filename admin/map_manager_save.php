<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/route_map_admin.php';
admin_require_auth();
if (!is_post()) { redirect('admin/map_manager.php'); }
guard_post_size();
require_csrf();
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 150000) { http_response_code(413); exit('Map form is too large.'); }
$type = is_string(post_input('type')) ? post_input('type') : '';
$id = is_scalar(post_input('id')) ? max(0, (int) post_input('id')) : 0;
$action = post_input('action', '');
$return = is_string(post_input('return_to')) ? post_input('return_to') : '';
$locationId = is_scalar(post_input('location_id')) ? max(0, (int) post_input('location_id')) : 0;
try { route_map_type($type); } catch (InvalidArgumentException) { http_response_code(422); exit('Invalid package collection.'); }

// Retain only bounded, expected form values, separately from package content old-input.
$retained = [];
foreach (['revision', 'source_revision', 'library_revision', 'status', 'name', 'latitude', 'longitude', 'destination_id', 'coordinate_source', 'is_verified', 'is_active', 'title', 'brand', 'route_color'] as $field) {
    if (is_string($_POST[$field] ?? null) && strlen($_POST[$field]) <= 2000) { $retained[$field] = $_POST[$field]; }
}
if (is_array($_POST['map_days'] ?? null) && count($_POST['map_days']) <= 367) {
    $retained['map_days'] = [];
    foreach ($_POST['map_days'] as $day => $values) {
        if (!preg_match('/\A(?:0|[1-9][0-9]{0,2})\z/', (string) $day) || !is_array($values) || count($values) > 8) { continue; }
        $retained['map_days'][$day] = array_values(array_filter($values, static fn($v) => is_string($v) && strlen($v) <= 30));
    }
}
$redirect = 'admin/map_manager.php?type=' . $type . '&id=' . $id;
try {
    if ($action === 'route') {
        $redirect = route_map_return_path($type, $id, $return);
        $_SESSION['route_map_input'][$type . ':' . $id] = $retained;
        if (post_input('map_form_complete', '') !== '1') { throw new InvalidArgumentException('The map form was incomplete. No changes were saved.'); }
        route_map_save($type, $id, $_POST);
        unset($_SESSION['route_map_input'][$type . ':' . $id]);
        flash_set('success', 'Journey map saved. Package text, photos and publication settings were preserved.');
    } elseif ($action === 'location') {
        $_SESSION['route_map_location_input'] = $retained + ['location_id' => $locationId, 'is_verified' => '0', 'is_active' => '0'];
        $redirect = 'admin/map_manager.php?tab=locations&location_id=' . $locationId . '&type=' . $type . '&id=' . $id;
        $locationId = route_map_location_save($locationId, $_POST);
        unset($_SESSION['route_map_location_input']);
        $redirect = 'admin/map_manager.php?tab=locations&location_id=' . $locationId . '&type=' . $type . '&id=' . $id;
        flash_set('success', 'Map location saved. Maps using an edited location require review before display.');
    } elseif ($action === 'design') {
        $_SESSION['route_map_design_input'] = $retained;
        $redirect = 'admin/map_manager.php?tab=design&type=' . $type . '&id=' . $id;
        route_map_design_save($_POST);
        unset($_SESSION['route_map_design_input']);
        flash_set('success', 'Default map design saved for all package maps.');
    } else { throw new InvalidArgumentException('Choose a valid Map Manager action.'); }
} catch (InvalidArgumentException | DomainException $error) {
    flash_set('error', $error->getMessage());
} catch (Throwable) {
    flash_set('error', 'The map could not be saved. Check that the Route Maps migration is applied, then reload and compare before retrying.');
}
redirect($redirect);
