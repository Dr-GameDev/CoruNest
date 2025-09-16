<!-- ===== CAMPAIGNS SECTION (resources/views/campaigns/index.blade.php) ===== -->
    <section class="campaigns-section" x-data="campaignFilter()">
        <div class="section-header">
            <h2 class="section-title">Active Campaigns</h2>
            <p class="section-subtitle">Support meaningful causes making a real difference in Cape Town communities</p>
        </div>

        <div class="campaigns-filter">
            <button class="filter-btn" :class="{ 'active': activeFilter === 'all' }" 
                    @click="activeFilter = 'all'">All Campaigns</button>
            <button class="filter-btn" :class="{ 'active': activeFilter === 'education' }" 
                    @click="activeFilter = 'education'">Education</button>
            <button class="filter-btn" :class="{ 'active': activeFilter === 'health' }" 
                    @click="activeFilter = 'health'">Health</button>
            <button class="filter-btn" :class="{ 'active': activeFilter === 'environment' }" 
                    @click="activeFilter = 'environment'">Environment</button>
            <button class="filter-btn" :class="{ 'active': activeFilter === 'community' }" 
                    @click="activeFilter = 'community'">Community</button>
        </div>

        <div class="campaigns-grid">
            <template x-for="campaign in filteredCampaigns" :key="campaign.id">
                <div class="campaign-card fade-in-up">
                    <div class="campaign-image" :style="'background: linear-gradient(45deg, ' + campaign.color + ', ' + campaign.colorLight + ')'">
                    </div>
                    <div class="campaign-content">
                        <h3 class="campaign-title" x-text="campaign.title"></h3>
                        <p class="campaign-description" x-text="campaign.description"></p>
                        
                        <div class="campaign-progress">
                            <div class="progress-info">
                                <span class="raised-amount" x-text="'R' + campaign.raised.toLocaleString()"></span>
                                <span class="target-amount" x-text="'of R' + campaign.target.toLocaleString()"></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" :style="'width: ' + (campaign.raised / campaign.target * 100) + '%'"></div>
                            </div>
                            <div class="mt-2" style="font-size: 0.875rem; color: var(--gray-500);">
                                <span x-text="Math.round(campaign.raised / campaign.target * 100)"></span>% funded
                            </div>
                        </div>

                        <div class="campaign-actions">
                            <a :href="'/campaigns/' + campaign.slug" class="btn btn-primary" style="flex: 1; justify-content: center;">
                                <i class="fas fa-heart"></i>
                                Donate Now
                            </a>
                            <a :href="'/campaigns/' + campaign.slug" class="btn btn-outline">
                                <i class="fas fa-info-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </section>