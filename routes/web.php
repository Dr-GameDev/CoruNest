<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\EventController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Homepage - redirect to campaigns for now
Route::get('/', [CampaignController::class, 'index'])->name('home');

// Public Campaign Routes
Route::prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/', [CampaignController::class, 'index'])->name('index');
    Route::get('/list', [CampaignController::class, 'list'])->name('list');
    Route::get('/category/{category?}', [CampaignController::class, 'byCategory'])->name('by-category');
    Route::get('/search', [CampaignController::class, 'search'])->name('search');
    Route::get('/{campaign:slug}', [CampaignController::class, 'show'])->name('show');
    Route::get('/{campaign:slug}/donate', [CampaignController::class, 'donate'])->name('donate');
});

// Public Event Routes
Route::prefix('events')->name('events.')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/category/{category?}', [EventController::class, 'byCategory'])->name('by-category');
    Route::get('/{event:slug}', [EventController::class, 'show'])->name('show');
    Route::get('/{event:slug}/volunteer', [EventController::class, 'volunteer'])->name('volunteer');
    Route::post('/{event:slug}/volunteer', [EventController::class, 'storeVolunteer'])->name('volunteer.store');
    Route::delete('/{event:slug}/volunteer', [EventController::class, 'cancelVolunteer'])->name('volunteer.cancel')->middleware('auth');
});

// Donation Routes
Route::prefix('donations')->name('donations.')->group(function () {
    Route::post('/', [DonationController::class, 'store'])->name('store');
    Route::get('/{donation}/success', [DonationController::class, 'success'])->name('success');
    Route::get('/{donation}/failure', [DonationController::class, 'failure'])->name('failure');
    Route::get('/{donation}/receipt', [DonationController::class, 'receipt'])->name('receipt')->middleware('auth');
    Route::get('/{donation}/receipt/download', [DonationController::class, 'downloadReceipt'])->name('receipt.download')->middleware('auth');
    Route::get('/{donation}/status', [DonationController::class, 'status'])->name('status');
    Route::delete('/{donation}/cancel', [DonationController::class, 'cancel'])->name('cancel')->middleware('auth');
    Route::get('/history', [DonationController::class, 'history'])->name('history')->middleware('auth');
    Route::get('/campaign/{campaign}/recent', [DonationController::class, 'recentForCampaign'])->name('campaign.recent');
});

// Payment Webhooks (outside auth middleware)
Route::post('/webhooks/payments/{provider}', [DonationController::class, 'webhook'])->name('donation.webhook');

// Volunteer Routes
Route::prefix('volunteers')->name('volunteers.')->middleware('auth')->group(function () {
    Route::get('/history', [EventController::class, 'volunteerHistory'])->name('history');
});

// Dashboard Routes (Inertia.js React Admin)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes (React Dashboard)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin'])->group(function () {
    
    // Admin Dashboard
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // Campaign Management
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Campaigns/Index');
        })->name('index');
        Route::get('/create', function () {
            return Inertia::render('Admin/Campaigns/Create');
        })->name('create');
        Route::get('/{campaign}/edit', function () {
            return Inertia::render('Admin/Campaigns/Edit');
        })->name('edit');
        // API routes for campaign CRUD will be added separately
    });

    // Event Management
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Events/Index');
        })->name('index');
        Route::get('/create', function () {
            return Inertia::render('Admin/Events/Create');
        })->name('create');
        Route::get('/{event}/edit', function () {
            return Inertia::render('Admin/Events/Edit');
        })->name('edit');
    });

    // Donation Management
    Route::prefix('donations')->name('donations.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Donations/Index');
        })->name('index');
        Route::get('/{donation}', function () {
            return Inertia::render('Admin/Donations/Show');
        })->name('show');
    });

    // Volunteer Management
    Route::prefix('volunteers')->name('volunteers.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Volunteers/Index');
        })->name('index');
        Route::get('/{volunteer}', function () {
            return Inertia::render('Admin/Volunteers/Show');
        })->name('show');
    });

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Users/Index');
        })->name('index');
        Route::get('/{user}', function () {
            return Inertia::render('Admin/Users/Show');
        })->name('show');
    });

    // Analytics
    Route::get('/analytics', function () {
        return Inertia::render('Admin/Analytics');
    })->name('analytics');

    // Settings
    Route::get('/settings', function () {
        return Inertia::render('Admin/Settings');
    })->name('settings');

    // Audit Logs
    Route::get('/audit-logs', function () {
        return Inertia::render('Admin/AuditLogs');
    })->name('audit-logs');

    // Bulk Email
    Route::prefix('emails')->name('emails.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Emails/Index');
        })->name('index');
        Route::get('/compose', function () {
            return Inertia::render('Admin/Emails/Compose');
        })->name('compose');
    });
});

