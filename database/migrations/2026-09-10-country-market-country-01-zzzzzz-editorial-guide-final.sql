-- COUNTRY-01: record that the editorial guide passed the UK pilot approval
-- boundary and is the applied standard for the remaining market pages.

SET NAMES utf8mb4;

UPDATE country_market_editorial_guides
SET version = '1.0',
    status = 'approved_applied',
    approved_at = COALESCE(approved_at, CURRENT_TIMESTAMP),
    updated_at = CURRENT_TIMESTAMP
WHERE guide_key = 'country-market-editorial-v1';
