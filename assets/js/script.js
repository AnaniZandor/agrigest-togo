/**
 * assets/js/script.js
 * AgriGest Togo - Navigation complète + Header Scroll
 * v3.2 PRODUCTION
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
    // 2. HAMBURGER MENU - CORRIGÉ v3.2
    // ========================================
    function initHamburger() {
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('nav');

        if (!hamburger || !nav) {
            console.error('❌ ERREUR: Hamburger ou nav non trouvés');
            console.log('Hamburger:', hamburger);
            console.log('Nav:', nav);
            return;
        }

        console.log('✅ Hamburger initialisé');

        // Au clic sur le hamburger
        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🔘 Click hamburger');
            
            const isOpen = nav.classList.contains('open');
            
            if (isOpen) {
                // Fermer
                nav.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
                console.log('🔒 Menu fermé');
            } else {
                // Ouvrir
                nav.classList.add('open');
                hamburger.classList.add('active');
                hamburger.setAttribute('aria-expanded', 'true');
                console.log('🔓 Menu ouvert');
            }
        });

        // Fermer au clic sur un lien (mobile uniquement)
        nav.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    nav.classList.remove('open');
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                    console.log('📱 Menu fermé (click lien mobile)');
                }
            });
        });

        // Fermer si on redimensionne au-delà de 768px
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                nav.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });

        // Fermer en cliquant en dehors du header
        document.addEventListener('click', function(e) {
            if (!e.target.closest('header') && nav.classList.contains('open')) {
                nav.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
                console.log('🔒 Menu fermé (click extérieur)');
            }
        });
    }

    // ========================================
    // 3. DROPDOWN "PLUS" - v3.2 CORRIGÉ
    // ========================================
    function initNavDropdown() {
        const navDropdownBtn = document.querySelector('.nav-dropdown-btn');
        const navDropdownMenu = document.querySelector('.nav-dropdown-menu');
        const navDropdown = document.querySelector('.nav-dropdown');
        const nav = document.querySelector('nav');
        const navLinks = document.querySelectorAll('nav > a');

        if (!navDropdown || !navDropdownBtn || !navDropdownMenu) {
            console.warn('⚠️ Dropdown non trouvé (normal si pas assez de liens)');
            return;
        }

        console.log('✅ Dropdown manager initialisé');

        // Détecter et gérer le débordement
        function checkOverflow() {
            let hasOverflow = false;

            // Réinitialiser : afficher tous les liens
            navLinks.forEach(link => {
                link.style.display = 'flex';
            });

            navDropdownMenu.innerHTML = '';

            // Calculer la largeur disponible
            const requiredDropdownWidth = 80; // width du bouton "Plus"
            const navAvailableWidth = nav.offsetWidth - requiredDropdownWidth - 20;

            let currentWidth = 0;
            let linksToHide = [];

            // Identifier les liens à cacher
            navLinks.forEach((link) => {
                const linkWidth = link.offsetWidth;
                currentWidth += linkWidth;

                if (currentWidth > navAvailableWidth) {
                    linksToHide.push(link);
                    hasOverflow = true;
                }
            });

            // Appliquer les changements
            if (hasOverflow && linksToHide.length > 0) {
                console.log(`📊 ${linksToHide.length} lien(s) caché(s)`);
                
                navDropdown.classList.add('has-overflow');

                linksToHide.forEach(link => {
                    link.style.display = 'none';
                });

                linksToHide.forEach(link => {
                    const clone = link.cloneNode(true);
                    clone.style.display = 'flex';
                    navDropdownMenu.appendChild(clone);
                });
            } else {
                console.log('✅ Pas de débordement');
                navDropdown.classList.remove('has-overflow');
                navDropdownMenu.innerHTML = '';
            }
        }

        // Toggle dropdown
        navDropdownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            navDropdownBtn.classList.toggle('active');
            navDropdownMenu.classList.toggle('open');
        });

        // Fermer au clic sur un lien
        navDropdownMenu.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                navDropdownBtn.classList.remove('active');
                navDropdownMenu.classList.remove('open');
            }
        });

        // Fermer en cliquant en dehors
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown')) {
                navDropdownBtn.classList.remove('active');
                navDropdownMenu.classList.remove('open');
            }
        });

        // Vérifier le débordement
        setTimeout(checkOverflow, 300);
        window.addEventListener('resize', function() {
            setTimeout(checkOverflow, 250);
        });
        window.addEventListener('load', function() {
            setTimeout(checkOverflow, 100);
        });
    }

    // ========================================
    // 4. INITIALISATION GLOBALE
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 AgriGest Togo v3.2 - Initialisation...');
        
        initHeaderScroll();
        initHamburger();
        initNavDropdown();
        
        console.log('✅ Tous les scripts chargés');
    });

})();