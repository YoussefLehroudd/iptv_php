'use strict';

document.addEventListener('DOMContentLoaded', () => {
  initSliders();
  initAnimations();
  initOfferModal();
  initFaq();
  initMobileMenu();
  initProviderCarousel();
  initFlashMessages();
});

function initSliders() {
  const sliderStates = {};
  const autoConfigs = {
    hero: 7000,
    movies: 5000,
    sports: 5000,
    testimonials: 6000,
  };

  const findVideoToggle = (videoEl) =>
    videoEl?.closest('.video-frame')?.querySelector('[data-video-toggle]');

  const updateToggleState = (button, video) => {
    if (!button || !video) return;
    const isUnmuted = !video.muted;
    button.setAttribute('aria-pressed', String(isUnmuted));
    button.title = isUnmuted ? 'Couper le son' : 'Activer le son';
    const label = button.querySelector('.sr-only');
    if (label) label.textContent = isUnmuted ? 'Couper le son' : 'Activer le son';
  };

  const clearAutoAdvance = (state) => {
    if (state.timer) {
      clearTimeout(state.timer);
      state.timer = null;
    }
    if (state.videoEl && state.videoHandler) {
      const currentVideo = state.videoEl;
      currentVideo.removeEventListener('ended', state.videoHandler);
      currentVideo.pause?.();
      currentVideo.muted = true;
      updateToggleState(findVideoToggle(currentVideo), currentVideo);
      state.videoEl = null;
      state.videoHandler = null;
    }
  };

  const applyVisibleCount = (state) => {
    if (state.mode !== 'multi') return;
    let visible = state.visibleBase;
    if (visible > 1) {
      if (window.matchMedia('(max-width: 640px)').matches) {
        visible = 1;
      } else if (window.matchMedia('(max-width: 960px)').matches) {
        visible = Math.min(2, state.visibleBase);
      } else if (window.matchMedia('(max-width: 1280px)').matches) {
        visible = Math.min(3, state.visibleBase);
      }
    }
    if (visible !== state.visible) {
      state.visible = visible;
      state.current = Math.min(state.current, Math.max(state.slides.length - visible, 0));
    }
    state.slider.style.setProperty('--visible', String(visible));
  };

  const cloneSlides = (state) => {
    if (!state.slider.hasAttribute('data-infinite') || state.cloned) return;
    state.slides.forEach((slide) => {
      const clone = slide.cloneNode(true);
      clone.setAttribute('aria-hidden', 'true');
      state.track.appendChild(clone);
    });
    state.slides = Array.from(state.track.querySelectorAll('.slide'));
    state.cloned = true;
  };

  const measureMulti = (state) => {
    if (state.mode !== 'multi') return;
    cloneSlides(state);
    applyVisibleCount(state);
    const first = state.slides[0];
    if (!first) return;
    const firstRect = first.getBoundingClientRect();
    if (!firstRect.width) return;
    let step = firstRect.width;
    if (state.slides[1]) {
      const secondRect = state.slides[1].getBoundingClientRect();
      step = secondRect.left - firstRect.left;
    } else {
      const styles = window.getComputedStyle(state.track);
      const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
      step += gap;
    }
    state.step = step;
    state.track.style.transform = `translateX(${-(state.current * step)}px)`;
  };

  const updateMultiTransform = (state) => {
    if (state.mode !== 'multi') return;
    if (!state.step) {
      measureMulti(state);
      return;
    }
    state.track.style.transform = `translateX(${-(state.current * state.step)}px)`;
  };

  const updateSlides = (state) => {
    if (!state) return;
    if (state.mode === 'multi') {
      updateMultiTransform(state);
      return;
    }
    state.slides.forEach((slide, idx) => {
      const isActive = idx === state.current;
      slide.classList.toggle('active', isActive);
      const video = slide.querySelector('video');
      if (video) {
        if (!isActive) {
          video.pause?.();
          video.currentTime = 0;
          video.muted = true;
          video.loop = false;
        } else {
          video.pause?.();
          video.currentTime = 0;
          video.muted = true;
          video.loop = false;
          const playPromise = video.play?.();
          if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(() => {});
          }
        }
        updateToggleState(findVideoToggle(video), video);
      }
    });
  };

  const queueNext = (id) => {
    const state = sliderStates[id];
    if (!state || !state.autoDelay) return;
    const active = state.slides[state.current];
    if (!active) return;

    const video = active.querySelector('video');
    if (video) {
      // Wait for the video to finish before advancing.
      const handler = () => {
        if (!state) return;
        state.videoEl = null;
        state.videoHandler = null;
        state.timer = setTimeout(() => changeSlide(id, 1), 0);
        video.removeEventListener('ended', handler);
      };
      state.videoEl = video;
      state.videoHandler = handler;
      video.addEventListener('ended', handler);
      const playPromise = video.play?.();
      if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch(() => {});
      }
    } else {
      state.timer = setTimeout(() => changeSlide(id, 1), state.autoDelay);
    }
  };

  const changeSlide = (id, direction) => {
    const state = sliderStates[id];
    if (!state) return;
    clearAutoAdvance(state);
    if (state.mode === 'multi') {
      applyVisibleCount(state);
      cloneSlides(state);
      const limit = Math.max(state.slides.length - state.visible, 0);
      if (limit <= 0) {
        state.current = 0;
        updateSlides(state);
        return;
      }
      state.current += direction;
      if (state.slider.hasAttribute('data-infinite')) {
        if (state.current < 0) {
          state.current = limit;
        } else if (state.current > limit) {
          state.current = 0;
        }
      } else {
        if (state.current > limit) state.current = 0;
        if (state.current < 0) state.current = limit;
      }
      updateSlides(state);
      queueNext(id);
      return;
    }
    const { slides } = state;
    state.current = (state.current + direction + slides.length) % slides.length;
    updateSlides(state);
    queueNext(id);
  };

  document.querySelectorAll('[data-slider]').forEach((slider, index) => {
    const track = slider.querySelector('.slider-track') || slider;
    const slides = Array.from(track.querySelectorAll('.slide'));
    if (!slides.length) return;
    const id = slider.dataset.slider || `slider-${index}`;
    const visibleBase = parseInt(slider.dataset.visible || '1', 10);
    const mode = visibleBase > 1 ? 'multi' : 'single';
    slider.dataset.slider = id;
    sliderStates[id] = {
      slider,
      track,
      slides,
      current: 0,
      autoDelay: autoConfigs[id] || null,
      timer: null,
      videoEl: null,
      videoHandler: null,
      mode,
      visibleBase,
      visible: mode === 'multi' ? visibleBase : 1,
      step: 0,
      cloned: false,
    };
    if (mode === 'multi') {
      applyVisibleCount(sliderStates[id]);
      slides.forEach((slide) => slide.classList.add('active'));
      requestAnimationFrame(() => measureMulti(sliderStates[id]));
    } else {
      slides[0].classList.add('active');
    }
    if (sliderStates[id].autoDelay) {
      queueNext(id);
    }
  });

  document.querySelectorAll('[data-slider-target]').forEach((button) => {
    const id = button.dataset.sliderTarget;
    const direction = button.dataset.direction === 'prev' ? -1 : 1;
    button.addEventListener('click', () => changeSlide(id, direction));
  });

  document.querySelectorAll('[data-video-toggle]').forEach((button) => {
    const frame = button.closest('.video-frame');
    const video = frame?.querySelector('video');
    if (!video) {
      button.remove();
      return;
    }
    updateToggleState(button, video);
    button.addEventListener('click', () => {
      const willUnmute = video.muted;
      video.muted = !willUnmute;
      if (willUnmute) {
        const playPromise = video.play?.();
        if (playPromise && typeof playPromise.catch === 'function') {
          playPromise.catch(() => {});
        }
      }
      updateToggleState(button, video);
    });
  });

  window.addEventListener('resize', () => {
    Object.values(sliderStates).forEach((state) => {
      if (state.mode === 'multi') {
        state.step = 0;
        measureMulti(state);
      }
    });
  });
}