// API Routes for Admin Dashboard (will be consumed by React components)
Route::prefix('api/admin')->name('api.admin.')->middleware(['auth', 'can:access-admin'])->group(function () {
    
    // Dashboard Stats
    Route::get('/stats', function () {
        $stats = [
            'total_campaigns' => \App\Models\Campaign::count(),
            'active_campaigns' => \App\Models\Campaign::active()->count(),
            'total_donations' => \App\Models\Donation::completed()->count(),
            'total_raised' => \App\Models\Donation::completed()->sum('amount'),
            'total_events' => \App\Models\Event::count(),
            'upcoming_events' => \App\Models\Event::upcoming()->count(),
            'total_volunteers' => \App\Models\Volunteer::whereIn('status', ['confirmed', 'completed'])->count(),
            'total_users' => \App\Models\User::count(),
        ];

        return response()->json($stats);
    });

    // Campaign API
    Route::apiResource('campaigns', \App\Http\Controllers\Admin\CampaignController::class);
    
    // Event API
    Route::apiResource('events', \App\Http\Controllers\Admin\EventController::class);
    
    // Donation API
    Route::apiResource('donations', \App\Http\Controllers\Admin\DonationController::class);
    Route::post('donations/{donation}/refund', [\App\Http\Controllers\Admin\DonationController::class, 'refund']);
    Route::get('donations/export', [\App\Http\Controllers\Admin\DonationController::class, 'export']);
    
    // Volunteer API
    Route::apiResource('volunteers', \App\Http\Controllers\Admin\VolunteerController::class);
    Route::post('volunteers/{volunteer}/confirm', [\App\Http\Controllers\Admin\VolunteerController::class, 'confirm']);
    Route::post('volunteers/{volunteer}/complete', [\App\Http\Controllers\Admin\VolunteerController::class, 'complete']);
    Route::get('volunteers/export', [\App\Http\Controllers\Admin\VolunteerController::class, 'export']);
    
    // User API
    Route::apiResource('users', \App\Http\Controllers\Admin\UserController::class);
    
    // Analytics API
    Route::prefix('analytics')->group(function () {
        Route::get('/donations-over-time', [\App\Http\Controllers\Admin\AnalyticsController::class, 'donationsOverTime']);
        Route::get('/top-campaigns', [\App\Http\Controllers\Admin\AnalyticsController::class, 'topCampaigns']);
        Route::get('/volunteer-participation', [\App\Http\Controllers\Admin\AnalyticsController::class, 'volunteerParticipation']);
        Route::get('/donor-demographics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'donorDemographics']);
    });
    
    // Bulk Email API
    Route::prefix('emails')->group(function () {
        Route::post('/send-bulk', [\App\Http\Controllers\Admin\EmailController::class, 'sendBulk']);
        Route::get('/templates', [\App\Http\Controllers\Admin\EmailController::class, 'templates']);
        Route::get('/recipients/{type}', [\App\Http\Controllers\Admin\EmailController::class, 'getRecipients']);
    });
    
    // Settings API
    Route::prefix('settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\SettingsController::class, 'update']);
    });
    
    // Audit Logs API
    Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index']);
});

// Static Pages
Route::view('/about', 'pages.about')->name('about');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/offline', 'pages.offline')->name('offline');

// Sitemap and SEO
Route::get('/sitemap.xml', function () {
    $campaigns = \App\Models\Campaign::active()
        ->select('slug', 'updated_at')
        ->get();
    
    $events = \App\Models\Event::upcoming()
        ->select('slug', 'updated_at')
        ->get();

    return response()->view('sitemap', compact('campaigns', 'events'))
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

// Robots.txt
Route::get('/robots.txt', function () {
    $robots = "User-agent: *\n";
    $robots .= "Disallow: /admin\n";
    $robots .= "Disallow: /api\n";
    $robots .= "Disallow: /dashboard\n";
    $robots .= "Allow: /\n";
    $robots .= "Sitemap: " . url('/sitemap.xml') . "\n";

    return response($robots)->header('Content-Type', 'text/plain');
})->name('robots');

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'app' => [
            'name' => config('app.name'),
            'version' => '1.0.0',
            'environment' => app()->environment(),
        ],
        'database' => [
            'connected' => \DB::connection()->getPdo() ? true : false,
        ],
        'cache' => [
            'working' => \Cache::has('health-check') || \Cache::put('health-check', true, 60),
        ],
    ]);
})->name('health');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
