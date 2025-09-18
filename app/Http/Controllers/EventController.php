<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /**
     * Display a listing of upcoming events.
     */
    public function index(Request $request): View
    {
        $query = Event::upcoming()->with(['creator']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Category filter
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Location filter
        if ($request->filled('location')) {
            $query->byLocation($request->location);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('starts_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('starts_at', '<=', $request->date_to . ' 23:59:59');
        }

        $events = $query->paginate(12)->withQueryString();

        $categories = Event::getCategories();
        
        $filters = [
            'search' => $request->search,
            'category' => $request->category,
            'location' => $request->location,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        // Get unique locations for filter dropdown
        $locations = Event::upcoming()
            ->select('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        return view('events.index', compact(
            'events',
            'categories',
            'locations',
            'filters'
        ));
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event): View
    {
        // Only show active events to public or drafts to admin
        if ($event->status === 'draft' && !auth()->user()?->canManageEvents()) {
            abort(404);
        }

        if ($event->status === 'cancelled') {
            abort(404, 'This event has been cancelled.');
        }

        $event->load(['creator', 'volunteers' => function($query) {
            $query->confirmed()
                ->with('user')
                ->latest()
                ->limit(10);
        }]);

        // Check if current user has already volunteered
        $userVolunteered = false;
        $userVolunteerStatus = null;
        
        if (auth()->check()) {
            $userVolunteer = $event->volunteers()
                ->where('user_id', auth()->id())
                ->first();
                
            if ($userVolunteer) {
                $userVolunteered = true;
                $userVolunteerStatus = $userVolunteer->status;
            }
        }

        // Related events
        $relatedEvents = Event::upcoming()
            ->where('id', '!=', $event->id)
            ->where('category', $event->category)
            ->limit(3)
            ->get();

        $confirmedVolunteers = $event->volunteers()
            ->confirmed()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        return view('events.show', compact(
            'event',
            'relatedEvents',
            'confirmedVolunteers',
            'userVolunteered',
            'userVolunteerStatus'
        ));
    }

    /**
     * Show volunteer signup form for an event.
     */
    public function volunteer(Event $event): View
    {
        if (!$event->signup_open) {
            abort(404, 'Volunteer signup is no longer available for this event.');
        }

        // Check if user already volunteered
        if (auth()->check() && $event->hasUserVolunteered(auth()->id())) {
            return redirect()->route('events.show', $event)
                ->with('info', 'You have already signed up for this event.');
        }

        $availableSkills = Volunteer::getAvailableSkills();

        return view('events.volunteer', compact('event', 'availableSkills'));
    }

    /**
     * Process volunteer signup.
     */
    public function storeVolunteer(Request $request, Event $event): RedirectResponse
    {
        if (!$event->signup_open) {
            throw ValidationException::withMessages([
                'event' => 'Volunteer signup is no longer available for this event.'
            ]);
        }

        // Check if user already volunteered
        if (auth()->check() && $event->hasUserVolunteered(auth()->id())) {
            return redirect()->route('events.show', $event)
                ->with('info', 'You have already signed up for this event.');
        }

        $validated = $request->validate([
            'volunteer_name' => auth()->check() ? 'nullable' : 'required|string|max:255',
            'volunteer_email' => auth()->check() ? 'nullable' : 'required|email|max:255',
            'volunteer_phone' => 'nullable|string|max:20',
            'skills' => 'array',
            'skills.*' => 'string|in:' . implode(',', array_keys(Volunteer::getAvailableSkills())),
            'message' => 'nullable|string|max:500',
            'has_transport' => 'boolean',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        // Additional validation for emergency contact
        if ($request->filled('emergency_contact_name') && !$request->filled('emergency_contact_phone')) {
            throw ValidationException::withMessages([
                'emergency_contact_phone' => 'Emergency contact phone is required when providing emergency contact name.'
            ]);
        }

        try {
            DB::beginTransaction();

            // Check capacity limit
            if ($event->signup_limit && $event->volunteer_count >= $event->signup_limit) {
                throw ValidationException::withMessages([
                    'event' => 'This event has reached its volunteer capacity.'
                ]);
            }

            $volunteer = Volunteer::create([
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'status' => 'pending',
                'volunteer_name' => $validated['volunteer_name'] ?? null,
                'volunteer_email' => $validated['volunteer_email'] ?? auth()->user()?->email,
                'volunteer_phone' => $validated['volunteer_phone'] ?? auth()->user()?->phone,
                'skills' => $validated['skills'] ?? [],
                'message' => $validated['message'] ?? null,
                'has_transport' => $validated['has_transport'] ?? false,
                'emergency_contact_provided' => $request->filled('emergency_contact_name'),
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            ]);

            DB::commit();

            // TODO: Send confirmation email to volunteer
            // TODO: Send notification to event organizers

            return redirect()->route('events.show', $event)
                ->with('success', 'Thank you for volunteering! We will contact you soon to confirm your participation.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Volunteer signup failed', [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
                'user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['volunteer' => 'An error occurred while processing your signup. Please try again.']);
        }
    }

    /**
     * Cancel volunteer signup.
     */
    public function cancelVolunteer(Event $event): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $volunteer = $event->volunteers()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if (!$volunteer) {
            return redirect()->route('events.show', $event)
                ->with('error', 'No volunteer signup found to cancel.');
        }

        if (!$volunteer->canBeCancelled()) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Cannot cancel volunteer signup at this time.');
        }

        $volunteer->cancel();

        return redirect()->route('events.show', $event)
            ->with('success', 'Your volunteer signup has been cancelled.');
    }

    /**
     * Get events by category for AJAX requests.
     */
    public function byCategory(Request $request)
    {
        $category = $request->get('category');
        
        $events = Event::upcoming()
            ->when($category, fn($query) => $query->byCategory($category))
            ->with(['creator'])
            ->limit(10)
            ->get();

        return response()->json([
            'events' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'description' => $event->description,
                    'location' => $event->location,
                    'starts_at' => $event->starts_at->format('Y-m-d H:i:s'),
                    'ends_at' => $event->ends_at->format('Y-m-d H:i:s'),
                    'formatted_date_range' => $event->formatted_date_range,
                    'volunteer_count' => $event->volunteer_count,
                    'capacity' => $event->capacity,
                    'remaining_slots' => $event->remaining_slot,
                    'signup_open' => $event->signup_open,
                    'main_image' => $event->main_image,
                    'category' => $event->category,
                    'creator' => $event->creator->name ?? 'CoruNest',
                    'url' => route('events.show', $event),
                    'volunteer_url' => route('events.volunteer', $event),
                ];
            }),
        ]);
    }

    /**
     * Get user's volunteer history.
     */
    public function volunteerHistory(Request $request): View
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $volunteers = auth()->user()
            ->volunteers()
            ->with(['event'])
            ->latest()
            ->paginate(10);

        $stats = [
            'total_volunteered' => auth()->user()->volunteer_count,
            'upcoming_events' => auth()->user()
                ->volunteers()
                ->whereHas('event', fn($query) => $query->upcoming())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'completed_events' => auth()->user()
                ->volunteers()
                ->where('status', 'completed')
                ->count(),
        ];

        return view('volunteers.history', compact('volunteers', 'stats'));
    }
}