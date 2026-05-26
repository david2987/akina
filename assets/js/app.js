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
  const reveals = document.querySelectorAll('.reveal, .card, .feature-card, .number-block, .step-icon, .connector-svg, .tech-card, .step, .review');
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        el.classList.add('show');
        // play pop on step icons
        if (el.classList.contains('step-icon')) {
          el.classList.add('play-pop');
        }
        // animate connector SVGs
        if (el.classList && el.classList.contains('connector-svg')) {
          el.classList.add('play');
        }
        // tech-card animation
        if (el.classList && el.classList.contains('tech-card')) {
          el.classList.add('play-slide');
          const img = el.querySelector('img');
          img && img.classList.add('play-pop');
          // float-slow micro-motion
          img && img.classList.add('float-slow');
        }
        // review slide animation
        if (el.classList && el.classList.contains('review')) {
          el.classList.add('active');
        }
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

  // Contact form with AJAX submission
  const contactForm = document.querySelector('.contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      // Obtener datos del formulario
      const formData = new FormData(contactForm);
      const submitButton = contactForm.querySelector('button[type="submit"]');
      const originalButtonText = submitButton.textContent;
      
      // Deshabilitar botón y mostrar estado de carga
      submitButton.disabled = true;
      submitButton.textContent = 'Enviando...';
      submitButton.style.opacity = '0.7';
      
      try {
        // Enviar datos al servidor
        const response = await fetch('send-email.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          // Mostrar mensaje de éxito
          showNotification('success', result.message);
          contactForm.reset();
        } else {
          // Mostrar mensaje de error
          showNotification('error', result.message || 'Error al enviar el mensaje. Por favor, intenta nuevamente.');
        }
      } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Error de conexión. Por favor, verifica tu conexión a internet e intenta nuevamente.');
      } finally {
        // Restaurar botón
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
        submitButton.style.opacity = '1';
      }
    });
  }
  
  // Función para mostrar notificaciones elegantes
  function showNotification(type, message) {
    // Remover notificación existente si hay alguna
    const existingNotification = document.querySelector('.custom-notification');
    if (existingNotification) {
      existingNotification.remove();
    }
    
    // Crear notificación
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    
    // Icono según el tipo
    const icon = type === 'success' 
      ? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
      : '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
    
    notification.innerHTML = `
      <div class="notification-icon">${icon}</div>
      <div class="notification-message">${message}</div>
      <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => notification.remove(), 300);
    }, 5000);
  }

});
