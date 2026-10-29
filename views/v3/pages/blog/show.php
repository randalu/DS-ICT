<?php
declare(strict_types=1);

/**
 * Blog article — reading layout with a narrow measure and related reading.
 *
 * @var \ICT\View\Page $page
 */

$d = $page->data;
?>
<article>
    <header class="ix-article-head">
        <div class="ix-wrap ix-wrap--narrow">
            <nav class="ix-crumbs" aria-label="Breadcrumb">
                <a href="<?= e(base_url()) ?>">Home</a>
                <span aria-hidden="true">/</span>
                <a href="<?= e($d['blog_url']) ?>">Blog</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page"><?= e($d['title']) ?></span>
            </nav>

            <h1 class="ix-h1"><?= e($d['title']) ?></h1>

            <?php if ($d['excerpt'] !== ''): ?>
                <p class="ix-lead"><?= e($d['excerpt']) ?></p>
            <?php endif; ?>

            <p class="ix-article-meta">
                <?= e($d['author']) ?><?php if ($d['published'] !== ''): ?> · <time datetime="<?= e($d['published']) ?>"><?= e($d['published']) ?></time><?php endif; ?>
            </p>
        </div>
    </header>

    <div class="ix-wrap ix-article-hero">
        <img src="<?= e($d['hero']) ?>" alt="" fetchpriority="high" decoding="async" width="1200" height="675">
    </div>

    <div class="ix-wrap ix-wrap--narrow ix-article-body">
        <div class="ix-prose ix-prose--article"><?= $d['content'] ?></div>

        <div class="ix-article-foot">
            <a class="ix-btn" href="<?= e($d['cta_url']) ?>">Plan a trip like this</a>
            <a class="ix-link" href="<?= e($d['blog_url']) ?>">Back to the blog <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</article>

<?php if ($d['related'] !== []): ?>
<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">Read next</h2>
            <a class="ix-link" href="<?= e($d['blog_url']) ?>">All articles <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-journal-list">
            <?php foreach ($d['related'] as $card): ?>
                <a class="ix-journal-row" href="<?= e($card['url']) ?>">
                    <span class="ix-journal-row__thumb">
                        <img src="<?= e($card['image']) ?>" alt="" loading="lazy" decoding="async">
                    </span>
                    <h3><?= e($card['title']) ?></h3>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
