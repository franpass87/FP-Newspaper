#!/usr/bin/env node
/* eslint-disable no-console */
const fs = require('fs');
const path = require('path');

const APPLY_FLAG = process.argv.find((arg) => arg.startsWith('--apply='));
const DOCS_FLAG = process.argv.includes('--docs');
const APPLY = APPLY_FLAG ? APPLY_FLAG.split('=')[1] === 'true' : false;

const AUTHOR = {
  name: 'Francesco Passeri',
  email: 'info@francescopasseri.com',
  uri: 'https://francescopasseri.com',
};

const SHORT_DESCRIPTION = 'Gestisce dossier tematici con schede riassuntive automatiche, timeline eventi e mappe interattive. Include follow-up email per redazioni WordPress.';

const ROOT = path.resolve(__dirname, '..');

const targets = [];

function readFile(filePath) {
  return fs.readFileSync(filePath, 'utf8');
}

function writeFile(filePath, original, updated) {
  if (original === updated) {
    return false;
  }
  if (APPLY) {
    const backupPath = `${filePath}.bak`;
    if (!fs.existsSync(backupPath)) {
      fs.writeFileSync(backupPath, original, 'utf8');
    }
    fs.writeFileSync(filePath, updated, 'utf8');
  }
  return true;
}

function ensurePluginHeader() {
  const filePath = path.join(ROOT, 'cv-dossier-context.php');
  const original = readFile(filePath);
  let content = original;
  const updates = [];

  const headerReplacements = [
    { regex: /(\* Plugin Name:\s*)(.+)/, value: 'CV Dossier & Context' },
    { regex: /(\* Plugin URI:\s*).*/, value: AUTHOR.uri, ensure: true },
    { regex: /(\* Description:\s*).*/, value: SHORT_DESCRIPTION },
    { regex: /(\* Author:\s*).*/, value: AUTHOR.name },
    { regex: /(\* Author URI:\s*).*/, value: AUTHOR.uri, ensure: true },
  ];

  headerReplacements.forEach((item) => {
    if (item.ensure && !new RegExp(item.regex.source, 'm').test(content)) {
      // Insert line after Plugin Name or Author as appropriate
      const lines = content.split('\n');
      const insertAfterIndex = lines.findIndex((line) => line.includes('Plugin Name'));
      if (item.regex.source.includes('Author URI')) {
        const authorLineIndex = lines.findIndex((line) => line.includes('* Author:'));
        if (authorLineIndex !== -1) {
          lines.splice(authorLineIndex + 1, 0, ` * Author URI: ${item.value}`);
          content = lines.join('\n');
          updates.push(item.regex.source.includes('Author URI') ? 'Author URI' : 'Plugin URI');
          return;
        }
      }
      if (insertAfterIndex !== -1) {
        lines.splice(insertAfterIndex + 1, 0, ` * Plugin URI: ${AUTHOR.uri}`);
        content = lines.join('\n');
        updates.push('Plugin URI');
        return;
      }
    }
    const newContent = content.replace(item.regex, `$1${item.value}`);
    if (newContent !== content) {
      const label = item.regex.source.includes('Description')
        ? 'Description'
        : item.regex.source.includes('Author URI')
          ? 'Author URI'
          : item.regex.source.includes('Author')
            ? 'Author'
            : item.regex.source.includes('Plugin URI')
              ? 'Plugin URI'
              : 'Plugin Name';
      updates.push(label);
      content = newContent;
    }
  });

  const docblockRegex = /@author\s+.+/;
  const linkRegex = /@link\s+.+/;
  if (!docblockRegex.test(content)) {
    content = content.replace(
      /class\s+CV_Dossier_Context\s+\{/, 
      '/**\n * Core plugin functionality for CV Dossier & Context.\n *\n * @author Francesco Passeri\n * @link https://francescopasseri.com\n */\nclass CV_Dossier_Context {'
    );
    updates.push('Class docblock');
  } else {
    const newContent = content
      .replace(docblockRegex, '@author Francesco Passeri')
      .replace(linkRegex, '@link https://francescopasseri.com');
    if (newContent !== content) {
      updates.push('Docblock metadata');
      content = newContent;
    }
  }

  if (updates.length === 0) {
    return;
  }

  if (writeFile(filePath, original, content)) {
    targets.push({ file: 'cv-dossier-context.php', fields: updates });
  }
}

function ensureComposerJson() {
  const filePath = path.join(ROOT, 'composer.json');
  const original = readFile(filePath);
  const data = JSON.parse(original);
  let changed = false;

  const desiredDescription = SHORT_DESCRIPTION;
  if (data.description !== desiredDescription) {
    data.description = desiredDescription;
    changed = true;
  }
  if (data.homepage !== AUTHOR.uri) {
    data.homepage = AUTHOR.uri;
    changed = true;
  }
  if (!data.support) {
    data.support = {};
  }
  if (data.support.issues !== AUTHOR.uri) {
    data.support.issues = AUTHOR.uri;
    changed = true;
  }
  const desiredAuthor = {
    name: AUTHOR.name,
    email: AUTHOR.email,
    homepage: AUTHOR.uri,
    role: 'Developer',
  };
  if (!Array.isArray(data.authors) || data.authors.length === 0) {
    data.authors = [desiredAuthor];
    changed = true;
  } else {
    const first = data.authors[0];
    if (
      first.name !== desiredAuthor.name ||
      first.email !== desiredAuthor.email ||
      first.homepage !== desiredAuthor.homepage ||
      first.role !== desiredAuthor.role
    ) {
      data.authors[0] = desiredAuthor;
      changed = true;
    }
  }

  const ensureScript = (name, command) => {
    if (!data.scripts) {
      data.scripts = {};
    }
    const existing = data.scripts[name];
    if (!existing || JSON.stringify(existing) !== JSON.stringify([command])) {
      data.scripts[name] = [command];
      changed = true;
    }
  };

  ensureScript('sync:author', 'node tools/sync-author-metadata.js --apply=${APPLY:-false}');
  ensureScript('sync:docs', 'node tools/sync-author-metadata.js --docs --apply=${APPLY:-false}');
  ensureScript('changelog:from-git', 'npx --yes conventional-changelog-cli -p angular -i CHANGELOG.md -s');

  if (!changed) {
    return;
  }

  const updated = `${JSON.stringify(data, null, 2)}\n`;
  if (writeFile(filePath, original, updated)) {
    targets.push({ file: 'composer.json', fields: ['authors', 'homepage', 'support', 'scripts', 'description'] });
  }
}

function ensureReadmeMd() {
  const filePath = path.join(ROOT, 'README.md');
  const original = readFile(filePath);
  let content = original;
  const replacements = [
    { regex: /\| \*\*Author\*\* \| \[[^\]]+\]\([^\)]+\) \|/g, value: `| **Author** | [${AUTHOR.name}](${AUTHOR.uri}) |` },
    { regex: /\| \*\*Requires WordPress\*\* \| [^|]+ \|/g, value: '| **Requires WordPress** | 6.0 |' },
    { regex: /\| \*\*Tested up to\*\* \| [^|]+ \|/g, value: '| **Tested up to** | 6.4 |' },
    { regex: /\| \*\*Requires PHP\*\* \| [^|]+ \|/g, value: '| **Requires PHP** | 8.0 |' },
    { regex: /## What it does\n\n[^\n]+\n/, value: `## What it does\n\n${SHORT_DESCRIPTION}\n` },
  ];

  replacements.forEach(({ regex, value }) => {
    content = content.replace(regex, value);
  });

  if (content === original) {
    return;
  }

  if (writeFile(filePath, original, content)) {
    targets.push({ file: 'README.md', fields: ['metadata table', 'short description'] });
  }
}

