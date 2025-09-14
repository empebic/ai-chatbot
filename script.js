// Mobilna navigacija
const navToggle = document.querySelector('.nav-toggle');
const navLinks = document.querySelector('.nav-links');
if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', String(isOpen));
  });
  navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    navLinks.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
  }));
}

// Glatko skrolanje za interne linkove (fallback na CSS scroll-behavior bi postojao, ali eksplicitno)
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', (e) => {
    const id = (anchor.getAttribute('href') || '').slice(1);
    const target = document.getElementById(id);
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// Galerija / Lightbox
const galleryButtons = Array.from(document.querySelectorAll('.gallery-item'));
const lightbox = document.querySelector('.lightbox');
const lightboxImg = document.querySelector('.lightbox-image');
const btnClose = document.querySelector('.lightbox-close');
const btnPrev = document.querySelector('.lightbox-prev');
const btnNext = document.querySelector('.lightbox-next');

const galleryImages = galleryButtons.map(btn => {
  const img = btn.querySelector('img');
  return img ? img.getAttribute('src') : '';
});

let currentIndex = 0;
function openLightbox(index) {
  currentIndex = index;
  if (!lightbox || !lightboxImg) return;
  lightboxImg.src = galleryImages[index];
  lightbox.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  if (!lightbox) return;
  lightbox.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}
function showPrev() { openLightbox((currentIndex - 1 + galleryImages.length) % galleryImages.length); }
function showNext() { openLightbox((currentIndex + 1) % galleryImages.length); }

galleryButtons.forEach(btn => {
  btn.addEventListener('click', () => openLightbox(Number(btn.dataset.index || 0)));
});

btnClose && btnClose.addEventListener('click', closeLightbox);
btnPrev && btnPrev.addEventListener('click', showPrev);
btnNext && btnNext.addEventListener('click', showNext);

lightbox && lightbox.addEventListener('click', (e) => {
  if (e.target === lightbox) closeLightbox();
});

window.addEventListener('keydown', (e) => {
  if (!lightbox || lightbox.getAttribute('aria-hidden') === 'true') return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowLeft') showPrev();
  if (e.key === 'ArrowRight') showNext();
});

// Kontakt forma: validacija i prikaz uspjeha
const form = document.querySelector('.contact-form');
if (form) {
  const success = form.querySelector('.form-success');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = form.querySelector('#name');
    const email = form.querySelector('#email');
    const phone = form.querySelector('#phone');
    const message = form.querySelector('#message');

    // Reset grešaka
    form.querySelectorAll('.error').forEach(el => (el.textContent = ''));

    let valid = true;

    if (!name.value.trim()) {
      const err = form.querySelector('.error[data-for="name"]');
      if (err) err.textContent = 'Molimo unesite ime i prezime.';
      valid = false;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email.value.trim() || !emailPattern.test(email.value)) {
      const err = form.querySelector('.error[data-for="email"]');
      if (err) err.textContent = 'Unesite valjanu e-mail adresu.';
      valid = false;
    }

    const phoneValue = phone.value.trim();
    if (phoneValue && phoneValue.length < 6) {
      const err = form.querySelector('.error[data-for="phone"]');
      if (err) err.textContent = 'Unesite ispravan broj telefona (npr. +387 61 123 456).';
      valid = false;
    }

    if (!message.value.trim() || message.value.trim().length < 10) {
      const err = form.querySelector('.error[data-for="message"]');
      if (err) err.textContent = 'Napišite poruku (najmanje 10 znakova).';
      valid = false;
    }

    if (!valid) return;

    // Lažni async submit (može se povezati s backendom po potrebi)
    setTimeout(() => {
      if (success) success.hidden = false;
      form.reset();
    }, 400);
  });
}

// Godina u podnožju
const yearEl = document.getElementById('year');
if (yearEl) yearEl.textContent = String(new Date().getFullYear());