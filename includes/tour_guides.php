<?php
declare(strict_types=1);
/** Retired API: retained only to reject stale clients and historical callers. */
function tour_guides_available(): bool { return false; }
function tour_guide_rows(array $tour, bool $lock=false): array { return []; }
function tour_guide_revision(array $tour): string { return hash('sha256','retired'); }
function tour_guide_save(int $id,array $input): void { throw new DomainException('Separate Tour reference pages are retired. Edit this Tour programme instead.'); }
