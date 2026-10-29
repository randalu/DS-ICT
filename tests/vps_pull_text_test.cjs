const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const text = fs.readFileSync(path.join(__dirname, '..', 'VPS-LIVE-PULL.txt'), 'utf8');
const start = text.indexOf("bash <<'RUN_LIVE_PULL'\n");
const finish = text.indexOf('\nRUN_LIVE_PULL\n', start);
const script = text.slice(start, finish + '\nRUN_LIVE_PULL\n'.length);

test('the complete terminal block has valid Bash and closed heredocs', () => {
    assert.notEqual(start, -1);
    assert.notEqual(finish, -1);
    const bash = process.env.BASH_BIN || (process.platform === 'win32' ? 'C:/Program Files/Git/bin/bash.exe' : 'bash');
    const result = spawnSync(bash, ['-n'], { input: script, encoding: 'utf8' });
    assert.equal(result.status, 0, result.stderr || result.error?.message);
});

test('the command supports both expected VPS users and keeps backups private', () => {
    assert.match(script, /id -un.*root/);
    assert.match(script, /sudo -iu serveradmin bash/);
    assert.match(script, /id -un.*serveradmin/);
    assert.match(script, /umask 077/);
    assert.match(script, /cd \/home\/srilankaprivatetravels\.com\/public_html/);
});

test('the release is pinned and newer application changes are rejected', () => {
    assert.match(script, /^code_target=(?:[0-9a-f]{40}|RELEASE_COMMIT_PENDING)$/m);
    assert.match(script, /git merge-base --is-ancestor "\$code_target" "\$target"/);
    assert.match(script, /git diff --name-only "\$code_target" "\$target"/);
    assert.match(script, /later_paths.*VPS-LIVE-PULL\.txt/);
    assert.match(script, /git show "\$target:cli\/pull_page_media_release\.sh"/);
    assert.match(script, /origin\)" == git@github-islandculture:randalu\/IslandCulture\.git/);
});

test('read-only preflight precedes apply and no destructive shortcut is suggested', () => {
    const check = script.indexOf('"$base" --check');
    const apply = script.indexOf('"$base" --apply');
    assert.ok(check > 0 && apply > check);
    assert.doesNotMatch(script, /git\s+(?:reset|clean|stash)\b/);
    assert.doesNotMatch(script, /--force(?:-with-lease)?\b/);
    assert.doesNotMatch(script, /(?:putenv|export)\s*\(?['"]?OPENAI_ADMIN_API_KEY/);
    assert.doesNotMatch(script, /\.env(?:\.production)?\s*>/);
    assert.match(text, /CODE_PULL_OK/);
    assert.match(text, /SCHEMA_RELEASE_OK/);
});

test('development source and deployment instructions are denied before file passthrough', () => {
    const rules = fs.readFileSync(path.join(__dirname, '..', '.htaccess'), 'utf8');
    const passthrough = rules.indexOf('# Let real files and directories pass through');
    const folders = rules.indexOf('storage|cli|tests|NEW-SITE');
    const instructions = rules.indexOf('^VPS-LIVE-PULL\\.txt$');
    assert.ok(folders > -1 && folders < passthrough);
    assert.ok(instructions > -1 && instructions < passthrough);
});
