/**
 * assets/js/script.js
 * AgriGest Togo - Navigation complète + Header Scroll
 * v3.1 Professionnel
 */

function() {
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
    // 2. HAMBURGER MENU - v3.1 AMÉLIORÉ
    // ========================================
    function initHamburger() {
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('nav');

        if (!hamburger || !nav) {
            console.warn('❌ Hamburger ou nav non trouvés');
            return;
        }

        console.log('✅ Hamburger initialisé');

        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = nav.classList.contains('open');
            
            if (isOpen) {
                nav.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
                console.log('🔒 Menu fermé');
            } else {
                nav.classList.add('open');
                hamburger.classList.add('active');
                hamburger.setAttribute('aria-expanded', 'true');
                console.log('🔓 Menu ouvert');
            }
        });

        // Fermer au clic sur un lien
        nav.querySelectorAll('a:not(.nav-dropdown-btn)').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    nav.classList.remove('open');
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Fermer en redimensionnant
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                nav.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });

        // Fermer en cliquant en dehors (desktop + mobile)
        document.addEventListener('click', function(e) {
            if (!e.target.closest('header')) {
                nav.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
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
        console.warn('⚠️ Dropdown non trouvé');
        return;
    }

    console.log('✅ Dropdown manager initialisé');

    // Remplir le dropdown avec les liens cachés
    function populateDropdown() {
        navDropdownMenu.innerHTML = '';
        
        navLinks.forEach((link) => {
            // Si le lien est hors de vue ou caché
            if (link.offsetLeft + link.offsetWidth > nav.offsetWidth - 100) {
                const clone = link.cloneNode(true);
                clone.className = clone.className.replace(/\s*active\s*/, ' ').trim();
                navDropdownMenu.appendChild(clone);
            }
        });
    }

    // Détecter et gérer le débordement
    function checkOverflow() {
        let hasOverflow = false;

        // Réinitialiser : afficher tous les liens
        navLinks.forEach(link => {
            link.style.display = 'flex';
        });

        navDropdownMenu.innerHTML = '';

        // Calculer la largeur nécessaire pour afficher le bouton "Plus"
        const requiredDropdownWidth = 80; // env. width du bouton "Plus"
        const navAvailableWidth = nav.offsetWidth - requiredDropdownWidth - 20;

        let currentWidth = 0;
        let linksToHide = [];

        // Parcourir les liens et identifier ceux à cacher
        navLinks.forEach((link, index) => {
            const linkWidth = link.offsetWidth;
            currentWidth += linkWidth;

            if (currentWidth > navAvailableWidth) {
                linksToHide.push(link);
                hasOverflow = true;
            }
        });

        // Si débordement détecté
        if (hasOverflow && linksToHide.length > 0) {
            console.log(`📊 ${linksToHide.length} lien(s) caché(s) pour débordement`);
            
            // Montrer le dropdown
            navDropdown.classList.add('has-overflow');

            // Cacher les liens qui débordent
            linksToHide.forEach(link => {
                link.style.display = 'none';
            });

            // Remplir le dropdown avec les liens cachés
            linksToHide.forEach(link => {
                const clone = link.cloneNode(true);
                clone.style.display = 'flex';
                clone.className = clone.className.replace(/\s*active\s*/, ' ').trim();
                navDropdownMenu.appendChild(clone);
            });
        } else {
            // Pas de débordement
            console.log('✅ Pas de débordement');
            navDropdown.classList.remove('has-overflow');
            navDropdownMenu.innerHTML = '';
        }
    }

    // Toggle dropdown
    navDropdownBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('🔽 Toggle dropdown');
        navDropdownBtn.classList.toggle('active');
        navDropdownMenu.classList.toggle('open');
    });

    // Fermer au clic sur un lien du dropdown
    navDropdownMenu.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' || e.target.closest('a')) {
            navDropdownBtn.classList.remove('active');
            navDropdownMenu.classList.remove('open');
            
            // Fermer le menu mobile aussi si ouvert
            if (window.innerWidth <= 768) {
                const hamburger = document.querySelector('.hamburger');
                nav.classList.remove('open');
                hamburger?.classList.remove('active');
            }
        }
    });

    // Fermer en cliquant en dehors
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown')) {
            navDropdownBtn.classList.remove('active');
            navDropdownMenu.classList.remove('open');
        }
    });

    // IMPORTANT : Vérifier le débordement
    // Attendre que les fonts soient chargées
    if (document.fonts) {
        document.fonts.ready.then(() => {
            console.log('🔤 Fonts chargées, vérification du débordement...');
            setTimeout(checkOverflow, 200);
        });
    } else {
        setTimeout(checkOverflow, 300);
    }

    // Vérifier aussi au resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            console.log('↔️ Resize détecté, re-vérification...');
            checkOverflow();
        }, 250);
    });

    // Vérifier quand la page est complètement chargée
    window.addEventListener('load', function() {
        console.log('📄 Page complètement chargée, vérification finale...');
        setTimeout(checkOverflow, 100);
    });
 }
}