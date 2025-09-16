// ===== ALPINE.JS COMPONENTS (resources/js/components.js) =====

// Campaign Filter Component
function campaignFilter() {
    return {
        activeFilter: 'all',
        campaigns: [
            {
                id: 1,
                title: 'Education for All Children',
                slug: 'education-for-all',
                description: 'Providing school supplies and educational resources to underprivileged children in Khayelitsha townships.',
                category: 'education',
                raised: 125000,
                target: 200000,
                color: '#3b82f6',
                colorLight: '#60a5fa'
            },
            {
                id: 2,
                title: 'Clean Water Initiative',
                slug: 'clean-water-initiative',
                description: 'Installing water purification systems in rural communities around Cape Town.',
                category: 'health',
                raised: 89000,
                target: 150000,
                color: '#06b6d4',
                colorLight: '#22d3ee'
            },
            {
                id: 3,
                title: 'Urban Garden Project',
                slug: 'urban-garden-project',
                description: 'Creating sustainable community gardens to combat food insecurity and promote healthy eating.',
                category: 'environment',
                raised: 45000,
                target: 75000,
                color: '#10b981',
                colorLight: '#34d399'
            },
            {
                id: 4,
                title: 'Senior Care Support',
                slug: 'senior-care-support',
                description: 'Providing healthcare and social support for elderly residents in low-income areas.',
                category: 'community',
                raised: 67000,
                target: 100000,
                color: '#8b5cf6',
                colorLight: '#a78bfa'
            },
            {
                id: 5,
                title: 'Youth Skills Training',
                slug: 'youth-skills-training',
                description: 'Empowering young adults with digital skills and entrepreneurship training programs.',
                category: 'education',
                raised: 78000,
                target: 120000,
                color: '#f59e0b',
                colorLight: '#fbbf24'
            },
            {
                id: 6,
                title: 'Ocean Cleanup Cape Town',
                slug: 'ocean-cleanup',
                description: 'Regular beach cleanups and marine conservation efforts along the Cape Peninsula.',
                category: 'environment',
                raised: 23000,
                target: 50000,
                color: '#06b6d4',
                colorLight: '#22d3ee'
            }
        ],
        get filteredCampaigns() {
            if (this.activeFilter === 'all') {
                return this.campaigns;
            }
            return this.campaigns.filter(campaign => campaign.category === this.activeFilter);
        }
    }
}

// Donation Form Component
function donationForm() {
    return {
        showModal: false,
        selectedAmount: null,
        customAmount: '',
        donorName: '',
        donorEmail: '',
        paymentMethod: 'yoco',
        agreedToTerms: false,
        loading: false,
        predefinedAmounts: [50, 100, 250, 500, 1000, 2500],

        getDonationAmount() {
            return this.customAmount || this.selectedAmount || 0;
        },

        async submitDonation() {
            if (!this.getDonationAmount() || !this.donorName || !this.donorEmail || !this.agreedToTerms) {
                alert('Please fill in all required fields');
                return;
            }

            this.loading = true;

            // Simulate API call
            setTimeout(() => {
                alert('Thank you for your donation! You will receive a receipt via email.');
                this.loading = false;
                this.showModal = false;
                this.resetForm();
            }, 2000);
        },

        resetForm() {
            this.selectedAmount = null;
            this.customAmount = '';
            this.donorName = '';
            this.donorEmail = '';
            this.paymentMethod = 'yoco';
            this.agreedToTerms = false;
        }
    }
}

// Events Component
function eventsData() {
    return {
        events: [
            {
                id: 1,
                title: 'Community Kitchen Volunteer Day',
                date: '2025-09-22',
                location: 'Mitchell\'s Plain Community Center',
                description: 'Help prepare and serve meals for families in need. No experience required!',
                capacity: 20,
                spotsLeft: 7
            },
            {
                id: 2,
                title: 'Beach Cleanup & Marine Conservation',
                date: '2025-09-25',
                location: 'Muizenberg Beach',
                description: 'Join us for a morning of environmental action along Cape Town\'s beautiful coastline.',
                capacity: 50,
                spotsLeft: 23
            },
            {
                id: 3,
                title: 'Reading Program for Children',
                date: '2025-09-28',
                location: 'Langa Library',
                description: 'Share the joy of reading with local children and help improve literacy rates.',
                capacity: 15,
                spotsLeft: 0
            },
            {
                id: 4,
                title: 'Urban Garden Workshop',
                date: '2025-10-02',
                location: 'Woodstock Community Garden',
                description: 'Learn sustainable gardening techniques while helping establish community food gardens.',
                capacity: 25,
                spotsLeft: 12
            },
            {
                id: 5,
                title: 'Senior Citizens Tech Support',
                date: '2025-10-05',
                location: 'Athlone Senior Center',
                description: 'Help elderly community members learn basic computer and smartphone skills.',
                capacity: 10,
                spotsLeft: 4
            },
            {
                id: 6,
                title: 'Youth Mentorship Program',
                date: '2025-10-08',
                location: 'Gugulethu Youth Center',
                description: 'Mentor young adults in career planning and life skills development.',
                capacity: 30,
                spotsLeft: 18
            }
        ],

        formatDate(dateString) {
            const date = new Date(dateString);
            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            return date.toLocaleDateString('en-ZA', options);
        },

        signUpForEvent(event) {
            if (event.spotsLeft === 0) return;

            if (confirm(`Sign up for "${event.title}"? You'll receive confirmation details via email.`)) {
                event.spotsLeft--;
                alert('Thank you for signing up! You\'ll receive confirmation details via email.');
            }
        }
    }
}

// Global donation modal trigger
window.showDonationModal = function () {
    // This would be called from campaign cards
    Alpine.store('donationModal').show = true;
}