-- Replace footer social icon uploads with tracked, fixed-size SVG assets.
-- This prevents broken footer icons when assets/uploads/social files are missing.

SET NAMES utf8mb4;

UPDATE social_links SET icon_image = 'assets/images/social-icons/facebook.svg', icon_text = '' WHERE slot_key = 'facebook';
UPDATE social_links SET icon_image = 'assets/images/social-icons/instagram.svg', icon_text = '' WHERE slot_key = 'instagram';
UPDATE social_links SET icon_image = 'assets/images/social-icons/youtube.svg', icon_text = '' WHERE slot_key = 'youtube';
UPDATE social_links SET icon_image = 'assets/images/social-icons/pinterest.svg', icon_text = '' WHERE slot_key = 'pinterest';
UPDATE social_links SET icon_image = 'assets/images/social-icons/x.svg', icon_text = '' WHERE slot_key = 'custom_1';
UPDATE social_links SET icon_image = 'assets/images/social-icons/tiktok.svg', icon_text = '' WHERE slot_key = 'custom_2';
UPDATE social_links SET icon_image = 'assets/images/social-icons/tripadvisor.svg', icon_text = '' WHERE slot_key = 'tripadvisor';
UPDATE social_links SET icon_image = 'assets/images/social-icons/gotourride.svg', icon_text = '' WHERE slot_key = 'linkedin';
UPDATE social_links SET icon_image = 'assets/images/social-icons/mastodon.svg', icon_text = '' WHERE slot_key = 'custom_3';
