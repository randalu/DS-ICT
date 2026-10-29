'use strict';
const path = require('node:path');
const fs = require('node:fs');
const { spawnSync } = require('node:child_process');
const root = path.resolve(__dirname, '..');
const output = path.join(root, 'output');
const temp = path.join(output, 'downloads');
fs.mkdirSync(temp, { recursive: true });
const cli = path.join(path.dirname(require.resolve('playwright/package.json')), 'cli.js');
const result = spawnSync(process.execPath, [cli, 'install', 'chromium', '--no-shell'], {
  cwd: root,
  env: { ...process.env, PLAYWRIGHT_BROWSERS_PATH: path.join(output, 'browsers'), PLAYWRIGHT_SKIP_BROWSER_GC: '1', TEMP: temp, TMP: temp },
  stdio: 'inherit',
});
process.exit(result.status ?? 1);
