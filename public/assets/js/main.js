'use strict';

document.addEventListener('DOMContentLoaded', () => {
  initSliders();
  initAnimations();
  initGreetingRotator();
  initOfferModal();
  initFaq();
  initMobileMenu();
  initProviderCarousel();
  initFlashMessages();
  initScrollTop();
  initMusicPlayer();
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

  const disableMediaDrag = (root) => {
    root.querySelectorAll('img, video').forEach((media) => {
      media.setAttribute('draggable', 'false');
      media.addEventListener('dragstart', (event) => event.preventDefault());
    });
  };

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
      currentVideo.dataset.userUnmuted = 'false';
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
          video.dataset.userUnmuted = 'false';
        } else {
          const userUnmuted = video.dataset.userUnmuted === 'true';
          video.loop = false;
          video.defaultMuted = !userUnmuted;
          if (userUnmuted) {
            video.removeAttribute('muted');
            video.muted = false;
            video.volume = 1;
          } else {
            video.setAttribute('muted', '');
            video.muted = true;
          }
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
    slides.forEach(disableMediaDrag);
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

  const attachDragHandlers = (id) => {
    const state = sliderStates[id];
    if (!state) return;
    const target = state.track;
    if (!target) return;
    target.style.touchAction = 'pan-y';
    let pointerId = null;
    let startX = 0;
    let isDragging = false;
    const threshold = 45;

    const onPointerDown = (event) => {
      if (event.target.closest('[data-video-toggle], .slider-nav, .video-audio-toggle')) {
        return;
      }
      pointerId = event.pointerId;
      startX = event.clientX;
      isDragging = true;
      target.setPointerCapture?.(pointerId);
    };

    const onPointerUp = (event) => {
      if (!isDragging || event.pointerId !== pointerId) return;
      isDragging = false;
      target.releasePointerCapture?.(pointerId);
      const delta = event.clientX - startX;
      pointerId = null;
      if (Math.abs(delta) >= threshold) {
        changeSlide(id, delta > 0 ? -1 : 1);
      }
    };

    const cancelDrag = (event) => {
      if (pointerId !== null && event.pointerId && event.pointerId !== pointerId) return;
      isDragging = false;
      if (pointerId !== null) {
        target.releasePointerCapture?.(pointerId);
        pointerId = null;
      }
    };

    const onPointerMove = (event) => {
      if (!isDragging) return;
      event.preventDefault();
    };

    target.addEventListener('pointerdown', onPointerDown);
    target.addEventListener('pointerup', onPointerUp);
    target.addEventListener('pointerleave', cancelDrag);
    target.addEventListener('pointercancel', cancelDrag);
    target.addEventListener('pointermove', onPointerMove, { passive: false });
  };

  Object.keys(sliderStates).forEach(attachDragHandlers);

  document.querySelectorAll('[data-slider-target]').forEach((button) => {
    const id = button.dataset.sliderTarget;
    const direction = button.dataset.direction === 'prev' ? -1 : 1;
    button.addEventListener('click', () => changeSlide(id, direction));
  });

  const videoToggleHandler = (event) => {
    const button = event.target.closest('[data-video-toggle]');
    if (!button) return;
    event.preventDefault();
    event.stopPropagation();
    const frame = button.closest('.video-frame');
    const video = frame?.querySelector('video');
    if (!video) {
      console.warn('Video toggle did not find video element');
      return;
    }

    console.log('Video toggle clicked', { muted: video.muted, hasMutedAttr: video.hasAttribute('muted') });

    const unmuteVideo = () => {
      video.dataset.userUnmuted = 'true';
      video.defaultMuted = false;
      video.removeAttribute('muted');
      video.muted = false;
      video.volume = 1;
      updateToggleState(button, video);
      console.log('Video unmuted');
    };

    if (video.muted || video.hasAttribute('muted')) {
      if (window.musicPlayerApi?.pause) {
        window.musicPlayerApi.pause();
        console.log('Paused music player before unmuting video');
      }
      unmuteVideo();
      const playPromise = video.play?.();
      if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch((error) => {
          console.warn('Video play rejected', error);
        });
      }
    } else {
      video.dataset.userUnmuted = 'false';
      video.pause();
      video.defaultMuted = true;
      video.setAttribute('muted', '');
      video.muted = true;
      updateToggleState(button, video);
      console.log('Video muted');
    }
  };

  document.addEventListener('click', videoToggleHandler);

  document.querySelectorAll('[data-video-toggle]').forEach((button) => {
    const frame = button.closest('.video-frame');
    const video = frame?.querySelector('video');
    if (video) {
      updateToggleState(button, video);
    }
  });

  const handleResize = () => {
    Object.values(sliderStates).forEach((state) => {
      if (state.mode !== 'multi') return;
      state.step = 0;
      applyVisibleCount(state);
      measureMulti(state);
    });
  };

  window.addEventListener('resize', handleResize);
  window.addEventListener('orientationchange', handleResize);
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

function initGreetingRotator() {
  document.querySelectorAll('[data-rotator]').forEach((rotator) => {
    const lines = Array.from(rotator.querySelectorAll('[data-rotator-line]'));
    if (!lines.length) return;
    let index = lines.findIndex((line) => line.classList.contains('active'));
    if (index < 0) {
      lines[0].classList.add('active');
      index = 0;
    }
    if (lines.length === 1) return;

    const rotate = () => {
      lines[index].classList.remove('active');
      index = (index + 1) % lines.length;
      lines[index].classList.add('active');
    };

    let timer;
    const start = () => {
      clearInterval(timer);
      timer = setInterval(rotate, 3600);
    };

    const pause = () => clearInterval(timer);

    rotator.addEventListener('mouseenter', pause);
    rotator.addEventListener('mouseleave', start);
    start();
  });
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
    const id = button.dataset.faq;
    const panel = document.querySelector(`[data-faq-panel="${id}"]`);
    if (!panel) return;
    button.setAttribute('aria-expanded', 'false');
    const toggle = () => {
      const isOpen = panel.classList.toggle('open');
      button.setAttribute('aria-expanded', String(isOpen));
    };
    button.addEventListener('click', toggle);
    button.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggle();
      }
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
  track.querySelectorAll('img, video').forEach((media) => {
    media.setAttribute('draggable', 'false');
    media.addEventListener('dragstart', (event) => event.preventDefault());
  });
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

  track.style.touchAction = 'pan-y';
  let dragPointerId = null;
  let dragStartX = 0;
  let isDraggingPointer = false;
  const dragThreshold = 40;

  const handlePointerDown = (event) => {
    dragPointerId = event.pointerId;
    dragStartX = event.clientX;
    isDraggingPointer = true;
    clearTimeout(timer);
    track.setPointerCapture?.(dragPointerId);
  };

  const handlePointerUp = (event) => {
    if (!isDraggingPointer || event.pointerId !== dragPointerId) return;
    track.releasePointerCapture?.(dragPointerId);
    isDraggingPointer = false;
    const delta = event.clientX - dragStartX;
    dragPointerId = null;
    if (Math.abs(delta) >= dragThreshold) {
      slide(delta > 0 ? -1 : 1);
    } else {
      schedule();
    }
  };

  const handlePointerMove = (event) => {
    if (!isDraggingPointer || event.pointerId !== dragPointerId) return;
    event.preventDefault();
  };

  const cancelDrag = (event) => {
    if (dragPointerId === null || event.pointerId !== dragPointerId) return;
    track.releasePointerCapture?.(dragPointerId);
    dragPointerId = null;
    isDraggingPointer = false;
    schedule();
  };

  track.addEventListener('pointerdown', handlePointerDown);
  track.addEventListener('pointerup', handlePointerUp);
  track.addEventListener('pointerleave', cancelDrag);
  track.addEventListener('pointercancel', cancelDrag);
  track.addEventListener('pointermove', handlePointerMove, { passive: false });

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

function initScrollTop() {
  const button = document.querySelector('[data-scroll-top]');
  if (!button) return;
  const toggleVisibility = () => {
    if (window.scrollY > 200) {
      button.classList.add('is-visible');
    } else {
      button.classList.remove('is-visible');
    }
  };
  button.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  window.addEventListener('scroll', toggleVisibility, { passive: true });
  toggleVisibility();
}

let youTubeApiPromise = null;

function loadYoutubeApi() {
  if (window.YT && window.YT.Player) {
    return Promise.resolve(window.YT);
  }
  if (youTubeApiPromise) {
    return youTubeApiPromise;
  }
  youTubeApiPromise = new Promise((resolve) => {
    const previousCallback = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => {
      if (typeof previousCallback === 'function') {
        previousCallback();
      }
      resolve(window.YT);
    };
    const script = document.createElement('script');
    script.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(script);
  });
  return youTubeApiPromise;
}

function initMusicPlayer() {
  const container = document.querySelector('[data-music-player]');
  const config = window.musicPlayerConfig;
  if (!container || !config || !Array.isArray(config.songs) || !config.songs.length) {
    return;
  }

  const songs = config.songs;
  const ui = {
    cover: container.querySelector('[data-music-cover]'),
    title: container.querySelector('[data-music-title]'),
    artist: container.querySelector('[data-music-artist]'),
    play: container.querySelector('[data-music-play]'),
    prev: container.querySelector('[data-music-prev]'),
    next: container.querySelector('[data-music-next]'),
    mute: container.querySelector('[data-music-mute]'),
    progress: container.querySelector('[data-music-progress]'),
    volume: container.querySelector('[data-music-volume]'),
    current: container.querySelector('[data-music-current]'),
    duration: container.querySelector('[data-music-duration]'),
    youtubeHolder: container.querySelector('[data-music-youtube]'),
  };

  const clampPercent = (value) => Math.min(Math.max(Number(value) || 0, 0), 100);

  const updateVolumeSliderFill = (value) => {
    if (!ui.volume) return;
    ui.volume.style.setProperty('--volume-progress', `${clampPercent(value)}%`);
  };

  const updateProgressSliderFill = (value) => {
    if (!ui.progress) return;
    ui.progress.style.setProperty('--progress-fill', `${clampPercent(value)}%`);
  };

  const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
  const defaultVolume = clamp((config.settings?.defaultVolume ?? Number(container.dataset.defaultVolume ?? 40)) / 100, 0, 1);
  const settings = config.settings || {};
  const defaultVolumeSetting = typeof settings.defaultVolume === 'number'
    ? settings.defaultVolume
    : Number(container.dataset.defaultVolume || 40);
  const defaultMutedSetting = typeof settings.defaultMuted === 'boolean'
    ? settings.defaultMuted
    : container.dataset.defaultMuted === 'true';

  const state = {
    index: 0,
    isPlaying: false,
    isMuted: defaultMutedSetting,
    volume: clamp(defaultVolumeSetting / 100, 0, 1),
    duration: 0,
    source: 'audio',
  };

  let isProgressDragging = false;

  const audio = new Audio();
  audio.preload = 'auto';
  audio.addEventListener('ended', () => handleNext());
  audio.addEventListener('loadedmetadata', () => {
    state.duration = audio.duration || 0;
  });

  const youtubeState = {
    player: null,
  };

  if (songs.some((song) => song.type === 'youtube')) {
    loadYoutubeApi();
  }

  const formatTime = (value) => {
    const total = Math.max(0, Math.floor(value || 0));
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
  };

  const currentSong = () => songs[state.index];

  function updateMeta() {
    const song = currentSong();
    ui.title.textContent = song.title;
    ui.artist.textContent = song.artist || 'Playlist ABDO IPTV';
    if (song.thumbnail) {
      ui.cover.style.backgroundImage = `url(${song.thumbnail})`;
    } else {
      ui.cover.style.backgroundImage = '';
    }
  }

  function applyVolume() {
    const effective = state.isMuted ? 0 : state.volume;
    audio.volume = effective;
    const percent = Math.round(state.volume * 100);
    ui.volume.value = percent;
    updateVolumeSliderFill(percent);
    if (youtubeState.player && youtubeState.player.setVolume) {
      youtubeState.player.setVolume(Math.round(state.volume * 100));
    }
  }

  function applyMute() {
    audio.muted = state.isMuted;
    if (youtubeState.player) {
      if (state.isMuted && youtubeState.player.mute) {
        youtubeState.player.mute();
      } else if (!state.isMuted && youtubeState.player.unMute) {
        youtubeState.player.unMute();
      }
    }
    ui.mute.textContent = state.isMuted ? '🔇' : '🔊';
  }

  function updatePlayButton() {
    ui.play.textContent = state.isPlaying ? '⏸' : '▶';
    container.classList.toggle('is-playing', state.isPlaying);
  }

  function setPlaying(nextState) {
    state.isPlaying = nextState;
    updatePlayButton();
    if (state.source === 'audio') {
      if (state.isPlaying) {
        const playPromise = audio.play();
        if (playPromise && typeof playPromise.then === 'function') {
          playPromise.catch(() => {
            state.isPlaying = false;
            updatePlayButton();
          });
        }
      } else {
        audio.pause();
      }
    } else if (youtubeState.player) {
      if (state.isPlaying && youtubeState.player.playVideo) {
        youtubeState.player.playVideo();
      } else if (!state.isPlaying && youtubeState.player.pauseVideo) {
        youtubeState.player.pauseVideo();
      }
    } else if (currentSong().type === 'youtube') {
      loadYoutubeTrack(currentSong());
    }
  }

  function handleNext(step = 1) {
    state.index = (state.index + step + songs.length) % songs.length;
    state.duration = 0;
    loadSong();
  }

  function loadSong() {
    const song = currentSong();
    updateMeta();
    ui.progress.value = 0;
    updateProgressSliderFill(0);
    ui.current.textContent = '0:00';
    ui.duration.textContent = '0:00';
    if (song.type === 'youtube') {
      state.source = 'youtube';
      audio.pause();
      audio.src = '';
      loadYoutubeTrack(song);
    } else {
      state.source = 'audio';
      if (youtubeState.player && youtubeState.player.stopVideo) {
        youtubeState.player.stopVideo();
      }
      audio.src = song.source;
      audio.load();
      if (state.isPlaying) {
        setTimeout(() => setPlaying(true), 80);
      }
    }
  }

  function loadYoutubeTrack(song) {
    if (!ui.youtubeHolder) return;
    if (!ui.youtubeHolder.id) {
      ui.youtubeHolder.id = 'musicYoutubeBridge';
    }
    loadYoutubeApi().then(() => {
      if (youtubeState.player) {
        youtubeState.player.loadVideoById(song.source);
        if (!state.isPlaying && youtubeState.player.pauseVideo) {
          youtubeState.player.pauseVideo();
        }
        applyMute();
        applyVolume();
        return;
      }
      youtubeState.player = new window.YT.Player(ui.youtubeHolder.id, {
        height: '0',
        width: '0',
        videoId: song.source,
        playerVars: {
          autoplay: state.isPlaying ? 1 : 0,
          controls: 0,
          rel: 0,
          playsinline: 1,
          enablejsapi: 1,
        },
        events: {
          onReady: () => {
            applyMute();
            applyVolume();
            if (!state.isPlaying && youtubeState.player && youtubeState.player.pauseVideo) {
              youtubeState.player.pauseVideo();
            }
          },
          onStateChange: (event) => {
            if (event.data === window.YT.PlayerState.ENDED) {
              handleNext();
            }
          },
        },
      });
    });
  }

  function updateProgressUI() {
    let current = 0;
    let duration = state.duration || 0;
    if (state.source === 'audio') {
      if (!Number.isNaN(audio.duration) && audio.duration) {
        duration = audio.duration;
      }
      current = audio.currentTime || 0;
    } else if (youtubeState.player && typeof youtubeState.player.getCurrentTime === 'function') {
      current = youtubeState.player.getCurrentTime() || 0;
      const ytDuration = youtubeState.player.getDuration?.();
      if (ytDuration) {
        duration = ytDuration;
      }
    }
    state.duration = duration;
    const percent = duration > 0 ? (current / duration) * 100 : 0;
    if (!isProgressDragging) {
      ui.progress.value = percent;
      updateProgressSliderFill(percent);
    }
    ui.current.textContent = formatTime(current);
    ui.duration.textContent = formatTime(duration);
  }

  function tick() {
    updateProgressUI();
    requestAnimationFrame(tick);
  }

  ui.play.addEventListener('click', () => {
    setPlaying(!state.isPlaying);
  });

  ui.prev.addEventListener('click', () => handleNext(-1));
  ui.next.addEventListener('click', () => handleNext(1));

  ui.mute.addEventListener('click', () => {
    state.isMuted = !state.isMuted;
    applyMute();
  });

  ui.volume.addEventListener('input', (event) => {
    const value = Number(event.target.value);
    state.volume = clamp(value / 100, 0, 1);
    state.isMuted = state.volume === 0;
    applyVolume();
    applyMute();
  });

  const stopProgressDrag = (commit = false) => {
    if (!isProgressDragging) return;
    isProgressDragging = false;
    if (commit) {
      const percentValue = clampPercent(ui.progress.value);
      const percent = percentValue / 100;
      const newTime = percent * state.duration;
      if (state.source === 'audio') {
        audio.currentTime = newTime;
      } else if (youtubeState.player && youtubeState.player.seekTo) {
        youtubeState.player.seekTo(newTime, true);
      }
      if (!state.isPlaying) {
        setPlaying(true);
      }
    }
  };

  ui.progress.addEventListener('pointerdown', () => {
    isProgressDragging = true;
  });
  ui.progress.addEventListener('pointerup', () => stopProgressDrag(true));
  ui.progress.addEventListener('pointercancel', () => stopProgressDrag());
  window.addEventListener('pointerup', () => stopProgressDrag(true));
  window.addEventListener('pointercancel', () => stopProgressDrag());

  ui.progress.addEventListener('input', (event) => {
    if (!state.duration) return;
    const percentValue = clampPercent(event.target.value);
    ui.progress.value = percentValue;
    updateProgressSliderFill(percentValue);
  });

  applyVolume();
  applyMute();
  updateMeta();
  loadSong();
  tick();

  window.musicPlayerApi = {
    pause: () => {
      if (state.isPlaying) {
        setPlaying(false);
      }
    },
  };
}








