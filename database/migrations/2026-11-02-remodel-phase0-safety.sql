-- Phase 0 safety fixes for the v2 remodel.
--
-- Idempotent and additive: no table or column is dropped, no content row is
-- destroyed. Only two setting values change and one placeholder review is
-- unpublished.
--
-- 1. business_partners_public_enabled was 1 while business_partners had 0 rows,
--    so /business-partners advertised an empty directory. Keep the feature flag
--    available but switch the public surface off until partners exist.
-- 2. ai_api_key stored a live OpenRouter credential inside site_settings. The
--    key is removed here and must be rotated at the provider; it belongs in
--    .env (Phase 7), never in a database row that admins can read back.
--    test_key is a leftover from an earlier settings round-trip test.
-- 3. The only reviews row is a placeholder ("Test Guest - Remove Later") that is
--    is_published = 1. The public reviews page already filters it, but any
--    future aggregate/schema query would not. Unpublish rather than delete so
--    the record stays auditable.

UPDATE site_settings
SET setting_value = '0'
WHERE setting_key = 'business_partners_public_enabled';

DELETE FROM site_settings
WHERE setting_key IN ('ai_api_key', 'test_key');

UPDATE reviews
SET is_published = 0,
    is_featured = 0
WHERE customer_name LIKE 'Test Guest%';