function initAnimations() {
  const animated = document.querySelectorAll('[data-animate]');
  if (!animated.length) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.2 });
  animated.forEach((section) => observer.observe(section));
}

function initOfferModal() {
  const modal = document.getElementById('offerModal');
  if (!modal) return;
  const titleEl = modal.querySelector('#modalTitle');
  const durationEl = modal.querySelector('.modal-duration');
  const descriptionEl = modal.querySelector('.modal-description');
  const featuresEl = modal.querySelector('.modal-features');
  const ctaEl = modal.querySelector('#modalCta');
  const closeBtn = modal.querySelector('.modal-close');

  const openModal = (data, whatsappLink) => {
    titleEl.textContent = data.name || 'Offre IPTV';
    durationEl.textContent = data.duration || '';
    descriptionEl.textContent = data.description || '';
    featuresEl.innerHTML = '';
    (data.features || '')
      .split(/\r?\n/)
      .map((feature) => feature.trim())
      .filter(Boolean)
      .forEach((feature) => {
        const li = document.createElement('li');
        li.textContent = feature;
        featuresEl.appendChild(li);
      });
    ctaEl.href = whatsappLink;
    modal.hidden = false;
    document.body.classList.add('modal-open');
  };

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove('modal-open');
  };

  closeBtn?.addEventListener('click', closeModal);
  modal.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });

  document.querySelectorAll('[data-offer]').forEach((button) => {
    button.addEventListener('click', () => {
      try {
        const offerData = JSON.parse(button.dataset.offer || '{}');
        const link = button.dataset.whatsapp || '#';
        openModal(offerData, link);
      } catch (error) {
        console.error('Invalid offer payload', error);
      }
    });
  });
}

