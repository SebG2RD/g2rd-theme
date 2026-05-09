import { __ } from "@wordpress/i18n";
import {
  InnerBlocks,
  InspectorControls,
  useBlockProps,
} from "@wordpress/block-editor";
import {
  PanelBody,
  SelectControl,
  ToggleControl,
  RangeControl,
  Notice,
} from "@wordpress/components";

const PERSPECTIVE_OPTIONS = [
  { label: __("Aucun", "g2rd"), value: "none" },
  { label: "gs_style_perspective", value: "gs_style_perspective" },
  { label: "gs_style_skewed", value: "gs_style_skewed" },
  { label: "gs_style_rotated", value: "gs_style_rotated" },
  { label: "gs_style_stacked", value: "gs_style_stacked" },
  { label: "gs_style_3d_multi", value: "gs_style_3d_multi" },
];

const HOVER_OPTIONS = [
  { label: __("Aucun", "g2rd"), value: "none" },
  { label: "gs-twin-on-hover", value: "gs-twin-on-hover" },
  { label: "gs_scale_on_hover", value: "gs_scale_on_hover" },
  { label: "gs_top_on_hover", value: "gs_top_on_hover" },
  { label: "gs_shadow_on_hover", value: "gs_shadow_on_hover" },
];

const ANIMATION_OPTIONS = [
  { label: __("Aucune", "g2rd"), value: "none" },
  { label: __("Fade In", "g2rd"), value: "fade-in" },
  { label: __("Fade In Up", "g2rd"), value: "fade-up" },
  { label: __("Zoom In", "g2rd"), value: "zoom-in" },
  { label: __("Clip Reveal", "g2rd"), value: "clip-reveal" },
];

/**
 * Données injectées par PHP (class-block-editor-autoload::localizeEffectKitsEditor).
 * Si absent (script chargé hors contexte attendu), on considère la licence OK.
 */
function isEffectKitsLicensed() {
  if (
    typeof window !== "undefined" &&
    window.g2rdEffectKitsEditor &&
    typeof window.g2rdEffectKitsEditor.licensed === "boolean"
  ) {
    return window.g2rdEffectKitsEditor.licensed;
  }
  return true;
}

export default function Edit({ attributes, setAttributes }) {
  const {
    perspectivePreset,
    hoverPreset,
    animationPreset,
    applyToChildren,
    staggerDelay,
    splitTextOnChildren,
    animateOnParentActive,
    minHeight,
  } = attributes;

  const licensed = isEffectKitsLicensed();

  const classNames = [
    "g2rd-effect-kits",
    applyToChildren ? "is-apply-children" : "is-apply-self",
  ].join(" ");

  const style = {
    ...(minHeight > 0 ? { minHeight: `${minHeight}px` } : {}),
  };

  const blockProps = useBlockProps({
    className: classNames,
    style,
    "data-g2rd-ek": "1",
    "data-animation-preset": animationPreset,
    "data-animate-on-parent-active": animateOnParentActive ? "1" : "0",
    "data-apply-to-children": applyToChildren ? "1" : "0",
    "data-split-text": splitTextOnChildren ? "1" : "0",
    "data-stagger-delay": String(staggerDelay),
    "data-perspective-preset": perspectivePreset,
    "data-hover-preset": hoverPreset,
  });

  return (
    <>
      <InspectorControls>
        {!licensed && (
          <PanelBody title={__("Licence", "g2rd")} initialOpen>
            <Notice status="warning" isDismissible={false}>
              {__(
                "Sans licence active, ce bloc n’apparaît pas dans l’inserter. Tu peux quand même ajuster les effets ci-dessous.",
                "g2rd"
              )}
            </Notice>
          </PanelBody>
        )}
        <PanelBody title={__("Effets de perspective", "g2rd")} initialOpen>
          <SelectControl
            label={__("Preset perspective", "g2rd")}
            value={perspectivePreset}
            options={PERSPECTIVE_OPTIONS}
            onChange={(value) => setAttributes({ perspectivePreset: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>

        <PanelBody title={__("Effets hover", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Preset hover", "g2rd")}
            value={hoverPreset}
            options={HOVER_OPTIONS}
            onChange={(value) => setAttributes({ hoverPreset: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>

        <PanelBody title={__("Animation", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Preset animation", "g2rd")}
            value={animationPreset}
            options={ANIMATION_OPTIONS}
            onChange={(value) => setAttributes({ animationPreset: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          <ToggleControl
            label={__("Appliquer les effets aux enfants directs", "g2rd")}
            checked={applyToChildren}
            onChange={(value) => setAttributes({ applyToChildren: value })}
            __nextHasNoMarginBottom
          />

          {animationPreset !== "none" && (
            <>
              <RangeControl
                label={__("Stagger delay (ms)", "g2rd")}
                value={staggerDelay}
                onChange={(value) => setAttributes({ staggerDelay: value || 0 })}
                min={0}
                max={600}
                step={20}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <ToggleControl
                label={__("Split text sur les enfants textuels", "g2rd")}
                checked={splitTextOnChildren}
                onChange={(value) => setAttributes({ splitTextOnChildren: value })}
                help={__(
                  "Découpe les textes simples en mots pour un effet mot par mot.",
                  "g2rd"
                )}
                __nextHasNoMarginBottom
              />
              <ToggleControl
                label={__('Déclencher quand un parent a la classe ".active"', "g2rd")}
                checked={animateOnParentActive}
                onChange={(value) => setAttributes({ animateOnParentActive: value })}
                __nextHasNoMarginBottom
              />
            </>
          )}
        </PanelBody>

        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Hauteur minimale (px)", "g2rd")}
            value={minHeight}
            onChange={(value) => setAttributes({ minHeight: value || 0 })}
            min={0}
            max={1200}
            step={20}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        {!licensed && (
          <Notice status="warning" isDismissible={false}>
            {__(
              "Licence G2RD inactive : ce bloc reste modifiable ici et visible sur le site, mais tu ne peux pas l’ajouter depuis l’inserter tant que la licence n’est pas activée.",
              "g2rd"
            )}
          </Notice>
        )}
        <Notice status="info" isDismissible={false}>
          {__(
            "Place tes blocs à l'intérieur, puis choisis les presets. Les variables --gs-root-* peuvent être surchargées globalement dans ton CSS.",
            "g2rd"
          )}
        </Notice>
        <InnerBlocks renderAppender={InnerBlocks.ButtonBlockAppender} />
      </div>
    </>
  );
}
