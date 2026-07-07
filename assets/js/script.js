/**
 * assets/js/main.js
 * Animations et interactions globales
 */

// ===== HEADER HIDE ON SCROLL =====
let lastScrollTop = 0;
const header = document.querySelector('header');

window.addEventListener('scroll', () => {
  let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  
  if (scrollTop > lastScrollTop) {
    // Scroll DOWN → cache le header
    header.style.transform = 'translateY(-100%)';
  } else {
    // Scroll UP → affiche le header
    header.style.transform = 'translateY(0)';
  }
  
  lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
});

// ===== HAMBURGER MENU =====
const hamburger = document.querySelector('.hamburger');
const nav = document.querySelector('nav');

if (hamburger) {
  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    nav.classList.toggle('open');
  });

  // Ferme le menu au clic sur un lien
  nav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('active');
      nav.classList.remove('open');
    });
  });
}