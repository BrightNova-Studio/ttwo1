#!/usr/bin/env node
/**
 * Build, sign, verify and publish-ready-package the Android app.
 *
 *   node scripts/release.mjs              rebuild the current version (no bump)
 *   node scripts/release.mjs patch        1.0.0 -> 1.0.1   (also: minor, major, or an exact "1.4.0")
 *   node scripts/release.mjs patch --aab  also build the Play Store bundle (.aab)
 *
 * Output (repo root /public/downloads):
 *   1two1-<version>.apk   the versioned file (kept for rollbacks)
 *   1two1.apk             always the newest
 *   android.json          version, size, SHA-256 and signing-certificate fingerprint (read by the website)
 * Upload the .apk files to the server by scp (they are not committed to git); android.json is committed.
 */
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '..');                // android-app/
const repo = path.resolve(root, '..');                // repo root
const androidDir = path.join(root, 'android');
const outDir = path.join(repo, 'public', 'downloads');

const args = process.argv.slice(2);
const bump = args.find((a) => !a.startsWith('--'));
const wantAab = args.includes('--aab');

const sdk = process.env.ANDROID_HOME || path.join(os.homedir(), 'AppData', 'Local', 'Android', 'Sdk');
const jdk = process.env.JAVA_HOME || 'C:\\Program Files\\Android\\Android Studio\\jbr';
const env = { ...process.env, ANDROID_HOME: sdk, ANDROID_SDK_ROOT: sdk, JAVA_HOME: jdk };

const run = (cmd, argv, opts = {}) => {
  const r = spawnSync(cmd, argv, { cwd: root, env, stdio: opts.capture ? ['ignore', 'pipe', 'inherit'] : 'inherit', shell: true, encoding: 'utf8', ...opts });
  if (r.status !== 0) { console.error(`\nFAILED: ${cmd} ${argv.join(' ')}`); process.exit(r.status || 1); }
  return r.stdout || '';
};

if (!fs.existsSync(path.join(androidDir, 'keystore.properties'))) {
  console.error('No signing key configured (android/keystore.properties). Refusing to build an unsigned release.');
  process.exit(1);
}
fs.writeFileSync(path.join(androidDir, 'local.properties'), `sdk.dir=${sdk.replace(/\\/g, '/')}\n`);

/* ------------------------------------------------------------ version */
const vfile = path.join(root, 'version.json');
const v = JSON.parse(fs.readFileSync(vfile, 'utf8'));
if (bump) {
  const [maj, min, pat] = v.versionName.split('.').map(Number);
  v.versionName = /^\d+\.\d+\.\d+$/.test(bump) ? bump
    : bump === 'major' ? `${maj + 1}.0.0`
    : bump === 'minor' ? `${maj}.${min + 1}.0`
    : bump === 'patch' ? `${maj}.${min}.${pat + 1}`
    : (() => { console.error('Use patch, minor, major or an exact x.y.z'); process.exit(1); })();
  v.versionCode += 1;
  const notes = args.find((a) => a.startsWith('--notes='));
  v.notes = notes ? notes.slice(8) : v.notes;
  fs.writeFileSync(vfile, JSON.stringify(v, null, 2) + '\n');
}
console.log(`\n== 1two1 Android ${v.versionName} (code ${v.versionCode}) ==\n`);

// The website reads this suffix from the User-Agent to know which app version is calling.
const cfgFile = path.join(root, 'capacitor.config.json');
const cfg = JSON.parse(fs.readFileSync(cfgFile, 'utf8'));
cfg.android.appendUserAgent = `1two1-android/${v.versionName} (${v.versionCode})`;
fs.writeFileSync(cfgFile, JSON.stringify(cfg, null, 2) + '\n');

/* -------------------------------------------------------------- build */
run('npx', ['cap', 'sync', 'android']);
const gradle = `"${path.join(androidDir, process.platform === 'win32' ? 'gradlew.bat' : 'gradlew')}"`;
run(gradle,['--no-daemon', 'clean', 'assembleRelease', ...(wantAab ? ['bundleRelease'] : [])], { cwd: androidDir });

const built = path.join(androidDir, 'app', 'build', 'outputs', 'apk', 'release', 'app-release.apk');
if (!fs.existsSync(built)) { console.error('Build finished but no signed APK was produced.'); process.exit(1); }

/* ------------------------------------------------------------- verify */
const bt = fs.readdirSync(path.join(sdk, 'build-tools')).sort().pop();
const apksigner = path.join(sdk, 'build-tools', bt, process.platform === 'win32' ? 'apksigner.bat' : 'apksigner');
const out = run(`"${apksigner}"`, ['verify', '--verbose', '--print-certs', `"${built}"`], { capture: true });
if (!/Verifies/.test(out)) { console.error('apksigner could not verify the APK.'); process.exit(1); }
const cert = (out.match(/certificate SHA-256 digest: ([0-9a-f]+)/i) || [])[1] || '';
const schemes = ['v2', 'v3'].filter((s) => new RegExp(`scheme \\(APK Signature Scheme ${s}\\): true`, 'i').test(out));
console.log(`\nSigned and verified. Schemes: ${schemes.join(', ')}. Certificate SHA-256: ${cert}`);

/* ------------------------------------------------------------ package */
fs.mkdirSync(outDir, { recursive: true });
const bytes = fs.readFileSync(built);
const sha256 = createHash('sha256').update(bytes).digest('hex');
const versioned = `1two1-${v.versionName}.apk`;
fs.writeFileSync(path.join(outDir, versioned), bytes);
fs.writeFileSync(path.join(outDir, '1two1.apk'), bytes);
if (wantAab) {
  const aab = path.join(androidDir, 'app', 'build', 'outputs', 'bundle', 'release', 'app-release.aab');
  if (fs.existsSync(aab)) fs.copyFileSync(aab, path.join(outDir, `1two1-${v.versionName}.aab`));
}

const meta = {
  name: '1two1',
  package: 'in.thesecretbasket.onetwoone',
  versionName: v.versionName,
  versionCode: v.versionCode,
  minAndroid: '7.0',
  size: bytes.length,
  sha256,
  signingCertSha256: cert,
  file: '1two1.apk',
  versionedFile: versioned,
  released: new Date().toISOString().slice(0, 10),
  notes: v.notes,
};
fs.writeFileSync(path.join(outDir, 'android.json'), JSON.stringify(meta, null, 2) + '\n');

// Human-readable history: newest first, never rewritten.
const log = path.join(root, 'CHANGELOG.md');
const prev = fs.existsSync(log) ? fs.readFileSync(log, 'utf8') : '# 1two1 Android: release history\n';
if (!prev.includes(`## ${v.versionName} `)) {
  const entry = `\n## ${v.versionName} (code ${v.versionCode}), ${meta.released}\n- ${v.notes}\n- APK SHA-256: \`${sha256}\`\n`;
  fs.writeFileSync(log, prev.replace(/^(# [^\n]*\n)/, `$1${entry}`));
}

console.log(`\nDone.\n  ${path.join(outDir, versioned)}\n  size ${(bytes.length / 1048576).toFixed(2)} MB\n  sha256 ${sha256}\n`);
