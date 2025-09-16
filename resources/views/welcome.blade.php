<!-- ===== HERO SECTION (resources/views/welcome.blade.php) ===== -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="fade-in-up">Organise. Fund. Mobilise.</h1>
            <p class="fade-in-up">Empowering NGOs in Cape Town to create lasting change through transparent fundraising and volunteer coordination.</p>
            
            <div class="hero-actions fade-in-up">
                <a href="/campaigns" class="btn btn-primary">
                    <i class="fas fa-rocket"></i>
                    Explore Campaigns
                </a>
                <a href="/events" class="btn btn-outline">
                    <i class="fas fa-users"></i>
                    Join as Volunteer
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number" x-data="{ count: 0 }" x-init="setInterval(() => count < 47 && count++, 50)">
                    R<span x-text="count"></span>M
                </span>
                <div class="stat-label">Total Raised</div>
            </div>
            <div class="stat-card">
                <span class="stat-number" x-data="{ count: 0 }" x-init="setInterval(() => count < 156 && count++, 30)">
                    <span x-text="count"></span>
                </span>
                <div class="stat-label">Active Campaigns</div>
            </div>
            <div class="stat-card">
                <span class="stat-number" x-data="{ count: 0 }" x-init="setInterval(() => count < 2840 && count++, 1)">
                    <span x-text="count"></span>
                </span>
                <div class="stat-label">Volunteers</div>
            </div>
            <div class="stat-card">
                <span class="stat-number" x-data="{ count: 0 }" x-init="setInterval(() => count < 89 && count++, 40)">
                    <span x-text="count"></span>
                </span>
                <div class="stat-label">NGO Partners</div>
            </div>
        </div>
    </section>