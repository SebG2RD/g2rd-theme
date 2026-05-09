import { InnerBlocks, useBlockProps } from "@wordpress/block-editor";

export default function Save({ attributes }) {
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

  const classNames = [
    "g2rd-effect-kits",
    applyToChildren ? "is-apply-children" : "is-apply-self",
  ].join(" ");

  const style = {
    ...(minHeight > 0 ? { minHeight: `${minHeight}px` } : {}),
  };

  const blockProps = useBlockProps.save({
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
    <div {...blockProps}>
      <InnerBlocks.Content />
    </div>
  );
}
