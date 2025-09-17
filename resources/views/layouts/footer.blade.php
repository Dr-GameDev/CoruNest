<!-- ===== FOOTER (resources/views/layouts/footer.blade.php) ===== -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4">CoruNest</h3>
                    <p class="text-gray-400 mb-4">
                        Empowering Cape Town NGOs with transparent, efficient donation and volunteer management.
                    </p>
                    <p class="text-sm text-gray-500">
                        Built with ❤️ for social impact organizations.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('campaigns.index') }}" class="text-gray-400 hover:text-white">Browse Campaigns</a></li>
                        <li><a href="{{ route('events.index') }}" class="text-gray-400 hover:text-white">Volunteer Events</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">POPIA Compliance</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} CoruNest. All rights reserved.</p>
            </div>
        </div>
    </footer>