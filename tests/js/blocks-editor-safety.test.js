const fs = require("fs");
const path = require("path");

const projectRoot = path.resolve(__dirname, "..", "..");

const blockChecks = [
  { slug: "g2rd-faq", requireRenderPhp: true, expectTransforms: true, expectDeprecated: true },
  { slug: "g2rd-cta-band", requireRenderPhp: false, expectTransforms: false, expectDeprecated: false },
  { slug: "g2rd-hero", requireRenderPhp: false, expectTransforms: false, expectDeprecated: false },
  { slug: "g2rd-countdown", requireRenderPhp: false, expectTransforms: false, expectDeprecated: true },
  { slug: "g2rd-testimonial", requireRenderPhp: false, expectTransforms: false, expectDeprecated: false },
];

function readFileSafe(filePath) {
  if (!fs.existsSync(filePath)) {
    return "";
  }
  return fs.readFileSync(filePath, "utf8");
}

describe("Sécurité éditeur Gutenberg - blocs critiques", () => {
  test.each(blockChecks)("%s: structure bloc valide", ({ slug, requireRenderPhp }) => {
    const blockDir = path.join(projectRoot, "blocks", slug);
    const blockJsonPath = path.join(blockDir, "block.json");

    expect(fs.existsSync(blockJsonPath)).toBe(true);

    const blockJson = JSON.parse(readFileSafe(blockJsonPath));
    expect(blockJson.name).toBe(`g2rd/${slug.replace("g2rd-", "")}`);
    expect(typeof blockJson.title).toBe("string");

    const editPath = path.join(blockDir, "src", "edit.js");
    const savePath = path.join(blockDir, "src", "save.js");
    expect(fs.existsSync(editPath)).toBe(true);
    expect(fs.existsSync(savePath)).toBe(true);

    if (requireRenderPhp) {
      const renderPath = path.join(blockDir, "render.php");
      expect(fs.existsSync(renderPath)).toBe(true);
    }
  });

  test.each(blockChecks)(
    "%s: index.js protège edit/save/deprecations/transforms",
    ({ slug, expectTransforms, expectDeprecated }) => {
      const indexPath = path.join(projectRoot, "blocks", slug, "src", "index.js");
      const source = readFileSafe(indexPath);

      expect(source.length).toBeGreaterThan(0);
      expect(source).toMatch(/registerBlockType/);
      expect(source).toMatch(/edit\s*[:,]/);
      expect(source).toMatch(/save\s*[:,]/);

      if (expectDeprecated) {
        expect(source).toMatch(/deprecated/);
      }

      if (expectTransforms) {
        expect(source).toMatch(/transforms/);
      }
    }
  );
});
