# GEM Admin UI Integration Guide
**Files added:** `_gem_map_region_tab.php` · `_gem_best_time_grid.php` · `_gem_day_arc_editor.php` · `_gem_itinerary_fields.php` · `_gem_day_highlights.php` · `_gem_save_patches.php`

---

## Step 1 — Run the migration first
```bash
mysql -u sril_site -p sril_site < database/migrations/2026-05-22-gem-redesign.sql
```

---

## Step 2 — destination_form.php

Add **two** includes. Both go inside the `<form>` tag.

### 2a. Map & Region tab
Add after the "Season Availability" card and before "Images":
```php
<?php require __DIR__ . '/_gem_map_region_tab.php'; ?>
```

### 2b. Replace the existing "Best Months" section
Find the existing `## Best Months at a Glance` card and replace the entire card block with:
```php
<?php
$_gem_bt_field   = 'best_time_json';
$_gem_bt_record  = $record;
$_gem_bt_context = 'destination';
require __DIR__ . '/_gem_best_time_grid.php';
?>
```

### 2c. destination_save.php changes
Copy the patches from `_gem_save_patches.php` → **Section A**.
Three places to edit (5 min total).

---

## Step 3 — experience_form.php

### 3a. Add category dropdown
Just before the "Experience Content" textarea group, add:
```php
<div class="mb-3">
  <label class="form-label fw-semibold">Mood / Category</label>
  <?php
  $expCategories = fetch_all("SELECT id, emoji, name FROM experience_categories WHERE is_active = 1 ORDER BY sort_order ASC");
  $selCatId = (int) old_input('category_id', $record['category_id'] ?? 0);
  ?>
  <select class="form-select" name="category_id">
    <option value="">— None —</option>
    <?php foreach ($expCategories as $ec): ?>
    <option value="<?= (int)$ec['id'] ?>" <?= $selCatId === (int)$ec['id'] ? 'selected' : '' ?>>
      <?= e($ec['emoji'] . ' ' . $ec['name']) ?>
    </option>
    <?php endforeach; ?>
  </select>
  <div class="form-text">Sets the mood tile filter on the experiences listing page.</div>
</div>
```

### 3b. Add Day Arc editor
After the "Intro" textarea, add:
```php
<?php require __DIR__ . '/_gem_day_arc_editor.php'; ?>
```

### 3c. experience_save.php changes
Copy the patches from `_gem_save_patches.php` → **Section B**.

---

## Step 4 — itinerary_form.php

### 4a. Add Traveller Types + Route Endpoints
After the Category `<select>` dropdown, add:
```php
<?php require __DIR__ . '/_gem_itinerary_fields.php'; ?>
```

### 4b. Replace the existing "Best Months" section
Find the `## Best Months for This Route` card and replace with:
```php
<?php
$_gem_bt_field   = 'best_months_json';
$_gem_bt_record  = $record;
$_gem_bt_context = 'itinerary';
require __DIR__ . '/_gem_best_time_grid.php';
?>
```

### 4c. itinerary_save.php changes
Copy the patches from `_gem_save_patches.php` → **Section C**.

---

## Step 5 — itinerary_days.php

### 5a. Add highlights tag input
After the "Travel Time" input, add:
```php
<?php
$_gem_dh_record = $editRecord;
require __DIR__ . '/_gem_day_highlights.php';
?>
```

### 5b. itinerary_days.php save changes
Copy the patches from `_gem_save_patches.php` → **Section D**.

---

## What each file produces in the DB

| Admin partial | Posted fields | DB column(s) updated |
|---|---|---|
| `_gem_map_region_tab.php` | `region_slug`, `region_label`, `emoji`, `stay_duration`, `map_x`, `map_y`, `map_r`, `map_initial` | `destinations.*` |
| `_gem_best_time_grid.php` | `best_time_ratings[Jan..Dec]`, `best_time_note` | `best_time_json` or `best_months_json` |
| `_gem_day_arc_editor.php` | `day_arc[0..3][...]`, `day_arc_images[0..3]` | `experiences.day_arc_json` |
| `_gem_itinerary_fields.php` | `traveller_types`, `start_point`, `end_point` | `itineraries.*` |
| `_gem_day_highlights.php` | `highlights_json_raw` | `itinerary_days.highlights_json` |

---

## Backward compatibility
All new DB columns use `db_column_exists()` guards in the save handlers — identical to the existing pattern in the codebase. Old installs without the migration applied will silently skip the new fields. No breakage.
