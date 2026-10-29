# SEO research review correction — 16 October 2026

Implemented locally. No paid API call, production change, migration or owner-page save was made for this correction. Browser/database writes used a disposable synthetic schema.

## Causes and fixes

- The research UI only displayed its apply button when `primary_phrase` was nonempty. The owner's research contained useful intent, questions and actions but no primary phrase, so it had no approval action. Matching page research now always offers **Review & apply keyword suggestions**.
- The comparison displays existing/proposed values, per-field approval, editable suggestions, limits and a missing-primary notice. Blank or unchecked suggestions preserve existing values; notes can be proposed from actions and uncertainties. Applying fills the editor and selects the keyword plan; it never saves automatically. **Save selected fields** remains explicit.
- Brief responses now identify their kind and owning content type/id, including previously saved research. General platform guidance and another page's brief cannot populate the current keyword plan. Late research responses do not replace another selected page's brief. The review rejects changes if its page, revision or editor changed after opening.
- The ambiguous Content button is now **Open keyword research**. Review actions appear near cached keyword plans and above research results. Empty research-list headings are omitted. The panel's script URL changes with its modification time so a refresh loads the correction.
- Research extraction distinguishes editorial keyword suggestions from measured demand: absence of search-volume data is not grounds to omit every keyword target. Existing partial briefs remain usable without another paid request.
- Arugam Bay's existing FAQ entries use `q`/`a`. The public renderer supports those aliases, but SEO validation rejected them and the standard FAQ editor showed blank rows. SEO validation now accepts exactly either `q`/`a` or `question`/`answer`, keeping nonempty/plain-text and unexpected-field checks. The shared FAQ editor displays both forms. No existing FAQs were rewritten.

## Validation and use

- Targeted SEO suite: **9 tests, 60 assertions**.
- Disposable integration rehearsal: **57 checks**, including legacy FAQ saves on destinations, itineraries and packages.
- Focused browser regression: **26 checks**, including desktop/390px review, blank primary/supporting preservation, notes approval, cancel/Escape, no automatic save, oversized suggestions, page/guidance ownership and legacy FAQ display. No uncaught errors or unexpected HTTP failures.
- PHP/JavaScript syntax, Composer validation and diff checks pass. Full PHPUnit: **204 tests, 1,334 assertions, 14 existing skips**, using the writable-session override. Results are in `output/seo-manager/keyword-review-phpunit-results.txt`.
- Browser test source: `tests/seo_keyword_review_browser.js`. Evidence and the mobile screenshot are under ignored `output/seo-manager/`.

To use existing Arugam Bay research: copy any unsaved edits before refreshing, select the page, choose **Open keyword research → Review & apply keyword suggestions**, approve/edit the fields, and choose **Apply approved suggestions to editor**. Review the populated fields, then explicitly save. Missing primary/supporting suggestions stay as they were unless the owner enters replacements.

The implementation uses the existing OpenAI structured-output interface; no model or endpoint changed. [Official structured-output documentation](https://developers.openai.com/api/docs/guides/structured-outputs).
