(function () {
  var toggle = document.getElementById("navToggle");
  var nav = document.getElementById("nav");
  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      nav.classList.toggle("open");
    });
    nav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        nav.classList.remove("open");
      });
    });
  }
})();

(function () {
  var header = document.querySelector(".site-header");
  if (header) {
    var onScroll = function () {
      header.classList.toggle("scrolled", window.scrollY > 8);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  if (!("IntersectionObserver" in window)) return;
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      entry.target.classList.add("in");
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });

  // Elements already on screen at load are left alone so they never flash hidden.
  var fold = window.innerHeight * 0.92;

  document.querySelectorAll(".section-head, .card, .video-frame, .pdf-wrap").forEach(function (el) {
    if (el.getBoundingClientRect().top < fold) return;

    var index = 0;
    var prev = el.previousElementSibling;
    while (prev) {
      if (prev.classList.contains("reveal")) index++;
      prev = prev.previousElementSibling;
    }
    el.style.setProperty("--d", Math.min(index, 4) * 90 + "ms");
    el.classList.add("reveal");

    el.addEventListener("animationend", function done(e) {
      if (e.target !== el) return;
      el.classList.remove("reveal", "in");
      el.style.removeProperty("--d");
      el.removeEventListener("animationend", done);
    });

    observer.observe(el);
  });
})();
