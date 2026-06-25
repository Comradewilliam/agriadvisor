const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function copyFile(src, dest) {
  if (!fs.existsSync(src)) {
    console.warn('Missing:', src);
    return false;
  }
  ensureDir(path.dirname(dest));
  fs.copyFileSync(src, dest);
  return true;
}

// Chart.js
const jsDir = path.join(root, 'public', 'assets', 'js');
ensureDir(jsDir);
if (copyFile(
  path.join(root, 'node_modules', 'chart.js', 'dist', 'chart.umd.min.js'),
  path.join(jsDir, 'chart.umd.min.js')
)) {
  console.log('Copied chart.js');
}

// Inter fonts
const interFiles = [
  ['inter-latin-400-normal.woff2', 'inter-latin-400-normal.woff2'],
  ['inter-latin-500-normal.woff2', 'inter-latin-500-normal.woff2'],
  ['inter-latin-600-normal.woff2', 'inter-latin-600-normal.woff2'],
  ['inter-latin-700-normal.woff2', 'inter-latin-700-normal.woff2'],
  ['inter-latin-800-normal.woff2', 'inter-latin-800-normal.woff2'],
];
const interSrc = path.join(root, 'node_modules', '@fontsource', 'inter', 'files');
const interDest = path.join(root, 'public', 'assets', 'fonts', 'inter');
for (const [srcName, destName] of interFiles) {
  if (copyFile(path.join(interSrc, srcName), path.join(interDest, destName))) {
    console.log('Copied', destName);
  }
}

// Material Symbols
const msSrc = path.join(root, 'node_modules', '@fontsource', 'material-symbols-outlined', 'files');
const msDest = path.join(root, 'public', 'assets', 'fonts', 'material-symbols');
if (copyFile(
  path.join(msSrc, 'material-symbols-outlined-latin-400-normal.woff2'),
  path.join(msDest, 'material-symbols-outlined-latin-400-normal.woff2')
)) {
  console.log('Copied Material Symbols font');
}

console.log('Asset copy complete.');