function ensureReadmeTxt() {
  const filePath = path.join(ROOT, 'readme.txt');
  const original = readFile(filePath);
  let content = original;
  const map = new Map([
    ['Contributors', 'franpass87, francescopasseri'],
    ['Tags', 'dossier, timeline, map, journalism, follow-up'],
    ['Requires at least', '6.0'],
    ['Tested up to', '6.4'],
    ['Requires PHP', '8.0'],
    ['Stable tag', '1.0.2'],
    ['License', 'GPLv2 or later'],
    ['License URI', 'https://www.gnu.org/licenses/gpl-2.0.html'],
  ]);

  map.forEach((value, key) => {
    const regex = new RegExp(`^${key}:.*$`, 'm');
    content = content.replace(regex, `${key}: ${value}`);
  });

  content = content.replace(/^(Gestisce dossier[^\n]*)/m, SHORT_DESCRIPTION);

  if (content === original) {
    return;
  }

  if (writeFile(filePath, original, content)) {
    targets.push({ file: 'readme.txt', fields: ['header fields', 'short description'] });
  }
}

function ensureDocs() {
  if (!DOCS_FLAG) {
    return;
  }
  const overviewPath = path.join(ROOT, 'docs', 'overview.md');
  if (fs.existsSync(overviewPath)) {
    const original = readFile(overviewPath);
    const updated = original.replace(
      /(#[^\n]+\n\n)([^\n]+\n)/,
      `$1${SHORT_DESCRIPTION}\n`
    );
    if (updated !== original && writeFile(overviewPath, original, updated)) {
      targets.push({ file: 'docs/overview.md', fields: ['short description'] });
    }
  }
}

try {
  ensurePluginHeader();
  ensureComposerJson();
  ensureReadmeMd();
  ensureReadmeTxt();
  ensureDocs();

  if (targets.length === 0) {
    console.log('No changes required.');
    process.exit(0);
  }

  const tableHeader = ['File', 'Aggiornamenti'];
  const rows = targets.map((entry) => [entry.file, entry.fields.join(', ')]);
  const widths = [
    Math.max(tableHeader[0].length, ...rows.map((r) => r[0].length)),
    Math.max(tableHeader[1].length, ...rows.map((r) => r[1].length)),
  ];

  const renderRow = (cols) => `| ${cols[0].padEnd(widths[0])} | ${cols[1].padEnd(widths[1])} |`;
  const separator = `|-${'-'.repeat(widths[0])}-|- ${'-'.repeat(widths[1])}-|`;

  console.log(renderRow(tableHeader));
  console.log(separator);
  rows.forEach((row) => console.log(renderRow(row)));

  process.exit(0);
} catch (error) {
  console.error(error);
  process.exit(1);
}
