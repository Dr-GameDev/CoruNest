<!-- ===== EVENTS SECTION (resources/views/events/index.blade.php) ===== -->
    <section class="events-section" x-data="eventsData()">
        <div class="section-header">
            <h2 class="section-title">Volunteer Opportunities</h2>
            <p class="section-subtitle">Join hands with local NGOs and make a direct impact in your community</p>
        </div>

        <div class="events-grid">
            <template x-for="event in events" :key="event.id">
                <div class="event-card fade-in-up">
                    <div class="event-date" x-text="formatDate(event.date)"></div>
                    <h3 class="event-title" x-text="event.title"></h3>
                    <div class="event-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span x-text="event.location"></span>
                    </div>
                    <p class="campaign-description" x-text="event.description"></p>
                    
                    <div class="volunteer-spots">
                        <span class="spots-left" x-text="event.spotsLeft + ' spots left'"></span>
                        <span class="text-gray-500" x-text="'of ' + event.capacity"></span>
                    </div>

                    <div class="progress-bar mb-4">
                        <div class="progress-fill" :style="'width: ' + ((event.capacity - event.spotsLeft) / event.capacity * 100) + '%'"></div>
                    </div>

                    <button @click="signUpForEvent(event)" 
                            class="btn btn-primary w-full"
                            :disabled="event.spotsLeft === 0">
                        <i class="fas fa-hand-paper"></i>
                        <span x-text="event.spotsLeft > 0 ? 'Sign Up to Volunteer' : 'Event Full'"></span>
                    </button>
                </div>
            </template>
        </div>
    </section>