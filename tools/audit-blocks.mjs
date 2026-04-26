import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const blocksDir = path.join(root, "blocks");
const docsBlocksDir = path.join(root, "docs", "blocks");
const strictMode = process.argv.includes("--strict");

const requiredBlockJsonKeys = ["apiVersion", "name", "title", "category", "attributes", "supports"];
const screenshotNames = ["screenshot.png", "screenshot.jpg", "screenshot.jpeg", "screenshot.webp"];
const requiredDocs = [
  "attributes.md",
  "frontend-demo.md",
  "editor-demo.md",
  "deprecations.md",
  "variations.md",
];

function isDirectory(filePath) {
  try {
    return fs.statSync(filePath).isDirectory();
  } catch {
    return false;
  }
}

function readJson(filePath) {
  try {
    return JSON.parse(fs.readFileSync(filePath, "utf8"));
  } catch {
    return null;
  }
}

function listBlockDirs() {
  if (!isDirectory(blocksDir)) {
    return [];
  }
  return fs
    .readdirSync(blocksDir)
    .map((name) => path.join(blocksDir, name))
    .filter((dirPath) => isDirectory(dirPath) && fs.existsSync(path.join(dirPath, "block.json")));
}

function checkBlock(blockPath) {
  const slug = path.basename(blockPath);
  const errors = [];
  const blockJsonPath = path.join(blockPath, "block.json");
  const readmePath = path.join(blockPath, "README.md");
  const blockJson = readJson(blockJsonPath);

  if (!blockJson) {
    errors.push("block.json invalide (JSON mal formé)");
    return { slug, errors };
  }

  for (const key of requiredBlockJsonKeys) {
    if (blockJson[key] === undefined) {
      errors.push(`block.json: clé manquante "${key}"`);
    }
  }

  if (!fs.existsSync(readmePath)) {
    errors.push("README.md manquant dans le dossier du bloc");
  }

  const hasScreenshot = screenshotNames.some((name) => fs.existsSync(path.join(blockPath, name)));
  if (!hasScreenshot) {
    errors.push("capture manquante (screenshot.png/jpg/jpeg/webp)");
  }

  const blockDocsPath = path.join(docsBlocksDir, slug);
  for (const docFile of requiredDocs) {
    if (!fs.existsSync(path.join(blockDocsPath, docFile))) {
      errors.push(`doc manquante: docs/blocks/${slug}/${docFile}`);
    }
  }

  const indexJsPath = path.join(blockPath, "src", "index.js");
  const hasIndexJs = fs.existsSync(indexJsPath);
  if (hasIndexJs) {
    const source = fs.readFileSync(indexJsPath, "utf8");
    if (!/deprecated/.test(source)) {
      errors.push("stratégie de dépréciation absente dans src/index.js (champ deprecated)");
    }
    if (!/variations/.test(source) && !Array.isArray(blockJson.variations)) {
      errors.push("variations absentes (ni block.json.variations, ni src/index.js)");
    }
  }

  return { slug, errors };
}

const blockDirs = listBlockDirs();
const report = blockDirs.map(checkBlock);
const failing = report.filter((r) => r.errors.length > 0);

console.log(`Audit blocs: ${report.length} bloc(s) analysé(s)`);

if (failing.length === 0) {
  console.log("OK: tous les blocs respectent le standard documentaire et metadata.");
  process.exit(0);
}

console.log(`\n${failing.length} bloc(s) ont des éléments manquants:\n`);
for (const item of failing) {
  console.log(`- ${item.slug}`);
  for (const err of item.errors) {
    console.log(`  - ${err}`);
  }
}

if (strictMode) {
  process.exit(1);
}

console.log(
  "\nMode progressif actif: audit non bloquant. " +
    "Utilisez `npm run audit:blocks:strict` pour rendre l'audit bloquant."
);
process.exit(0);
