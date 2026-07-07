/**
 * assets/js/script.js
 * AgriGest Togo - Scripts principaux
 */

(function() {
    'use strict';

    // ========================================
    // 1. HEADER HIDE ON SCROLL
    // ========================================
    function initHeaderScroll() {
        const header = document.querySelector('header');
        if (!header) return;

        let lastScrollTop = 0;
        let ticking = false;

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    if (scrollTop > lastScrollTop && scrollTop > 100) {
                        header.style.transform = 'translateY(-100%)';
                        header.style.transition = 'transform 0.3s ease';
                    } else {
                        header.style.transform = 'translateY(0)';
                        header.style.transition = 'transform 0.3s ease';
                    }
                    
                    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    // ========================================
    // 2. HAMBURGER MENU - CORRIGÉ
    // ========================================
    function initHamburger() {
        const hamburger = document.getElementById('hamburgerBtn');
        const nav = document.getElementById('navMenu');

        if (!hamburger || !nav) {
            console.warn('❌ Hamburger non trouvé');
            return;
        }

        console.log('✅ Hamburger initialisé');

        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            
            // ✅ Inverser la logique : si le menu est ouvert, on le ferme et inversement
            const isOpen = nav.classList.contains('open');
            
            if (isOpen) {
                // Fermer le menu
                nav.classList.remove('open');
                this.classList.remove('active');
                this.setAttribute('aria-expanded', 'false');
                console.log('🔒 Menu fermé');
            } else {
                // Ouvrir le menu
                nav.classList.add('open');
                this.classList.add('active');
                this.setAttribute('aria-expanded', 'true');
                console.log('🔓 Menu ouvert');
            }
        });

        // Fermer le menu au clic sur un lien (mobile)
        nav.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    nav.classList.remove('open');
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Fermer le menu en redimensionnant
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                nav.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ========================================
    // 3. INITIALISATION
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        initHeaderScroll();
        initHamburger();
        console.log('✅ AgriGest Togo - Scripts chargés');
    });

})();