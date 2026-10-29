<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/seo_manager.php';
echo json_encode(['configured'=>admin_openai_key()!=='','request_schema'=>db_table_exists('admin_ai_requests'),'asset_revision_schema'=>db_table_exists('media_asset_alt_revisions'),'placement_schema'=>db_table_exists('media_placement_alts'),'models'=>['alt'=>admin_openai_model('alt'),'seo'=>admin_openai_model('seo'),'audit'=>admin_openai_model('audit')]],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
