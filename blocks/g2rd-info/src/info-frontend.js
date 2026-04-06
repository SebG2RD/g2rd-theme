// G2RD Info Block Frontend Script
document.addEventListener("DOMContentLoaded", function () {
  const infoBlocks = document.querySelectorAll(".g2rd-info-block");

  infoBlocks.forEach((block) => {
    // Add hover effect classes based on data attributes
    const hoverEffect = block.getAttribute("data-hover-effect");
    if (hoverEffect && hoverEffect !== "none") {
      block.classList.add(`hover-${hoverEffect}`);
    }

    // Add smooth transitions
    block.style.transition = "all 0.3s ease";

    // Add focus management for accessibility
    block.addEventListener("focusin", function () {
      this.style.outline = "2px solid #007cba";
      this.style.outlineOffset = "2px";
    });

    block.addEventListener("focusout", function () {
      this.style.outline = "";
      this.style.outlineOffset = "";
    });
  });
});
