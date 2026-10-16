// Main JavaScript file for Phambili Ma-Africa - UI Interactions only
// Auth handled by PHP sessions in includes/

document.addEventListener('DOMContentLoaded', function() {
    initHeader();
    initNavigation();
    initCart();
    initBackToTop();
    initCurrentYear();
    initServiceCarousel();
    initUserProfileDropdown();
    initScrollAnimations();
    initParallax();
    initCounterAnimations();
    initHeroTypewriter(); // Now uses simple fade-in instead of typewriter
});

// Header scroll effect
function initHeader() {
    const header = document.querySelector('.header');
    if (!header) return;
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('header-scrolled');
        } else {
            header.classList.remove('header-scrolled');
        }
    });
}

// Navigation sidebar functionality
function initNavigation() {
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const closeNavBtn = document.getElementById('close-nav-btn');
    const mainNav = document.getElementById('main-nav');
    const menuOverlay = document.getElementById('menu-overlay');
    
    if (!hamburgerBtn || !mainNav) return;
    
    function openNav() {
        mainNav.classList.add('active');
        if (menuOverlay) menuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeNav() {
        mainNav.classList.remove('active');
        if (menuOverlay) menuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    hamburgerBtn.addEventListener('click', openNav);
    if (closeNavBtn) closeNavBtn.addEventListener('click', closeNav);
    if (menuOverlay) menuOverlay.addEventListener('click', closeNav);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mainNav.classList.contains('active')) {
            closeNav();
        }
    });
}

// Cart sidebar (UI only - no auth)
function initCart() {
    const cartBtn = document.getElementById('cart-btn');
    const closeCartBtn = document.getElementById('close-cart-btn');
    const cartSidebar = document.getElementById('cart-sidebar');
    const menuOverlay = document.getElementById('menu-overlay');
    const isLoggedIn = document.body.dataset.isLoggedIn === 'true';
    
    if (!cartBtn || !cartSidebar) return;
    
    function openCart() {
        if (!isLoggedIn) {
            window.location.href = 'login.php';
            return;
        }
        cartSidebar.classList.add('active');
        if (menuOverlay) menuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeCart() {
        cartSidebar.classList.remove('active');
        if (menuOverlay) menuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    cartBtn.addEventListener('click', openCart);
    if (closeCartBtn) closeCartBtn.addEventListener('click', closeCart);
    if (menuOverlay) menuOverlay.addEventListener('click', closeCart);
}

// User Profile Dropdown functionality
function initUserProfileDropdown() {
    const profileDropdown = document.getElementById('user-profile-dropdown');
    const profileBtn = document.getElementById('user-profile-btn');
    const menuOverlay = document.getElementById('menu-overlay');

    if (!profileBtn || !profileDropdown) return;

    // Toggle dropdown on button click
    profileBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        profileDropdown.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('active');
        }
    });

    // Close dropdown when menu overlay is clicked
    if (menuOverlay) {
        menuOverlay.addEventListener('click', function() {
            profileDropdown.classList.remove('active');
        });
    }
}

// Back to top button
function initBackToTop() {
    const backToTop = document.getElementById('back-to-top');
    if (!backToTop) return;
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 500) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
    
    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// Set current year in footer
function initCurrentYear() {
    const yearElements = document.querySelectorAll('#current-year');
    const currentYear = new Date().getFullYear();
    yearElements.forEach(el => el.textContent = currentYear);
}

