import { readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(fileURLToPath(new URL('.', import.meta.url)), '..');
const ignoredDirectories = new Set(['.git', 'node_modules', 'vendor', 'storage', 'public/build', 'bootstrap/cache']);
const expectedBuildScripts = {
    'security:audit': 'node scripts/security-audit.mjs',
    build: 'npm run security:audit && vite build',
    dev: 'npm run security:audit && vite',
};
const forbiddenLifecycleScripts = new Set(['preinstall', 'install', 'postinstall', 'prepare', 'prepublish', 'prepublishOnly']);
const suspiciousNames = new Set([
    ['api', '.js'].join(''),
    ['temp_auto_push', '.bat'].join(''),
    ['temp_interactive_push', '.bat'].join(''),
]);
const suspiciousMarkers = [
    ['node', ' ', 'api', '.', 'js'].join(''),
    ['global', '[', "'!", "']"].join(''),
    ['String', '.', 'from', 'CharCode', '('].join(''),
    ['new', ' ', 'Function', '('].join(''),
];
const findings = [];

function addFinding(message) {
    if (!findings.includes(message)) findings.push(message);
}

function shouldIgnore(path) {
    const relativePath = relative(root, path).replaceAll('\\', '/');
    return [...ignoredDirectories].some((directory) =>
        relativePath === directory || relativePath.startsWith(`${directory}/`)
    );
}

function walk(directory) {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        const path = join(directory, entry.name);
        if (shouldIgnore(path)) continue;

        if (entry.isDirectory()) {
            walk(path);
            continue;
        }

        const relativePath = relative(root, path).replaceAll('\\', '/');
        if (suspiciousNames.has(entry.name)) {
            addFinding(`${relativePath}: unexpected executable filename`);
            continue;
        }

        let contents;
        try {
            contents = readFileSync(path, 'utf8');
        } catch {
            continue;
        }

        for (const marker of suspiciousMarkers) {
            if (contents.includes(marker)) addFinding(`${relativePath}: suspicious code marker detected`);
        }

        if ((relativePath === 'postcss.config.js' || relativePath === 'tailwind.config.js') && contents.split('\n').some((line) => line.length > 500)) {
            addFinding(`${relativePath}: unusually obfuscated long line`);
        }
    }
}

const packageJsonPath = join(root, 'package.json');
try {
    const packageJson = JSON.parse(readFileSync(packageJsonPath, 'utf8'));
    const scripts = packageJson.scripts ?? {};

    for (const [name, expected] of Object.entries(expectedBuildScripts)) {
        if (scripts[name] !== expected) addFinding(`package.json: unexpected ${name} script`);
    }

    for (const name of forbiddenLifecycleScripts) {
        if (scripts[name]) addFinding(`package.json: unexpected lifecycle script ${name}`);
    }
} catch (error) {
    addFinding(`package.json: unable to parse (${error.message})`);
}

walk(root);

if (findings.length > 0) {
    console.error('Security audit failed: suspicious files or code markers were found.');
    for (const finding of findings) console.error(`- ${finding}`);
    process.exit(1);
}

console.log('Security audit passed: no known injected scripts or obfuscated build hooks found.');
