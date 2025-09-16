<!-- ===== NAVIGATION (resources/views/layouts/navigation.blade.php) ===== -->
    <nav class="navbar" x-data="{ scrolled: false }" 
         x-init="window.addEventListener('scroll', () => scrolled = window.pageYOffset > 50)"
         :class="{ 'scrolled': scrolled }">
        <div class="nav-container">
            <a href="/" class="logo">
                <i class="fas fa-heart"></i> CoruNest
            </a>
            
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/campaigns">Campaigns</a></li>
                <li><a href="/events">Events</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="/contact">Contact</a></li>
                <li><a href="/login" class="btn btn-outline">Login</a></li>
            </ul>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>