<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
admin_require_auth();
$adminTitle='Analytics Retention Hold';$adminPage='analytics_retention';
$retentionReport=ict_analytics_retention_report();$hold=$retentionReport['hold'];
$old=old_input('analytics_hold',null);
if(is_array($old)){foreach(['reason','reference','next_review_date'] as $key){if(is_string($old[$key]??null)){$hold[$key]=$old[$key];}}}
require __DIR__.'/_top.php';require __DIR__.'/_analytics_retention_form.php';require __DIR__.'/_bottom.php';
