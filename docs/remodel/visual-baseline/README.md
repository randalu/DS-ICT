# Visual baseline

## Status

Screenshot capture at 390 / 768 / 1440 could **not** be produced in this
environment. Headless Chrome aborts before first paint:

```
FATAL:mojo\public\cpp\platform\platform_channel.cc:112] Check failed:
Access is denied. (0x5)
```

Chrome cannot create its IPC channel under the sandboxed shell, and a retry
with wider permissions produced the same failure. Browser-based visual capture
therefore has to run outside the agent sandbox (Laragon shell or CI), or the
sign-off has to happen in the browser at `http://localhost/ict/`.

## How to reproduce

```powershell
$chrome = "C:\Program Files\Google\Chrome\Application\chrome.exe"
foreach ($w in 390, 768, 1440) {
    & $chrome --headless=new --disable-gpu --hide-scrollbars `
        --virtual-time-budget=8000 --window-size="$w,900" `
        --user-data-dir="$PWD\build\tmp\chrome-profile" `
        --screenshot="docs\remodel\visual-baseline\home-$w.png" `
        "http://localhost/ict/"
}
```

The pre-remodel capture is reproducible from the `pre-remodel` tag:

```powershell
git stash
git checkout pre-remodel -- index.php
# run the loop above into home-legacy-$w.png
git checkout remodel/v2 -- index.php
```

## What was recorded instead

- Baseline HTTP responses for `/`, `/destinations`, `/reviews` (200) and
  `/business-partners` (404 after the Phase 0 safety fix).
- Full PHPUnit output: `build/baseline/tests-raw.txt`.
- Route gate smoke: 84 passed, 0 failed, 1 skipped.
- Image alt lint: 427 issues (expected while `assets/uploads/` is empty).
- Page weight before the rewrite: 110,572 bytes of HTML for `/`.
