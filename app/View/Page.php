<?php
declare(strict_types=1);

namespace ICT\View;

/**
 * Immutable page model.
 *
 * One instance describes everything the layout needs: the view template, the
 * <head> metadata, the JSON-LD graph, the body class hook and the payload the
 * template renders. Controllers build a Page; views never query the database.
 */
final class Page
{
    /** @param array<string,string> $meta */
    public function __construct(
        public readonly string $template,
        public readonly array $meta = [],
        public readonly array $data = [],
        public readonly array $schema = [],
        public readonly string $bodyClass = '',
        public readonly array $breadcrumbs = [],
        public readonly bool $recordPageView = true,
    ) {
    }

    /**
     * Read a meta field with a fallback, so templates never have to guess
     * whether a controller supplied canonical/OG overrides.
     */
    public function meta(string $key, string $default = ''): string
    {
        $value = $this->meta[$key] ?? '';

        return $value === '' ? $default : (string) $value;
    }

    /** Return a copy with extra meta values; unknown keys are appended. */
    public function withMeta(array $meta): self
    {
        return new self(
            $this->template,
            array_merge($this->meta, $meta),
            $this->data,
            $this->schema,
            $this->bodyClass,
            $this->breadcrumbs,
            $this->recordPageView,
        );
    }

    /** Return a copy with extra template data. */
    public function withData(array $data): self
    {
        return new self(
            $this->template,
            $this->meta,
            array_merge($this->data, $data),
            $this->schema,
            $this->bodyClass,
            $this->breadcrumbs,
            $this->recordPageView,
        );
    }

    /** Return a copy carrying the JSON-LD graph emitted by schema_output(). */
    public function withSchema(array $schema): self
    {
        return new self(
            $this->template,
            $this->meta,
            $this->data,
            $schema,
            $this->bodyClass,
            $this->breadcrumbs,
            $this->recordPageView,
        );
    }
}
