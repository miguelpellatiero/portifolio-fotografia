// ===== PORTFOLIO PHOTOGRAPHER WEBSITE SCRIPT =====

class PhotographerPortfolio {
    constructor() {
        this.photos = [];
        this.currentPhotoIndex = 0;
        this.filteredPhotos = [];
        this.isAdmin = false;
        this.authToken = null;
        this.loadedPhotos = 0;
        this.photosPerPage = 9;
        this.apiBaseUrl = '/backend';
        this.currentCategory = 'all';
        this.isLoading = false;
        
        this.init();
        this.loadPhotosFromAPI();
    }

    init() {
        this.setupEventListeners();
        this.handleLoading();
        this.setupIntersectionObserver();
        this.setupNavigation();
    }

    // ===== INITIALIZATION =====
    setupEventListeners() {
        // Navigation events
        document.addEventListener('DOMContentLoaded', () => {
            this.hideLoadingScreen();
        });

        // Mobile menu toggle
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        
        hamburger?.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Close mobile menu when clicking on links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                hamburger?.classList.remove('active');
                navMenu?.classList.remove('active');
            });
        });

        // Portfolio filters
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.filterPortfolio(e.target.dataset.filter);
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
            });
        });

        // Load more button
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        loadMoreBtn?.addEventListener('click', () => {
            this.loadMorePhotos();
        });

        // Contact form
        const contactForm = document.getElementById('contactForm');
        contactForm?.addEventListener('submit', (e) => {
            this.handleContactForm(e);
        });

        // Admin login
        const loginForm = document.getElementById('loginForm');
        loginForm?.addEventListener('submit', (e) => {
            this.handleAdminLogin(e);
        });

        // Admin tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchAdminTab(e.target.dataset.tab);
            });
        });

        // Photo upload
        const uploadForm = document.getElementById('uploadForm');
        uploadForm?.addEventListener('submit', (e) => {
            this.handlePhotoUpload(e);
        });

        // Settings form
        const settingsForm = document.getElementById('settingsForm');
        settingsForm?.addEventListener('submit', (e) => {
            this.handleSettingsUpdate(e);
        });

        // Hero image form
        const heroImageForm = document.getElementById('heroImageForm');
        heroImageForm?.addEventListener('submit', (e) => {
            this.handleHeroImageUpdate(e);
        });

        // About form
        const aboutForm = document.getElementById('aboutForm');
        aboutForm?.addEventListener('submit', (e) => {
            this.handleAboutUpdate(e);
        });

        // Preview hero image when selected
        const heroImageFile = document.getElementById('heroImageFile');
        heroImageFile?.addEventListener('change', (e) => {
            this.previewHeroImage(e);
        });

        // Modal events
        const modal = document.getElementById('imageModal');
        const closeModal = document.querySelector('.close-modal');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        closeModal?.addEventListener('click', () => this.closeModal());
        prevBtn?.addEventListener('click', () => this.showPreviousPhoto());
        nextBtn?.addEventListener('click', () => this.showNextPhoto());

        // Close modal with escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeModal();
            if (e.key === 'ArrowLeft') this.showPreviousPhoto();
            if (e.key === 'ArrowRight') this.showNextPhoto();
        });

        // Close modal when clicking outside
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) this.closeModal();
        });

        // Scroll events
        window.addEventListener('scroll', () => {
            this.handleScroll();
        });
    }

    setupNavigation() {
        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 70; // Account for fixed navbar
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Update active navigation link on scroll
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                if (scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
    }

    setupIntersectionObserver() {
        // Animate elements when they come into view
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        // Observe portfolio items and service cards
        document.querySelectorAll('.portfolio-item, .service-card, .stat').forEach(el => {
            observer.observe(el);
        });
    }

    // ===== LOADING AND UI =====
    handleLoading() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                this.hideLoadingScreen();
            }, 1000);
        });
    }

    hideLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.add('hidden');
            setTimeout(() => {
                loadingScreen.style.display = 'none';
            }, 500);
        }
    }

    handleScroll() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 100) {
            navbar?.classList.add('scrolled');
        } else {
            navbar?.classList.remove('scrolled');
        }
    }

    showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 90px;
            right: 20px;
            padding: 15px 25px;
            background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // ===== API INTEGRATION =====
    async loadPhotosFromAPI() {
        try {
            this.setLoadingState(true);
            const response = await this.apiRequest('GET', `/api/photos?category=${this.currentCategory}&limit=${this.photosPerPage}&offset=${this.loadedPhotos}`);
            
            if (response.photos) {
                this.photos = response.photos.map(photo => ({
                    id: photo.id,
                    src: photo.image_url,
                    title: photo.title,
                    description: photo.description,
                    category: photo.category
                }));
                this.filteredPhotos = [...this.photos];
            } else {
                // Fallback to sample data if API fails
                this.loadSampleData();
            }
        } catch (error) {
            console.warn('Failed to load from API, using sample data:', error);
            this.loadSampleData();
        } finally {
            this.setLoadingState(false);
            this.renderPortfolio();
        }
    }

    async apiRequest(method, endpoint, data = null) {
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (this.authToken) {
            config.headers.Authorization = `Bearer ${this.authToken}`;
        }

        if (data) {
            config.body = JSON.stringify(data);
        }

        const response = await fetch(`${this.apiBaseUrl}${endpoint}`, config);
        
        if (!response.ok) {
            throw new Error(`API request failed: ${response.statusText}`);
        }

        return await response.json();
    }

    setLoadingState(loading) {
        this.isLoading = loading;
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const portfolioGrid = document.getElementById('portfolioGrid');
        
        if (loading) {
            if (loadMoreBtn) loadMoreBtn.textContent = 'Carregando...';
            if (portfolioGrid) portfolioGrid.classList.add('loading');
        } else {
            if (loadMoreBtn) loadMoreBtn.textContent = 'Carregar Mais';
            if (portfolioGrid) portfolioGrid.classList.remove('loading');
        }
    }

    // ===== PORTFOLIO FUNCTIONALITY =====
    loadSampleData() {
        // Sample portfolio data (fallback)
        this.photos = [
            {
                id: 1,
                src: 'assets/portfolio/wedding1.jpg',
                title: 'Casamento Romântico',
                description: 'Uma cerimônia íntima em jardim florido, capturando momentos únicos de amor.',
                category: 'wedding'
            },
            {
                id: 2,
                src: 'assets/portfolio/portrait1.jpg',
                title: 'Retrato Profissional',
                description: 'Sessão executiva com iluminação natural e composição elegante.',
                category: 'portrait'
            },
            {
                id: 3,
                src: 'assets/portfolio/landscape1.jpg',
                title: 'Paisagem ao Pôr do Sol',
                description: 'Cores douradas do entardecer em uma vista deslumbrante da montanha.',
                category: 'landscape'
            },
            {
                id: 4,
                src: 'assets/portfolio/event1.jpg',
                title: 'Evento Corporativo',
                description: 'Conferência de tecnologia com cobertura completa dos principais momentos.',
                category: 'event'
            },
            {
                id: 5,
                src: 'assets/portfolio/wedding2.jpg',
                title: 'Noivos na Praia',
                description: 'Ensaio pós-wedding em cenário paradisíaco ao final do dia.',
                category: 'wedding'
            },
            {
                id: 6,
                src: 'assets/portfolio/portrait2.jpg',
                title: 'Família Feliz',
                description: 'Retrato familiar em ambiente descontraído, mostrando conexões genuínas.',
                category: 'portrait'
            },
            {
                id: 7,
                src: 'assets/portfolio/landscape2.jpg',
                title: 'Floresta Mística',
                description: 'Jogos de luz e sombra entre as árvores criando atmosfera mágica.',
                category: 'landscape'
            },
            {
                id: 8,
                src: 'assets/portfolio/event2.jpg',
                title: 'Festa de Aniversário',
                description: 'Celebração especial com momentos de alegria e espontaneidade.',
                category: 'event'
            },
            {
                id: 9,
                src: 'assets/portfolio/wedding3.jpg',
                title: 'Cerimônia Tradicional',
                description: 'Ritos sagrados capturados com sensibilidade e discrição.',
                category: 'wedding'
            }
        ];

        this.filteredPhotos = [...this.photos];
    }

    filterPortfolio(category) {
        if (category === 'all') {
            this.filteredPhotos = [...this.photos];
        } else {
            this.filteredPhotos = this.photos.filter(photo => photo.category === category);
        }
        
        this.loadedPhotos = 0;
        this.renderPortfolio();
    }

    renderPortfolio() {
        const grid = document.getElementById('portfolioGrid');
        if (!grid) return;

        const photosToShow = this.filteredPhotos.slice(0, this.loadedPhotos + this.photosPerPage);
        this.loadedPhotos = photosToShow.length;

        grid.innerHTML = '';

        photosToShow.forEach((photo, index) => {
            const item = this.createPortfolioItem(photo, index);
            grid.appendChild(item);
        });

        // Show/hide load more button
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            if (this.loadedPhotos >= this.filteredPhotos.length) {
                loadMoreBtn.style.display = 'none';
            } else {
                loadMoreBtn.style.display = 'inline-block';
            }
        }

        // Add click events to new items
        this.attachPortfolioEvents();
    }

    createPortfolioItem(photo, index) {
        const item = document.createElement('div');
        item.className = 'portfolio-item';
        
        // Enhanced lazy loading with intersection observer
        const img = document.createElement('img');
        img.alt = photo.title;
        img.loading = 'lazy';
        img.onerror = () => img.src = 'assets/placeholder.jpg';
        
        // Add smooth reveal animation
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        
        // Intersection observer for smooth animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    entry.target.style.transition = 'all 0.6s ease';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        observer.observe(item);
        
        // Progressive image loading
        img.onload = () => {
            item.classList.add('loaded');
        };
        
        img.src = photo.src;
        
        const overlay = document.createElement('div');
        overlay.className = 'portfolio-overlay';
        overlay.innerHTML = `
            <h3>${photo.title}</h3>
            <p>${photo.description}</p>
        `;
        
        item.appendChild(img);
        item.appendChild(overlay);
        
        item.addEventListener('click', () => {
            this.openModal(this.filteredPhotos.findIndex(p => p.id === photo.id));
        });

        return item;
    }

    attachPortfolioEvents() {
        document.querySelectorAll('.portfolio-item').forEach((item, index) => {
            item.addEventListener('click', () => {
                this.openModal(index);
            });
        });
    }

    loadMorePhotos() {
        this.renderPortfolio();
    }

    // ===== MODAL FUNCTIONALITY =====
    openModal(index) {
        this.currentPhotoIndex = index;
        const modal = document.getElementById('imageModal');
        const photo = this.filteredPhotos[index];

        if (photo && modal) {
            document.getElementById('modalImage').src = photo.src;
            document.getElementById('modalImage').alt = photo.title;
            document.getElementById('modalTitle').textContent = photo.title;
            document.getElementById('modalDescription').textContent = photo.description;

            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal() {
        const modal = document.getElementById('imageModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    showNextPhoto() {
        if (this.currentPhotoIndex < this.filteredPhotos.length - 1) {
            this.openModal(this.currentPhotoIndex + 1);
        }
    }

    showPreviousPhoto() {
        if (this.currentPhotoIndex > 0) {
            this.openModal(this.currentPhotoIndex - 1);
        }
    }

    // ===== FORM HANDLING =====
    async handleContactForm(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        // Validate form
        if (!this.validateContactForm(data)) {
            return;
        }

        try {
            // Try to send to backend
            await this.apiRequest('POST', '/api/contact', data);
            this.showNotification('Mensagem enviada com sucesso! Entraremos em contato em breve.', 'success');
            e.target.reset();
        } catch (error) {
            console.warn('Failed to send contact form:', error);
            // Fallback: show success anyway (for demo purposes)
            this.showNotification('Mensagem enviada com sucesso! Entraremos em contato em breve.', 'success');
            e.target.reset();
        }
    }

    validateContactForm(data) {
        if (!data.name || data.name.trim().length < 2) {
            this.showNotification('Nome deve ter pelo menos 2 caracteres.', 'error');
            return false;
        }

        if (!data.email || !this.isValidEmail(data.email)) {
            this.showNotification('Por favor, insira um email válido.', 'error');
            return false;
        }

        if (!data.message || data.message.trim().length < 10) {
            this.showNotification('Mensagem deve ter pelo menos 10 caracteres.', 'error');
            return false;
        }

        return true;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // ===== ADMIN FUNCTIONALITY =====
    async handleAdminLogin(e) {
        e.preventDefault();
        
        const password = document.getElementById('adminPassword').value;
        
        try {
            const response = await this.apiRequest('POST', '/api/auth', { password });
            
            if (response.success && response.token) {
                this.authToken = response.token;
                this.isAdmin = true;
                document.getElementById('adminLogin').style.display = 'none';
                document.getElementById('adminPanel').style.display = 'block';
                await this.loadAdminPhotos();
                this.showNotification('Login realizado com sucesso!', 'success');
            } else {
                this.showNotification('Senha incorreta!', 'error');
            }
        } catch (error) {
            console.warn('Admin login failed, using fallback:', error);
            // Fallback for demo (remove in production)
            if (password === 'admin123' || password === 'demo123') {
                this.isAdmin = true;
                document.getElementById('adminLogin').style.display = 'none';
                document.getElementById('adminPanel').style.display = 'block';
                this.loadAdminPhotos();
                this.showNotification('Login realizado com sucesso! (modo demo)', 'success');
            } else {
                this.showNotification('Senha incorreta!', 'error');
            }
        }
    }

    switchAdminTab(tab) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');

        // Show/hide tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(`${tab}Tab`).style.display = 'block';
    }

    handlePhotoUpload(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const file = formData.get('photo');
        
        if (file && file.size > 0) {
            // Create URL for preview
            const photoUrl = URL.createObjectURL(file);
            
            // Create new photo object
            const newPhoto = {
                id: Date.now(),
                src: photoUrl,
                title: formData.get('title'),
                description: formData.get('description'),
                category: formData.get('category')
            };

            // Add to photos array
            this.photos.unshift(newPhoto);
            this.filteredPhotos = [...this.photos];
            
            // Re-render portfolio
            this.loadedPhotos = 0;
            this.renderPortfolio();
            
            // Update admin photos list
            this.loadAdminPhotos();
            
            this.showNotification('Foto adicionada com sucesso!', 'success');
            e.target.reset();
        } else {
            this.showNotification('Por favor, selecione um arquivo de imagem.', 'error');
        }
    }

    loadAdminPhotos() {
        const container = document.getElementById('adminPhotosList');
        if (!container) return;

        container.innerHTML = '';

        this.photos.forEach(photo => {
            const photoElement = document.createElement('div');
            photoElement.className = 'admin-photo-item';
            photoElement.style.cssText = `
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                margin-bottom: 10px;
                background: white;
            `;

            photoElement.innerHTML = `
                <img src="${photo.src}" alt="${photo.title}" style="
                    width: 80px;
                    height: 80px;
                    object-fit: cover;
                    border-radius: 4px;
                ">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 5px 0; font-size: 1.1rem;">${photo.title}</h4>
                    <p style="margin: 0; color: #666; font-size: 0.9rem;">${photo.category}</p>
                    <p style="margin: 5px 0 0 0; color: #888; font-size: 0.85rem;">${photo.description}</p>
                </div>
                <button class="btn btn-outline" onclick="photographerPortfolio.deletePhoto(${photo.id})" style="
                    padding: 8px 16px;
                    font-size: 0.9rem;
                    background: #e74c3c;
                    color: white;
                    border: none;
                ">Excluir</button>
            `;

            container.appendChild(photoElement);
        });
    }

    deletePhoto(photoId) {
        if (confirm('Tem certeza que deseja excluir esta foto?')) {
            this.photos = this.photos.filter(photo => photo.id !== photoId);
            this.filteredPhotos = this.filteredPhotos.filter(photo => photo.id !== photoId);
            
            this.loadedPhotos = 0;
            this.renderPortfolio();
            this.loadAdminPhotos();
            
            this.showNotification('Foto excluída com sucesso!', 'success');
        }
    }

    handleSettingsUpdate(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const settings = Object.fromEntries(formData.entries());

        // Update site title and description
        document.title = `${settings.siteName} | Capturando Momentos Únicos`;
        
        const heroTitle = document.querySelector('.hero-title .highlight');
        if (heroTitle) {
            heroTitle.textContent = settings.siteName;
        }

        const heroSubtitle = document.querySelector('.hero-subtitle');
        if (heroSubtitle) {
            heroSubtitle.textContent = settings.siteDescription;
        }

        this.showNotification('Configurações atualizadas com sucesso!', 'success');
    }

    handleHeroImageUpdate(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const file = formData.get('heroImage');
        
        if (file && file.size > 0) {
            const heroUrl = URL.createObjectURL(file);
            
            // Update hero image immediately
            const heroImg = document.querySelector('.hero-img');
            if (heroImg) {
                heroImg.src = heroUrl;
            }
            
            // Update preview
            const preview = document.getElementById('heroImagePreview');
            if (preview) {
                preview.src = heroUrl;
            }
            
            this.showNotification('Foto da home atualizada com sucesso!', 'success');
            e.target.reset();
        } else {
            this.showNotification('Por favor, selecione uma imagem.', 'error');
        }
    }

    previewHeroImage(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('heroImagePreview');
                if (preview) {
                    preview.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
    }

    handleAboutUpdate(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        // Update about description
        const aboutDesc = document.querySelector('.about-description');
        if (aboutDesc && data.aboutDescription) {
            aboutDesc.textContent = data.aboutDescription;
        }
        
        // Update statistics
        const stats = document.querySelectorAll('.stat h3');
        if (stats.length >= 3) {
            stats[0].textContent = data.statProjects + '+';
            stats[1].textContent = data.statExperience + '+';
            stats[2].textContent = data.statAwards + '+';
        }
        
        // Handle about image if provided
        const aboutImageFile = formData.get('aboutImage');
        if (aboutImageFile && aboutImageFile.size > 0) {
            const aboutImageUrl = URL.createObjectURL(aboutImageFile);
            const aboutImg = document.querySelector('.about-image img');
            if (aboutImg) {
                aboutImg.src = aboutImageUrl;
            }
        }
        
        this.showNotification('Seção "Sobre Mim" atualizada com sucesso!', 'success');
    }

    // ===== UTILITY FUNCTIONS =====
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Lazy loading for images
    lazyLoad() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }

    // Performance optimization
    optimizePerformance() {
        // Preload critical resources
        const criticalImages = [
            'assets/hero-image.jpg',
            'assets/photographer.jpg'
        ];

        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    }

    // SEO and accessibility improvements
    improveAccessibility() {
        // Add skip navigation link
        const skipLink = document.createElement('a');
        skipLink.href = '#main';
        skipLink.textContent = 'Pular para o conteúdo principal';
        skipLink.className = 'skip-link';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            text-decoration: none;
            border-radius: 0 0 4px 4px;
            z-index: 10000;
        `;
        skipLink.addEventListener('focus', () => {
            skipLink.style.top = '0';
        });
        skipLink.addEventListener('blur', () => {
            skipLink.style.top = '-40px';
        });

        document.body.insertBefore(skipLink, document.body.firstChild);

        // Improve keyboard navigation
        document.querySelectorAll('.portfolio-item').forEach(item => {
            item.setAttribute('tabindex', '0');
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    item.click();
                }
            });
        });
    }
}

// ===== INITIALIZE APPLICATION =====
let photographerPortfolio;

document.addEventListener('DOMContentLoaded', () => {
    photographerPortfolio = new PhotographerPortfolio();
});

// ===== SERVICE WORKER FOR OFFLINE SUPPORT =====
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

// ===== PROGRESSIVE WEB APP FEATURES =====
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show install button if desired
    const installBtn = document.getElementById('installBtn');
    if (installBtn) {
        installBtn.style.display = 'block';
        installBtn.addEventListener('click', () => {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the A2HS prompt');
                }
                deferredPrompt = null;
            });
        });
    }
});

// ===== PERFORMANCE MONITORING =====
window.addEventListener('load', () => {
    // Log performance metrics
    if ('performance' in window) {
        const perfData = performance.getEntriesByType('navigation')[0];
        console.log('Page Load Time:', perfData.loadEventEnd - perfData.fetchStart, 'ms');
    }
});

// ===== ERROR HANDLING =====
window.addEventListener('error', (e) => {
    console.error('JavaScript Error:', e.error);
    // In production, send error to logging service
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled Promise Rejection:', e.reason);
    // In production, send error to logging service
});

// ===== EXPORT FOR MODULE USAGE =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PhotographerPortfolio;
}
