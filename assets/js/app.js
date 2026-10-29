// Sticky header scroll class
(function () {
    var header = document.getElementById('site-shell-header');
    if (!header) return;
    var onScroll = function () {
        header.classList.toggle('is-scrolled', window.scrollY > 50);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}());

document.addEventListener("DOMContentLoaded", () => {
  /* Reduced motion preference */
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const body = document.body;

  const onScroll = () => {
    if (window.scrollY > 8) {
      body.classList.add("is-scrolled");
    } else {
      body.classList.remove("is-scrolled");
    }
  };

  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  /* ── Mobile menu overlay ──────────────────────────────── */
  const navToggle = document.querySelector('.site-shell-toggle');
  const navCollapse = document.getElementById('siteNav');
  const navBackdrop = document.getElementById('siteNavBackdrop');

  if (navToggle && navCollapse && navBackdrop) {
    const openMenu = () => {
      body.classList.add('menu-open');
      navBackdrop.classList.add('is-open');
      navToggle.setAttribute('aria-expanded', 'true');
    };
    const closeMenu = () => {
      body.classList.remove('menu-open');
      navBackdrop.classList.remove('is-open');
      navToggle.setAttribute('aria-expanded', 'false');
    };

    navToggle.addEventListener('click', () => {
      const isOpen = navCollapse.classList.contains('show');
      if (isOpen) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    navBackdrop.addEventListener('click', () => {
      closeMenu();
      if (window.bootstrap && window.bootstrap.Collapse) {
        const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
        if (bsCollapse) bsCollapse.hide();
      }
    });

    navCollapse.querySelectorAll('.nav-link, .site-shell-plan-btn').forEach((link) => {
      link.addEventListener('click', () => {
        closeMenu();
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navCollapse.classList.contains('show')) {
        closeMenu();
        if (window.bootstrap && window.bootstrap.Collapse) {
          const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
          if (bsCollapse) bsCollapse.hide();
        }
      }
    });
  }

  const autoRevealSelectors = [
    "section .card",
    "section .mini-feature",
    "section .rounded-4.border",
    "section .p-4.border",
    "section .p-4.bg-white",
    "section .p-4.p-lg-5",
    "section img.img-fluid",
    "section h1",
    "section .lead",
    "section .btn",
    "section .badge-soft"
  ];

  const revealTargets = [];
  autoRevealSelectors.forEach((selector) => {
    document.querySelectorAll(selector).forEach((el, index) => {
      if (!el.classList.contains("reveal")) {
        el.classList.add("reveal");
        if (index % 3 === 1) el.classList.add("reveal-delay-1");
        if (index % 3 === 2) el.classList.add("reveal-delay-2");
        revealTargets.push(el);
      }
    });
  });

  if (!prefersReducedMotion && "IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.12,
        rootMargin: "0px 0px -40px 0px"
      }
    );

    revealTargets.forEach((el) => observer.observe(el));
  } else {
    revealTargets.forEach((el) => el.classList.add("is-visible"));
  }

  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener("click", (e) => {
      const href = link.getAttribute("href");
      if (!href || href === "#") return;

      const target = document.querySelector(href);
      if (!target) return;

      e.preventDefault();
      target.scrollIntoView({ behavior: prefersReducedMotion ? "auto" : "smooth", block: "start" });

      const mobileToc = link.closest("details.toc-mobile");
      if (mobileToc) {
        mobileToc.open = false;
      }
    });
  });

  // Fallback for environments where Bootstrap JS is unavailable (e.g., CDN blocked locally).
  if (!(window.bootstrap && window.bootstrap.Collapse) && !window.__ictAccordionFallbackInit) {
    window.__ictAccordionFallbackInit = true;
    const buttons = Array.from(
      document.querySelectorAll('.accordion-button[data-bs-toggle="collapse"][data-bs-target]')
    );

    const getButtonForPanel = (panelId) =>
      buttons.find((btn) => (btn.getAttribute("data-bs-target") || "") === `#${panelId}`);

    const setPanelState = (panel, expanded) => {
      panel.classList.toggle("show", expanded);
      const btn = getButtonForPanel(panel.id);
      if (!btn) return;
      btn.classList.toggle("collapsed", !expanded);
      btn.setAttribute("aria-expanded", expanded ? "true" : "false");
    };

    buttons.forEach((button) => {
      button.addEventListener("click", (event) => {
        event.preventDefault();
        const targetSelector = button.getAttribute("data-bs-target");
        if (!targetSelector) return;

        const panel = document.querySelector(targetSelector);
        if (!panel) return;

        const willExpand = !panel.classList.contains("show");
        const parentSelector = panel.getAttribute("data-bs-parent");

        if (parentSelector) {
          const parent = document.querySelector(parentSelector);
          if (parent) {
            parent.querySelectorAll(".accordion-collapse.show").forEach((openPanel) => {
              if (openPanel !== panel) {
                setPanelState(openPanel, false);
              }
            });
          }
        }

        setPanelState(panel, willExpand);
      });
    });
  }

  // Global image lightbox for public pages (excluding hero sections).
  const isLightboxCandidate = (img) => {
    if (!(img instanceof HTMLImageElement)) return false;
    if (!img.closest("main")) return false;
    if (img.closest(".home-hero-banner, .page-hero-banner")) return false;
    if (img.closest(".image-lightbox__dialog")) return false;
    if (img.closest("[data-lightbox-ignore]")) return false;
    if (!img.currentSrc && !img.getAttribute("src")) return false;
    return true;
  };

  const lightbox = document.createElement("div");
  lightbox.className = "image-lightbox";
  lightbox.setAttribute("aria-hidden", "true");
  lightbox.innerHTML = `
    <div class="image-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Image preview">
      <button type="button" class="image-lightbox__close" aria-label="Close image preview">x</button>
      <img class="image-lightbox__img" alt="">
    </div>
  `;
  document.body.appendChild(lightbox);

  const lightboxImage = lightbox.querySelector(".image-lightbox__img");
  const lightboxClose = lightbox.querySelector(".image-lightbox__close");

  const openLightbox = (img) => {
    const fullSrc = img.currentSrc || img.getAttribute("src");
    if (!fullSrc) return;

    lightboxImage.setAttribute("src", fullSrc);
    lightboxImage.setAttribute("alt", img.getAttribute("alt") || "Image preview");
    lightbox.classList.add("is-open");
    lightbox.setAttribute("aria-hidden", "false");
    document.body.classList.add("lightbox-open");
  };

  const closeLightbox = () => {
    if (!lightbox.classList.contains("is-open")) return;
    lightbox.classList.remove("is-open");
    lightbox.setAttribute("aria-hidden", "true");
    document.body.classList.remove("lightbox-open");
    lightboxImage.removeAttribute("src");
  };

  document.querySelectorAll("main img").forEach((img) => {
    if (isLightboxCandidate(img)) {
      img.classList.add("ict-lightbox-target");
    }
  });

  document.addEventListener("click", (event) => {
    const clickedImage = event.target.closest("img");
    if (!clickedImage || !isLightboxCandidate(clickedImage)) return;

    event.preventDefault();
    event.stopPropagation();
    openLightbox(clickedImage);
  });

  lightbox.addEventListener("click", (event) => {
    if (event.target === lightbox) {
      closeLightbox();
    }
  });

  lightboxClose.addEventListener("click", closeLightbox);

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeLightbox();
    }
  });

  document.querySelectorAll("[data-itinerary-slider]").forEach((slider) => {
    const rail = slider.querySelector("[data-itinerary-rail]");
    const prev = slider.querySelector("[data-itinerary-prev]");
    const next = slider.querySelector("[data-itinerary-next]");
    if (!rail || !prev || !next) return;

    const slides = Array.from(rail.querySelectorAll(".home-itinerary-slide"));
    if (slides.length === 0) return;

    const AUTO_DELAY_MS = 5000;
    let autoTimer = null;

    const getMaxScroll = () => Math.max(0, rail.scrollWidth - rail.clientWidth);

    const getSlidePositions = () => {
      const maxScroll = getMaxScroll();
      return slides.map((slide) => Math.min(slide.offsetLeft, maxScroll));
    };

    const getCurrentIndex = (positions) => {
      const current = rail.scrollLeft;
      let nearest = 0;
      let minDistance = Number.POSITIVE_INFINITY;

      positions.forEach((position, index) => {
        const distance = Math.abs(position - current);
        if (distance < minDistance) {
          minDistance = distance;
          nearest = index;
        }
      });

      return nearest;
    };

    const moveBy = (direction, wrap = false) => {
      const positions = getSlidePositions();
      if (positions.length < 2) return;

      const currentIndex = getCurrentIndex(positions);
      let targetIndex = currentIndex + direction;

      if (wrap) {
        if (targetIndex >= positions.length) targetIndex = 0;
        if (targetIndex < 0) targetIndex = positions.length - 1;
      } else {
        targetIndex = Math.max(0, Math.min(targetIndex, positions.length - 1));
      }

      rail.scrollTo({ left: positions[targetIndex], behavior: prefersReducedMotion ? "auto" : "smooth" });
    };

    const updateNav = () => {
      const positions = getSlidePositions();
      const maxScroll = getMaxScroll();
      const canSlide = positions.length > 1 && maxScroll > 2;

      if (!canSlide) {
        prev.disabled = true;
        next.disabled = true;
        return;
      }

      const index = getCurrentIndex(positions);
      prev.disabled = index <= 0;
      next.disabled = index >= positions.length - 1 || rail.scrollLeft >= (maxScroll - 2);
    };

    const stopAuto = () => {
      if (autoTimer) {
        window.clearInterval(autoTimer);
        autoTimer = null;
      }
    };

    const startAuto = () => {
      stopAuto();
      if (slides.length < 2) return;
      autoTimer = window.setInterval(() => {
        moveBy(1, true);
      }, AUTO_DELAY_MS);
    };

    prev.addEventListener("click", () => {
      moveBy(-1);
      startAuto();
    });

    next.addEventListener("click", () => {
      moveBy(1);
      startAuto();
    });

    slider.addEventListener("mouseenter", stopAuto);
    slider.addEventListener("mouseleave", startAuto);
    slider.addEventListener("focusin", stopAuto);
    slider.addEventListener("focusout", (event) => {
      if (!slider.contains(event.relatedTarget)) {
        startAuto();
      }
    });
    slider.addEventListener("touchstart", stopAuto, { passive: true });
    slider.addEventListener("touchend", startAuto, { passive: true });

    rail.addEventListener("scroll", updateNav, { passive: true });
    window.addEventListener("resize", () => {
      updateNav();
    });
    updateNav();
    startAuto();
  });

  /* --------------------------------------------------------
     MOBILE MENU — Body scroll lock + backdrop close
     -------------------------------------------------------- */
  const mobileCollapse = document.getElementById("siteNav");
  if (mobileCollapse && window.matchMedia("(max-width: 991.98px)").matches) {
    let menuOpen = false;

    const lockScroll = () => {
      menuOpen = true;
      body.classList.add("menu-open");
      body.style.overflow = "hidden";
    };

    const unlockScroll = () => {
      menuOpen = false;
      body.classList.remove("menu-open");
      body.style.overflow = "";
    };

    if (window.bootstrap && window.bootstrap.Collapse) {
      const bsCollapse = new bootstrap.Collapse(mobileCollapse, { toggle: false });

      mobileCollapse.addEventListener("show.bs.collapse", () => lockScroll());
      mobileCollapse.addEventListener("hide.bs.collapse", () => unlockScroll());
      mobileCollapse.addEventListener("hidden.bs.collapse", () => unlockScroll());

      mobileCollapse.addEventListener("click", (e) => {
        if (e.target === mobileCollapse) {
          bsCollapse.hide();
        }
      });

      mobileCollapse.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
          bsCollapse.hide();
        });
      });
    } else {
      const toggle = document.querySelector(".site-shell-toggle");
      if (toggle) {
        toggle.addEventListener("click", () => {
          if (mobileCollapse.classList.contains("show")) {
            mobileCollapse.classList.remove("show");
            unlockScroll();
          } else {
            mobileCollapse.classList.add("show");
            lockScroll();
          }
        });
      }

      mobileCollapse.addEventListener("click", (e) => {
        if (e.target === mobileCollapse) {
          mobileCollapse.classList.remove("show");
          unlockScroll();
        }
      });

      mobileCollapse.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
          mobileCollapse.classList.remove("show");
          unlockScroll();
        });
      });
    }
  }

  /* --------------------------------------------------------
     BOTTOM NAV — Auto-hide on scroll
     -------------------------------------------------------- */
  const stickyCta = document.querySelector(".sticky-cta");
  if (stickyCta) {
    let lastScrollY = window.scrollY;
    let ticking = false;
    const scrollThreshold = 300;

    const updateBottomNav = () => {
      const currentY = window.scrollY;
      const scrollDiff = currentY - lastScrollY;

      if (currentY < scrollThreshold) {
        stickyCta.classList.remove("hide-bar");
        stickyCta.classList.add("show-bar");
      } else if (scrollDiff > 15) {
        stickyCta.classList.add("hide-bar");
        stickyCta.classList.remove("show-bar");
      } else if (scrollDiff < -15) {
        stickyCta.classList.remove("hide-bar");
        stickyCta.classList.add("show-bar");
      }

      lastScrollY = currentY;
      ticking = false;
    };

    window.addEventListener("scroll", () => {
      if (!ticking) {
        requestAnimationFrame(updateBottomNav);
        ticking = true;
      }
    }, { passive: true });

    stickyCta.classList.add("show-bar");
  }

  /* --------------------------------------------------------
     BACK TO TOP BUTTON
     -------------------------------------------------------- */
  const backToTop = document.getElementById("backToTop");
  if (backToTop) {
    const backToTopThreshold = 300;

    const toggleBackToTop = () => {
      if (window.scrollY > backToTopThreshold) {
        backToTop.classList.add("is-visible");
      } else {
        backToTop.classList.remove("is-visible");
      }
    };

    window.addEventListener("scroll", toggleBackToTop, { passive: true });
    toggleBackToTop();

    backToTop.addEventListener("click", () => {
      window.scrollTo({ top: 0, behavior: prefersReducedMotion ? "auto" : "smooth" });
    });
  }

  /* --------------------------------------------------------
     SCROLL SPY DOTS — Mobile section navigation
     -------------------------------------------------------- */
  const scrollSpyTrack = document.getElementById("scrollSpyTrack");
  if (scrollSpyTrack && window.matchMedia("(max-width: 767.98px)").matches) {
    const sections = document.querySelectorAll("main > section[id], main section[id]");
    if (sections.length > 1) {
      let dots = [];

      sections.forEach((section) => {
        const id = section.getAttribute("id");
        if (!id) return;

        const title = section.querySelector("h1, h2, h3, .section-heading");
        const label = title ? title.textContent.trim().slice(0, 30) : id.replace(/-/g, " ");

        const dot = document.createElement("button");
        dot.className = "scroll-spy-dot";
        dot.setAttribute("aria-label", "Scroll to " + label);
        dot.setAttribute("type", "button");

        const tooltip = document.createElement("span");
        tooltip.className = "scroll-spy-tooltip";
        tooltip.textContent = label;
        dot.appendChild(tooltip);

        dot.addEventListener("click", () => {
          section.scrollIntoView({ behavior: prefersReducedMotion ? "auto" : "smooth", block: "start" });
        });

        scrollSpyTrack.appendChild(dot);
        dots.push({ dot, section });
      });

      if (dots.length > 0 && "IntersectionObserver" in window) {
        const activeDotObserver = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              const match = dots.find((d) => d.section === entry.target);
              if (match) {
                if (entry.isIntersecting) {
                  match.dot.classList.add("active");
                } else {
                  match.dot.classList.remove("active");
                }
              }
            });
          },
          { threshold: 0.3, rootMargin: "-10% 0px -10% 0px" }
        );

        dots.forEach((d) => activeDotObserver.observe(d.section));
      }
    }
  }

  /* --------------------------------------------------------
     TITLE SCROLL ANIMATIONS
     -------------------------------------------------------- */
  const titleElements = document.querySelectorAll(
    "main h1, main h2, main .section-heading, main .lead"
  );
  if (titleElements.length > 0 && !prefersReducedMotion && "IntersectionObserver" in window) {
    titleElements.forEach((el) => {
      el.classList.add("title-animate");
    });

    const titleObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-title-visible");
          } else {
            entry.target.classList.remove("is-title-visible");
          }
        });
      },
      { threshold: 0.3 }
    );

    titleElements.forEach((el) => titleObserver.observe(el));
  } else if (titleElements.length > 0) {
    titleElements.forEach((el) => {
      el.classList.add("is-title-visible");
    });
  }
});

/* Image error fallback */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('img').forEach(function(img) {
        img.addEventListener('error', function() {
            this.style.display = 'none';
        });
    });
});
