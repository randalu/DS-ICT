<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$args=array_slice($argv,1);
if(array_diff($args,['--dry-run','--json'])){fwrite(STDERR,"Only --dry-run and --json are supported. No apply operation or deletion is available.\n");exit(2);}
require_once __DIR__.'/../includes/functions.php';
$report=ict_analytics_retention_report();
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
exit($report['candidate_count']===null?1:0);
