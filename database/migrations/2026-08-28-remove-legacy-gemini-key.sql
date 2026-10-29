-- Removes the legacy gemini_api_key site setting after migrating
-- the AI search backend to use the OpenRouter key exclusively.

DELETE FROM site_settings WHERE setting_key = 'gemini_api_key';
