<?php declare(strict_types=1); ?>
<section class="container py-5 mt-5">
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto">
            <h1 class="display-4 fw-bold mb-3 text-center">AI Search</h1>
            <p class="lead text-muted text-center">Find Sri Lanka destinations, itineraries and experiences.</p>
            <?php if ($publicAiState['error'] !== ''): ?><div class="alert alert-warning" role="alert"><?= e($publicAiState['error']) ?></div><?php endif; ?>
            <?php if ($publicAiState['enabled']): ?>
                <p id="publicAiNotice">When you select <strong>Search with AI</strong>, your search text is sent to OpenRouter and its selected model provider to match trip content. Their data handling and retention depend on the provider and account settings. Please omit names, contact details, passport numbers, booking references and other private information. Results are trip ideas; confirm suitability and availability with us.</p>
                <form method="POST" action="<?= e(ict_privacy_url('ai-search')) ?>" class="mt-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ai_notice" value="<?= e(ICT_PUBLIC_AI_NOTICE) ?>">
                    <label class="form-label" for="publicAiQuery">What would you like to explore?</label>
                    <textarea id="publicAiQuery" name="q" class="form-control" rows="3" maxlength="1000" required aria-describedby="publicAiNotice publicAiLimit" placeholder="e.g. Elephants and tea plantations"><?= e($publicAiState['query']) ?></textarea>
                    <p class="small text-muted mt-2" id="publicAiLimit">Up to 1,000 characters. Five searches per 15 minutes per session and IP.</p>
                    <button class="btn btn-primary px-4" type="submit" name="search_action" value="search_with_ai">Search with AI</button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">AI search is currently unavailable. Explore our trip ideas or ask us to help plan your visit.</div>
            <?php endif; ?>
            <nav class="d-flex flex-wrap gap-3 mt-4" aria-label="Explore without AI">
                <a href="<?= e(base_url('destinations')) ?>">Destinations</a>
                <a href="<?= e(base_url('itineraries')) ?>">Itineraries</a>
                <a href="<?= e(base_url('experiences')) ?>">Experiences</a>
                <a href="<?= e(base_url('contact')) ?>">Ask us to plan your trip</a>
            </nav>
        </div>
    </div>
    <?php if ($publicAiState['searched']): ?>
        <div class="row"><div class="col-lg-8 mx-auto">
            <h2 class="h5 mb-4">Trip ideas for &ldquo;<?= e($publicAiState['query']) ?>&rdquo;</h2>
            <?php if (!$publicAiState['results']): ?>
                <div class="alert alert-info" role="status">No matching trip ideas found. Try a different search, or use the links above.</div>
            <?php else: ?>
                <div class="list-group">
                <?php foreach ($publicAiState['results'] as $result): ?>
                    <?php $resultUrl = ict_public_ai_result_url($result['url'] ?? ''); if ($resultUrl === '') { continue; } ?>
                    <a href="<?= e($resultUrl) ?>" class="list-group-item list-group-item-action p-4 border-0 shadow-sm rounded mb-3">
                        <h3 class="h5 mb-2 text-primary"><?= e($result['title'] ?? '') ?></h3>
                        <p class="mb-1 text-secondary"><?= e($result['excerpt'] ?? '') ?></p>
                    </a>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div></div>
    <?php endif; ?>
</section>
