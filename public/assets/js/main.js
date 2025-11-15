'use strict';

document.addEventListener('DOMContentLoaded', () => {
  initSliders();
  initAnimations();
  initOfferModal();
  initFaq();
  initMobileMenu();
});

function initSliders() {
  const sliderStates = {};
  document.querySelectorAll('[data-slider]').forEach((slider, index) => {
    const slides = Array.from(slider.querySelectorAll('.slide'));
    if (!slides.length) return;
    const id = slider.dataset.slider || `slider-${index}`;
    slider.dataset.slider = id;
    sliderStates[id] = { slides, current: 0 };
    slides[0].classList.add('active');
  });

  const changeSlide = (id, direction) => {
    const state = sliderStates[id];
    if (!state) return;
    const { slides } = state;
    state.current = (state.current + direction + slides.length) % slides.length;
    slides.forEach((slide, idx) => {
      slide.classList.toggle('active', idx === state.current);
    });
  };

  document.querySelectorAll('[data-slider-target]').forEach((button) => {
    const id = button.dataset.sliderTarget;
    const direction = button.dataset.direction === 'prev' ? -1 : 1;
    button.addEventListener('click', () => changeSlide(id, direction));
  });

  const autoConfigs = {
    hero: 7000,
    movies: 5000,
    sports: 5000,
    testimonials: 6000,
  };
  Object.entries(autoConfigs).forEach(([id, delay]) => {
    if (sliderStates[id]) {
      setInterval(() => changeSlide(id, 1), delay);
    }
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
