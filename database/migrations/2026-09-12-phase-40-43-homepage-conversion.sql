-- PHASE 40-43: homepage trust, approach, category discovery, and review proof
-- HSTS is intentionally outside this migration and remains a separate decision.

SET NAMES utf8mb4;

INSERT INTO site_settings (setting_key, setting_value)
SELECT
    'homepage_new_layout_json',
    '{"trust":{"label":"Trust Strip","enabled":true,"sort_order":10},"itineraries":{"label":"Featured Itineraries","enabled":true,"sort_order":20},"experiences":{"label":"Experiences","enabled":true,"sort_order":30},"day-tours":{"label":"Day Tours","enabled":true,"sort_order":35},"destinations":{"label":"Destinations","enabled":true,"sort_order":40},"categories":{"label":"Tour Categories","enabled":true,"sort_order":45},"approach":{"label":"Why Travel With Us","enabled":true,"sort_order":50},"reviews":{"label":"Reviews","enabled":true,"sort_order":60},"cta":{"label":"CTA Banner","enabled":true,"sort_order":70}}'
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_layout_json'
);

UPDATE site_settings
SET setting_value = '{"trust":{"label":"Trust Strip","enabled":true,"sort_order":10},"itineraries":{"label":"Featured Itineraries","enabled":true,"sort_order":20},"experiences":{"label":"Experiences","enabled":true,"sort_order":30},"day-tours":{"label":"Day Tours","enabled":true,"sort_order":35},"destinations":{"label":"Destinations","enabled":true,"sort_order":40},"categories":{"label":"Tour Categories","enabled":true,"sort_order":45},"approach":{"label":"Why Travel With Us","enabled":true,"sort_order":50},"reviews":{"label":"Reviews","enabled":true,"sort_order":60},"cta":{"label":"CTA Banner","enabled":true,"sort_order":70}}'
WHERE setting_key = 'homepage_new_layout_json';

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_approach_default_hidden_applied', '1'
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_approach_default_hidden_applied'
);

UPDATE site_settings
SET setting_value = '1'
WHERE setting_key = 'homepage_new_approach_default_hidden_applied';
