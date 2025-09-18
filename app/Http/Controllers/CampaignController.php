<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    /**
     * Display the homepage with featured campaigns.
     */
    public function index(): View
    {
        $featuredCampaigns = Campaign::active()
            ->featured()
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        $recentCampaigns = Campaign::active()
            ->with(['creator'])
            ->latest()
            ->limit(6)
            ->get();

        $stats = [
            'total_campaigns' => Campaign::where('status', '!=', 'draft')->count(),
            'total_raised' => Campaign::sum('current_amount'),
            'total_donors' => Campaign::sum('donor_count'),
            'active_campaigns' => Campaign::active()->count(),
        ];

        return view('campaigns.index', compact(
            'featuredCampaigns',
            'recentCampaigns',
            'stats'
        ));
    }

    /**
     * Display a listing of all campaigns with filters.
     */
    public function list(Request $request): View
    {
        $query = Campaign::active()->with(['creator']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Category filter
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Sort options
        $sort = $request->get('sort', 'latest');
        match($sort) {
            'ending_soon' => $query->endingSoon(),
            'progress' => $query->orderByProgress(),
            'featured' => $query->featured()->latest(),
            default => $query->latest(),
        };

        $campaigns = $query->paginate(12)->withQueryString();

        $categories = Campaign::getCategories();
        
        $filters = [
            'search' => $request->search,
            'category' => $request->category,
            'sort' => $sort,
        ];

        return view('campaigns.list', compact(
            'campaigns',
            'categories',
            'filters'
        ));
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): View
    {
        // Only show active campaigns to public or drafts to admin
        if ($campaign->status === 'draft' && !auth()->user()?->canManageCampaigns()) {
            abort(404);
        }

        if ($campaign->status === 'archived') {
            abort(404, 'This campaign is no longer active.');
        }

        $campaign->load(['creator', 'donations' => function($query) {
            $query->completed()
                ->where('anonymous', false)
                ->latest()
                ->limit(10);
        }]);

        // Related campaigns
        $relatedCampaigns = Campaign::active()
            ->where('id', '!=', $campaign->id)
            ->where('category', $campaign->category)
            ->limit(3)
            ->get();

        $recentDonations = $campaign->donations()
            ->completed()
            ->where('anonymous', false)
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        return view('campaigns.show', compact(
            'campaign',
            'relatedCampaigns',
            'recentDonations'
        ));
    }

    /**
     * Show donation form for a campaign.
     */
    public function donate(Campaign $campaign): View
    {
        if ($campaign->status !== 'active' || !$campaign->is_active) {
            abort(404, 'This campaign is not accepting donations.');
        }

        $suggestedAmounts = [50, 100, 250, 500, 1000];
        
        return view('campaigns.donate', compact(
            'campaign',
            'suggestedAmounts'
        ));
    }

    /**
     * Get campaigns by category for AJAX requests.
     */
    public function byCategory(Request $request)
    {
        $category = $request->get('category');
        
        $campaigns = Campaign::active()
            ->when($category, fn($query) => $query->byCategory($category))
            ->with(['creator'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'campaigns' => $campaigns->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'summary' => $campaign->summary,
                    'target_amount' => $campaign->target_amount,
                    'current_amount' => $campaign->current_amount,
                    'progress_percentage' => $campaign->progress_percentage,
                    'donor_count' => $campaign->donor_count,
                    'days_remaining' => $campaign->days_remaining,
                    'main_image' => $campaign->main_image,
                    'category' => $campaign->category,
                    'creator' => $campaign->creator->name ?? 'CoruNest',
                    'url' => route('campaigns.show', $campaign),
                    'donate_url' => route('campaigns.donate', $campaign),
                ];
            }),
        ]);
    }

    /**
     * Search campaigns for autocomplete.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $campaigns = Campaign::active()
            ->search($query)
            ->select('id', 'title', 'slug', 'summary', 'category')
            ->limit(5)
            ->get();

        return response()->json([
            'campaigns' => $campaigns->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'summary' => Str::limit($campaign->summary, 100),
                    'category' => $campaign->category,
                    'url' => route('campaigns.show', $campaign),
                ];
            }),
        ]);
    }
}