// Service carousel
function initServiceCarousel() {
    const servicesBar = document.getElementById('home-services-bar');
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    const progressBar = document.getElementById('progress-bar');

    if (!servicesBar) return;

    let currentPosition = 0;

    function getScrollStep() {
        const card = servicesBar.querySelector('.home-service-card');
        if (!card) return 352;
        const barStyle = window.getComputedStyle(servicesBar);
        const gap = parseFloat(barStyle.columnGap || barStyle.gap) || 32;
        return card.offsetWidth + gap;
    }

    function getMaxScroll() {
        return Math.max(0, servicesBar.scrollWidth - servicesBar.parentElement.clientWidth);
    }

    // Check if services are already rendered by PHP (database content)
    // If the bar has children other than loading-state, use those
    const hasPhpContent = servicesBar.querySelectorAll('.home-service-card').length > 0;

    // Only add default services if PHP hasn't rendered any
    if (!hasPhpContent) {
        // Default services (fallback if database is empty)
        const defaultServices = [
            { name: 'Deep Cleaning', icon: 'fa-broom' },
            { name: 'Office Cleaning', icon: 'fa-building' },
            { name: 'Carpet Cleaning', icon: 'fa-rug' },
            { name: 'Upholstery Cleaning', icon: 'fa-couch' },
            { name: 'Window Cleaning', icon: 'fa-window-maximize' }
        ];

        servicesBar.innerHTML = defaultServices.map(service => `
            <a href="services.php" class="home-service-card">
                <div class="home-card-image">
                    <i class="fas ${service.icon}"></i>
                </div>
                <div class="home-service-info">
                    <h3>${service.name}</h3>
                    <span class="home-service-cta">Learn More <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
        `).join('');
    }
    
    function updateCarousel() {
        const maxScroll = getMaxScroll();
        currentPosition = Math.min(currentPosition, maxScroll);
        servicesBar.style.transform = `translateX(-${currentPosition}px)`;
        if (progressBar) {
            const progress = maxScroll > 0 ? (currentPosition / maxScroll) * 100 : 100;
            progressBar.style.width = `${Math.min(100, Math.max(25, progress))}%`;
        }
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentPosition = Math.max(0, currentPosition - getScrollStep());
            updateCarousel();
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentPosition = Math.min(getMaxScroll(), currentPosition + getScrollStep());
            updateCarousel();
        });
    }

    window.addEventListener('resize', updateCarousel);
    updateCarousel();
}

// Dramatic Scroll Animations
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                
                // Add staggered animation for child elements
                const children = entry.target.querySelectorAll('.animate-stagger');
                children.forEach((child, index) => {
                    setTimeout(() => {
                        child.classList.add('animate-in');
                    }, index * 100);
                });
            }
        });
    }, observerOptions);

    // Observe elements with animation classes
    const animatedElements = document.querySelectorAll('.animate-on-scroll, .hero, .service-card, .testimonial-card, .stat-item, .highlight-card, .mission-item, .product-category-card, .social-card, .contact-form-container, .contact-info-box, .home-services-header, .index-quote-container');
    animatedElements.forEach(el => observer.observe(el));
}

// Parallax Effect
function initParallax() {
    const parallaxElements = document.querySelectorAll('.hero, .about-header, .contact-header, .booking-header, .services-banner, .product-header');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        
        parallaxElements.forEach(element => {
            const speed = 0.5;
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    });
}

// Counter Animations
function initCounterAnimations() {
    const counters = document.querySelectorAll('.stat-number');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = counter.innerText;
                const hasPlus = target.includes('+');
                const hasDecimal = target.includes('.');
                const numericValue = parseFloat(target.replace(/[^0-9.]/g, ''));
                
                let current = 0;
                const increment = numericValue / 50;
                const duration = 2000;
                const stepTime = duration / 50;
                
                const updateCounter = () => {
                    current += increment;
                    if (current < numericValue) {
                        let displayValue = current;
                        if (hasDecimal) {
                            displayValue = current.toFixed(1);
                        }
                        counter.innerText = Math.floor(displayValue) + (hasPlus ? '+' : '');
                        setTimeout(updateCounter, stepTime);
                    } else {
                        counter.innerText = target;
                    }
                };
                
                updateCounter();
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
}

// Hero Title Simple Fade Animation
function initHeroTypewriter() {
    const heroTitle = document.querySelector('.hero h1');
    if (!heroTitle) return;
    
    // Simple fade-in animation instead of typewriter
    heroTitle.style.opacity = '0';
    heroTitle.style.transform = 'translateY(20px)';
    heroTitle.style.transition = 'opacity 1s ease, transform 1s ease';
    
    setTimeout(() => {
        heroTitle.style.opacity = '1';
        heroTitle.style.transform = 'translateY(0)';
    }, 300);
}
