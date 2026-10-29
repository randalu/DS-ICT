-- PHASE 64: make every published tour package point to a deeper planning guide.

INSERT INTO itinerary_package_links
    (informational_path, package_slug, package_path, informational_label, package_label, is_active)
VALUES
    ('/9-day-itinerary', 'sri-lanka-wildlife-safari-tour', '/tours/sri-lanka-wildlife-safari-tour', 'Read the 9-day wildlife guide', 'Compare the wildlife private tour', 1),
    ('/12-day-itinerary', 'sri-lanka-honeymoon-package', '/tours/sri-lanka-honeymoon-package', 'Read the 12-day planning guide', 'Plan the honeymoon package', 1),
    ('/7-day-itinerary', 'sri-lanka-family-tour-package', '/tours/sri-lanka-family-tour-package', 'Read the 7-day planning guide', 'Plan the family package', 1),
    ('/14-day-itinerary', 'luxury-sri-lanka-slow-travel-package', '/tours/luxury-sri-lanka-slow-travel-package', 'Read the 14-day planning guide', 'Plan the luxury package', 1)
ON DUPLICATE KEY UPDATE
    package_path = VALUES(package_path),
    informational_label = VALUES(informational_label),
    package_label = VALUES(package_label),
    is_active = VALUES(is_active);
