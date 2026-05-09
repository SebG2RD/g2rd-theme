function splitTextWords(element) {
  if (!element || element.dataset.g2rdWordsSplit === "1") {
    return;
  }

  if (element.children.length > 0) {
    return;
  }

  const text = (element.textContent || "").trim();
  if (!text) {
    return;
  }

  const words = text.split(/\s+/).filter(Boolean);
  if (words.length <= 1) {
    return;
  }

  const fragment = document.createDocumentFragment();
  words.forEach((word, index) => {
    const span = document.createElement("span");
    span.className = "g2rd-ek-word";
    span.textContent = word;
    fragment.appendChild(span);
    if (index < words.length - 1) {
      fragment.appendChild(document.createTextNode(" "));
    }
  });

  element.textContent = "";
  element.appendChild(fragment);
  element.dataset.g2rdWordsSplit = "1";
}

function getTargets(wrapper) {
  const applyToChildren = wrapper.dataset.applyToChildren === "1";
  if (!applyToChildren) {
    return [wrapper];
  }

  return Array.from(wrapper.children).filter(
    (node) => node.nodeType === 1 && !node.classList.contains("block-list-appender")
  );
}

function activateAnimation(wrapper, targets) {
  const stagger = parseInt(wrapper.dataset.staggerDelay || "120", 10) || 0;
  targets.forEach((target, index) => {
    target.style.setProperty("--g2rd-ek-item-delay", `${index * stagger}ms`);
    target.classList.add("is-inview");
  });
}

function initEffectKit(wrapper) {
  const animationPreset = wrapper.dataset.animationPreset || "none";
  const perspectivePreset = wrapper.dataset.perspectivePreset || "none";
  const hoverPreset = wrapper.dataset.hoverPreset || "none";
  const splitText = wrapper.dataset.splitText === "1";
  const animateOnParentActive = wrapper.dataset.animateOnParentActive === "1";

  const targets = getTargets(wrapper);

  targets.forEach((target) => {
    if (perspectivePreset !== "none") {
      target.classList.add(perspectivePreset);
    }
    if (hoverPreset !== "none") {
      target.classList.add(hoverPreset);
    }
    if (animationPreset !== "none") {
      target.classList.add("g2rd-ek-anim", `g2rd-ek-anim--${animationPreset}`);
    }
    if (splitText) {
      splitTextWords(target);
    }
  });

  if (animationPreset === "none") {
    return;
  }

  if (animateOnParentActive) {
    const activeParent = wrapper.closest(".active");
    if (activeParent) {
      activateAnimation(wrapper, targets);
      return;
    }

    const observer = new MutationObserver(() => {
      const found = wrapper.closest(".active");
      if (found) {
        activateAnimation(wrapper, targets);
        observer.disconnect();
      }
    });
    observer.observe(document.body, { attributes: true, subtree: true });
    return;
  }

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          activateAnimation(wrapper, targets);
          io.disconnect();
        }
      });
    },
    { threshold: 0.2 }
  );

  io.observe(wrapper);
}

function boot() {
  const wrappers = document.querySelectorAll('.g2rd-effect-kits[data-g2rd-ek="1"]');
  wrappers.forEach((wrapper) => initEffectKit(wrapper));
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
