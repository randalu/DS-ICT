# Task Plan: Recommended Stays — Wire Partners into Itinerary Timeline

## Goal
Display real partner/hotel data inside each day card of the itinerary timeline, matching by `overnight_location` string. The business_partners system already exists with admin CRUD — this task connects it to the frontend.

## Context
- `business_partners` table exists with `destination_id` FK, `location` field, `status` column
- `business_partner_itinerary` junction table links partners to itineraries
- `itinerary_days.overnight_location` is a free-text VARCHAR(150), NOT a FK
- `itinerary.php` already calls `fetch_business_partners_for_itinerary()` at line 99 but doesn't use the result per-day
- Admin panel for partners already exists (`admin/business_partners.php`, `admin/business_partner_form.php`)

## Phases

### Phase 1: Helper Function — Fetch Partners by Location
- **Status**: pending
- Add `fetch_business_partners_for_day($itinerary_id, $location)` to `includes/schema_helpers.php`
- Query: match partners where itinerary_id matches via junction table AND location LIKE the overnight_location string
- Return max 3 partners per day
- Include: business_name, slug, hero_image, location, business_type

### Phase 2: Itinerary PHP — Fetch + Pass Partners Per Day
- **Status**: pending
- In `itinerary.php`, inside the day loop, call the new helper for each day that has `overnight_location`
- Store result in `$dayPartners` variable
- Pass to the HTML section

### Phase 3: Itinerary HTML — Display Partner Cards
- **Status**: pending
- Replace the placeholder "Recommended Stays" section with actual partner cards
- Show: partner name, thumbnail image, location, link to partner page
- Max 3 cards per day, horizontal scroll if >1

### Phase 4: CSS — Partner Card Styling in Timeline
- **Status**: pending
- Style partner cards inside `.tl-stays` section
- Small, compact cards with image + name overlay
- Horizontal flex layout

### Phase 5: Validation & Commit
- **Status**: pending
- PHP lint all changed files
- Smoke tests
- Commit, push

## Decisions
- Use free-text matching (LIKE) since overnight_location is not a FK — acceptable for 3-5 results
- Max 3 partners per day to keep cards compact
- Partner cards link to existing `/partners/[slug]` public page
