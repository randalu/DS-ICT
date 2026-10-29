<?php
declare(strict_types=1);
if(!in_array(PHP_SAPI,['cli','cli-server'],true)){http_response_code(404);exit;}
$fixture=json_decode(file_get_contents(__DIR__.'/../../output/page-gallery-20261028/fixture.json'),true,32,JSON_THROW_ON_ERROR);
if(!preg_match('/^ict_page_media_test_[a-f0-9]{12}$/D',$fixture['database']??''))throw new RuntimeException('Invalid synthetic schema.');
putenv('DB_NAME='.$fixture['database']);putenv('APP_URL=http://127.0.0.1:'.(int)($fixture['port']??8768));putenv('APP_ENV=testing');putenv('APP_DEBUG=1');
ini_set('session.save_path',__DIR__.'/../../output/session');
