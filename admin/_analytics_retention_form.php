<div class="card card-soft"><div class="card-body">
    <h1 class="h4">Analytics retention hold</h1>
    <div class="alert alert-info">Disposal is paused. This page records a global hold and shows a dry run; it cannot delete records or unpause cleanup.</div>
    <p class="text-secondary">Imran is primary and Antony is backup for the monthly review. The saved reviewer is the signed-in admin account; personal account mapping and private backup/recovery evidence remain separate.</p>
    <ul><?php foreach($retentionReport['blocked_reasons'] as $reason): ?><li><?= e($reason) ?></li><?php endforeach; ?></ul>
    <dl class="row">
        <dt class="col-sm-4">Runtime click-event cutoff</dt><dd class="col-sm-8"><?= $retentionReport['runtime_days']!==null?e($retentionReport['runtime_days'].' days'):'Unavailable' ?></dd>
        <dt class="col-sm-4">Manual analytics review rule</dt><dd class="col-sm-8"><?php if($retentionReport['manual_rule']): ?><?= e($retentionReport['manual_rule']['period'].' '.$retentionReport['manual_rule']['unit'].'; '.$retentionReport['manual_rule']['trigger']) ?><?php else: ?>Not configured/verified<?php endif; ?></dd>
        <dt class="col-sm-4">Periods and trigger agree</dt><dd class="col-sm-8"><?= $retentionReport['periods_reconciled']?'Yes; this does not authorize disposal':'No; review required' ?></dd>
        <dt class="col-sm-4">Dry-run cutoff</dt><dd class="col-sm-8"><?= e($retentionReport['cutoff']??'Unavailable') ?></dd>
        <dt class="col-sm-4">Events older than cutoff</dt><dd class="col-sm-8"><?= $retentionReport['candidate_count']!==null?(int)$retentionReport['candidate_count']:'Unavailable' ?> — candidate count only; zero records deleted</dd>
        <dt class="col-sm-4">Last recorded review</dt><dd class="col-sm-8"><?= $hold['configured']?e('Admin #'.$hold['reviewed_by'].' at '.$hold['reviewed_at']):'Not yet recorded' ?></dd>
    </dl>
    <form method="post" action="<?= e(base_url('admin/analytics_retention_save.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="analytics_hold[revision]" value="<?= (int)$retentionReport['hold']['revision'] ?>">
        <label class="form-label" for="holdReason">Hold reason</label><input class="form-control mb-3" id="holdReason" name="analytics_hold[reason]" maxlength="500" required value="<?= e($hold['reason']) ?>">
        <label class="form-label" for="holdReference">Decision reference (optional)</label><input class="form-control mb-3" id="holdReference" name="analytics_hold[reference]" maxlength="120" value="<?= e($hold['reference']) ?>">
        <label class="form-label" for="holdReviewDate">Next review date</label><input class="form-control mb-3" id="holdReviewDate" type="date" name="analytics_hold[next_review_date]" required value="<?= e($hold['next_review_date']) ?>">
        <p class="small text-secondary">Use non-sensitive reasons/reference IDs. A due/overdue date never releases a hold. This mutable row records the current review, not an immutable history; retain decision evidence in the approved private process once storage is verified. It covers click-event analytics only, not page views, security logs, bookings, email, spreadsheets, provider data or backups.</p>
        <button class="btn btn-success" type="submit">Record review and keep disposal paused</button>
        <a class="btn btn-outline-secondary" href="<?= e(base_url('admin/analytics_retention.php')) ?>">Refresh dry run</a>
    </form>
</div></div>