function initFaq() {
  document.querySelectorAll('.faq-question').forEach((button) => {
    button.addEventListener('click', () => {
      const id = button.dataset.faq;
      const panel = document.querySelector(`[data-faq-panel="${id}"]`);
      if (!panel) return;
      panel.classList.toggle('open');
    });
  });
}

function initMobileMenu() {
  const toggle = document.querySelector('[data-menu-toggle]');
  const panel = document.querySelector('[data-menu-panel]');
  const backdrop = document.querySelector('[data-menu-backdrop]');
  if (!toggle || !panel || !backdrop) return;

  const mq = window.matchMedia('(min-width: 1025px)');
  let isOpen = false;

  const applyState = () => {
    if (mq.matches) {
      isOpen = false;
      toggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('menu-open');
      panel.removeAttribute('aria-hidden');
      backdrop.hidden = true;
      return;
    }
    document.body.classList.toggle('menu-open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    panel.setAttribute('aria-hidden', String(!isOpen));
    backdrop.hidden = !isOpen;
  };

  const setState = (nextState) => {
    isOpen = nextState;
    applyState();
  };

  toggle.addEventListener('click', () => {
    if (mq.matches) return;
    setState(!isOpen);
  });

  backdrop.addEventListener('click', () => setState(false));

  panel.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setState(false));
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen) setState(false);
  });

  const handleChange = () => applyState();

  if (typeof mq.addEventListener === 'function') {
    mq.addEventListener('change', handleChange);
  } else if (typeof mq.addListener === 'function') {
    mq.addListener(handleChange);
  }

  applyState();
}

function initProviderCarousel() {
  const carousel = document.querySelector('[data-provider-carousel]');
  if (!carousel) return;
  const track = carousel.querySelector('[data-provider-track]');
  const navButtons = carousel.querySelectorAll('[data-provider-nav]');
  if (!track) return;
  const items = Array.from(track.children);
  const originalCount = items.length;
  const getVisibleCount = () => {
    const width = window.innerWidth;
    if (width < 560) return 1;
    if (width < 960) return Math.min(2, originalCount);
    return Math.min(3, originalCount);
  };
  if (originalCount <= getVisibleCount()) {
    carousel.classList.add('provider-carousel--static');
    return;
  }
  items.forEach((item) => track.appendChild(item.cloneNode(true)));
  let index = 0;
  let itemWidth = 0;
  let timer;
  let isSliding = false;
  let fallbackId;

  const computeWidth = () => {
    const first = track.querySelector('.provider-logo');
    if (!first) return 0;
    const trackStyle = window.getComputedStyle(track);
    const gap = parseFloat(trackStyle.columnGap || trackStyle.gap || '0');
    return first.getBoundingClientRect().width + gap;
  };

  const applyTransform = (instant = false) => {
    if (!itemWidth) itemWidth = computeWidth();
    const value = `translate3d(${-index * itemWidth}px, 0, 0)`;
    if (instant) {
      track.style.transition = 'none';
      track.style.transform = value;
      // force reflow before restoring transition
      track.getBoundingClientRect();
      track.style.transition = '';
    } else {
      track.style.transform = value;
    }
  };

  const normalizeIndex = () => {
    if (index >= originalCount) {
      index -= originalCount;
      applyTransform(true);
    } else if (index < 0) {
      index += originalCount;
      applyTransform(true);
    }
    isSliding = false;
    schedule();
  };

  const handleTransitionEnd = () => {
    track.removeEventListener('transitionend', handleTransitionEnd);
    clearTimeout(fallbackId);
    normalizeIndex();
  };

  const slide = (step) => {
    if (isSliding) return;
    isSliding = true;
    clearTimeout(timer);
    itemWidth = computeWidth();
    index += step;
    track.addEventListener('transitionend', handleTransitionEnd);
    clearTimeout(fallbackId);
    fallbackId = setTimeout(handleTransitionEnd, 800);
    applyTransform();
  };

  const schedule = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      slide(getVisibleCount());
    }, 4500);
  };

  navButtons.forEach((button) => {
    const direction = button.dataset.providerNav === 'prev' ? -1 : 1;
    button.addEventListener('click', () => slide(direction));
  });

  window.addEventListener('resize', () => {
    itemWidth = computeWidth();
    applyTransform(true);
  });

  itemWidth = computeWidth();
  applyTransform(true);
  schedule();
}
function initFlashMessages() {
  const flashes = document.querySelectorAll('[data-flash]');
  if (!flashes.length) {
    clearContactParam();
    return;
  }
  flashes.forEach((flash) => {
    setTimeout(() => {
      flash.classList.add('fade-out');
    }, 3000);
    setTimeout(() => {
      flash.remove();
      clearContactParam();
    }, 3600);
  });

  function clearContactParam() {
    const url = new URL(window.location.href);
    if (url.searchParams.has('contact')) {
      url.searchParams.delete('contact');
      history.replaceState({}, document.title, url.pathname + (url.search ? `?${url.searchParams.toString()}` : '') + url.hash);
    }
  }
}
