# Findings

## 2026-07-10 — Partners System Research

### Existing Tables (from 2026-04-30-business-partners.sql)
- `business_partner_categories` — category taxonomy (Hotels & Accommodations seeded)
- `business_partners` — partner records with `destination_id`, `location`, `status`, `hero_image`
- `business_partner_photos` — photo gallery
- `business_partner_destination` — M2M partner-to-destination
- `business_partner_itinerary` — M2M partner-to-itinerary

### Key Schema Facts
- `itinerary_days.overnight_location` is VARCHAR(150) — plain text, NOT a FK
- `business_partners.location` is also VARCHAR — both are free-text strings
- Matching strategy: LIKE comparison between overnight_location and partner location
- Partners have `status` field: applied/contacted/negotiating/agreed/published/rejected
- Only `published` status partners should show on frontend

### Existing PHP Coverage
- `fetch_business_partners_for_itinerary($itinerary_id, $limit)` already exists
- `itinerary.php` line 99-101 already fetches partners for the itinerary
- Helper functions in `includes/schema_helpers.php` lines 2460-2736
- Admin CRUD: admin/business_partners.php, _form.php, _save.php, _delete.php, _status_save.php
- Public pages: business_partners.php (list), business_partner_apply.php (application form)

### What's Missing
- No per-day partner display in timeline
- No function to match partners by overnight_location string
- The placeholder in itinerary.php (lines 453-459) just links to contact page
