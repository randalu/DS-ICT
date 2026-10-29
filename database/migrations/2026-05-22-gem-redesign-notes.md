# GEM Redesign — DB Migration Reference
`2026-05-22-gem-redesign.sql`

---

## What this migration does — one line per table

| Table | What was added | Why |
|---|---|---|
| `destinations` | `map_x`, `map_y`, `map_r`, `map_initial` | SVG pin coordinates for the interactive map |
| `destinations` | `region_slug`, `region_label` | Region filter pills (Cultural Triangle, Hill Country, etc.) |
| `destinations` | `emoji` | List panel thumbnail fallback — no image needed |
| `destinations` | `stay_duration` | Card meta chip e.g. "Half day" |
| `destinations` | `best_time_json` | 12-month colour-strip on detail page |
| `destinations` | `route_notes_json` | Driving notes section on detail page |
| `destinations` | `itinerary_count` | Denormalised — shown on map hover card without JOIN |
| `experience_categories` | **New table** | The 7 mood tiles (Wildlife, Culture, Beach…) |
| `experiences` | `category_id` | Primary mood FK |
| `experiences` | `day_arc_json` | Day-in-the-life content (Morning/Midday/Afternoon/Evening) |
| `experiences` | `itinerary_count`, `destination_count` | Cached counts for listing cards |
| `experience_category_pivot` | **New table** | Many-to-many for secondary moods (future) |
| `itineraries` | `traveller_types` | Filter chip — couple/family/honeymoon/solo/group |
| `itineraries` | `start_point`, `end_point` | Filter chip — "Starts: Colombo ▾" |
| `itineraries` | `best_months_json` | 12-month strip on detail page |
| `itineraries` | `destination_count` | Cached stop count shown on cards |
| `itinerary_categories` | `theme_slug`, `emoji` | Powers the theme filter chip on listing page |
| `itinerary_days` | `highlights_json` | Bullet chips in the timeline expand panel |
| `route_sessions` | **New table** | Server-side route/wishlist persistence |

---

## Column details

### `destinations.region_slug`
Allowed values (enforced by admin UI, not DB constraint for flexibility):

| slug | Label | Map zone |
|---|---|---|
| `cultural` | Cultural Triangle | North-central ellipse |
| `hill` | Hill Country | Central ellipse |
| `wildlife` | Wildlife | Lower-central ellipse |
| `south` | South Coast | South ellipse |
| `east` | East Coast | East ellipse |

### `destinations.best_time_json`
```json
{
  "months": [
    {"month": "Jan", "rating": "great"},
    {"month": "Feb", "rating": "great"},
    {"month": "Mar", "rating": "okay"},
    {"month": "Apr", "rating": "avoid"},
    {"month": "May", "rating": "avoid"},
    {"month": "Jun", "rating": "avoid"},
    {"month": "Jul", "rating": "okay"},
    {"month": "Aug", "rating": "okay"},
    {"month": "Sep", "rating": "okay"},
    {"month": "Oct", "rating": "avoid"},
    {"month": "Nov", "rating": "great"},
    {"month": "Dec", "rating": "great"}
  ]
}
```
Ratings: `great` → green, `okay` → amber, `avoid` → grey.

### `destinations.route_notes_json`
```json
[
  {
    "from": "Colombo Airport",
    "to": "Sigiriya",
    "duration": "3.5 hrs",
    "road": "A1 then A6",
    "notes": "Dual carriageway most of the way. Comfortable drive."
  }
]
```

### `experiences.day_arc_json`
```json
[
  {
    "time_of_day": "Morning",
    "icon": "🌅",
    "heading": "Early Drive from Sigiriya",
    "body": "Leave by 6am to beat the heat...",
    "image_path": "uploads/experiences/minneriya-morning.jpg"
  },
  {
    "time_of_day": "Midday",
    "icon": "☀️",
    "heading": "Jeep Safari in the Park",
    "body": "Enter with your private jeep...",
    "image_path": "uploads/experiences/minneriya-elephants.jpg"
  },
  {
    "time_of_day": "Afternoon",
    "icon": "🌿",
    "heading": "Lunch & a Village Stop",
    "body": "Return via a roadside rice and curry lunch...",
    "image_path": null
  },
  {
    "time_of_day": "Evening",
    "icon": "🌙",
    "heading": "Back at the Lodge",
    "body": "Return by 3–4pm...",
    "image_path": null
  }
]
```

### `itineraries.traveller_types`
Stored as a comma-separated string. Query with `FIND_IN_SET()` or `LIKE '%couple%'`.
```
couple,honeymoon
family
solo
```

### `itinerary_days.highlights_json`
```json
["🏛 Dambulla Caves", "🌿 Spice Garden", "🚗 ~4 hrs drive"]
```

### `route_sessions` token flow
1. On first visit, PHP checks for cookie `ict_route_token`
2. If missing, generate `bin2hex(random_bytes(32))`, set cookie (30 day expiry), INSERT row
3. On every `+` / `♡` action, UPDATE `dest_slugs` / `exp_slugs`
4. On "Build My Route" / "Send My Wishlist", read the session and pre-fill the inquiry form
5. On inquiry submission, set `converted = 1`, `inquiry_id = <new_id>`
6. Cleanup cron: `DELETE FROM route_sessions WHERE expires_at < NOW()`

---

## How to apply

```bash
# On the VPS
mysql -u sril_site -p sril_site < database/migrations/2026-05-22-gem-redesign.sql
```

The migration is **idempotent** — safe to run more than once. Every `ALTER` is guarded by `IF NOT EXISTS` and every `INSERT` uses `INSERT IGNORE`.

---

## Admin UI changes needed (post-migration)

### `destinations` form — new tab: **"Map & Region"**
| Field | Input type | Notes |
|---|---|---|
| `region_slug` | `<select>` | cultural / hill / wildlife / south / east |
| `region_label` | `<input text>` | Auto-filled from region_slug selection |
| `emoji` | `<input text>` | Single emoji character |
| `map_x` | `<input number step=0.5>` | SVG viewBox coords (0–220) |
| `map_y` | `<input number step=0.5>` | SVG viewBox coords (0–345) |
| `map_r` | `<select>` | 5 / 5.5 / 6 / 6.5 / 7 / 7.5 / 8 / 8.5 / 9 |
| `map_initial` | `<input text maxlength=2>` | Letter(s) inside pin |
| `stay_duration` | `<input text>` | e.g. Half day, 1–2 nights |

### `destinations` form — new section: **"Best Time (Monthly)"**
12-month grid. Each month has a 3-option toggle: `great` / `okay` / `avoid`.
Saved as `best_time_json`.

### `experiences` form — new fields
| Field | Input type |
|---|---|
| `category_id` | `<select>` from `experience_categories` |
| `day_arc_json` | 4-row repeater: time_of_day, icon (emoji), heading, body (textarea), image (media picker) |

### `itineraries` form — new fields
| Field | Input type |
|---|---|
| `traveller_types` | Multi-checkbox: couple / family / honeymoon / solo / group |
| `start_point` | `<input text>` or `<select>` |
| `end_point` | `<input text>` or `<select>` |
| `best_months_json` | 12-month grid (same as destinations) |

### `itinerary_days` form — new field
| Field | Input type |
|---|---|
| `highlights_json` | Tag input — each tag becomes an array item |
