// Minimal UX and effects: parallax, reveal on scroll, counters, tilt effect

document.addEventListener('DOMContentLoaded', function () {
  // Parallax
  const parallax = document.querySelectorAll('[data-speed]');
  window.addEventListener('scroll', function () {
    const scrolled = window.pageYOffset;
    parallax.forEach(el => {
      const speed = parseFloat(el.dataset.speed || 0.5);
      const y = (scrolled * speed) / 2;
      el.style.transform = `translate3d(0, ${y}px, 0)`;
    });
  });

  // Reveal on scroll
  const reveals = document.querySelectorAll('.reveal, .card, .feature-card, .number-block');
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('show');
      }
    });
  }, { threshold: 0.12 });
  reveals.forEach(e => observer.observe(e));

  // Counters
  const counters = document.querySelectorAll('.number');
  const counterObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseFloat(el.dataset.target) || 0;
        if (el.dataset.animated) return;
        el.dataset.animated = 'true';
        let start = 0; const duration = 1200;
        const stepTime = Math.max(10, Math.floor(duration / (target || 1)));
        const startTs = performance.now();
        function tick(ts) {
          const elapsed = ts - startTs;
          const progress = Math.min(1, elapsed / duration);
          const value = Math.floor(progress * target);
          el.textContent = value.toLocaleString();
          if (progress < 1) requestAnimationFrame(tick);
          else el.textContent = (target >= 1 && Number.isInteger(target)) ? target.toLocaleString() : target;
        }
        requestAnimationFrame(tick);
      }
    });
  }, { threshold: 0.7 });
  counters.forEach(c => counterObserver.observe(c));

  // Simple tilt on hover for class .tilt
  const tiltEls = document.querySelectorAll('.tilt');
  tiltEls.forEach(el => {
    el.addEventListener('mousemove', (e) => {
      const rect = el.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width;
      const y = (e.clientY - rect.top) / rect.height;
      const rx = (y - 0.5) * -6;
      const ry = (x - 0.5) * 8;
      el.style.transform = `perspective(900px) rotateX(${rx}deg) rotateY(${ry}deg)`;
    });
    el.addEventListener('mouseleave', () => {
      el.style.transform = '';
    });
  });

  // Enhanced carousel for testimonials (filters for '.review' slides)
  const reviewsContainer = document.querySelector('.carousel.reviews');
  if (reviewsContainer) {
    const slides = Array.from(reviewsContainer.querySelectorAll('.review'));
    const prevBtn = reviewsContainer.querySelector('.carousel-prev');
    const nextBtn = reviewsContainer.querySelector('.carousel-next');
    let slideIndex = 0;
    function showSlide(i) {
      slides.forEach((s, k) => {
        s.style.display = (k === i ? 'block' : 'none');
        s.classList.toggle('active', k === i);
      });
    }
    showSlide(slideIndex);
    // Autoplay
    let autoplay = setInterval(() => {
      slideIndex = (slideIndex + 1) % slides.length;
      showSlide(slideIndex);
    }, 5000);
    // Controls
    prevBtn && prevBtn.addEventListener('click', () => { clearInterval(autoplay); slideIndex = (slideIndex - 1 + slides.length) % slides.length; showSlide(slideIndex); });
    nextBtn && nextBtn.addEventListener('click', () => { clearInterval(autoplay); slideIndex = (slideIndex + 1) % slides.length; showSlide(slideIndex); });
  }

  // Nav toggle
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  const nav = document.querySelector('.nav');
  navToggle && navToggle.addEventListener('click', () => {
    if (navLinks.style.display === 'flex') navLinks.style.display = 'none';
    else navLinks.style.display = 'flex';
  });

  // Sticky header on scroll
  let prevScroll = window.pageYOffset;
  const header = document.querySelector('.site-header');
    // No sticky header: We keep the header static to avoid layout blocking when scrolling.

  // Contact form simulation
  const contactForm = document.querySelector('.contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = contactForm.querySelector('input[type=text]')?.value || 'Cliente';
      alert(`Gracias ${name}. Tu mensaje fue recibido. Nos contactaremos pronto.`);
      contactForm.reset();
    });
  }

});
