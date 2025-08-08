<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Events\BookingCreated;
use App\Events\StatusUpdated;
use Illuminate\Support\Facades\Auth;


class AppointmentController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        
        // Start with base query
        $query = Appointment::query()->with(['employee.user', 'service', 'user']);

        // Only admins can see all appointments
        if (!(method_exists($user, 'hasRole') && $user->hasRole('admin'))) {
            $query->where(function($q) use ($user) {
                if ($user->employee) {
                    $q->where('employee_id', $user->employee->id);
                }
                $q->orWhere('user_id', $user->id);
            });
        }

        // Apply filters (status, date range) from request
        $this->applyAppointmentFilters($query, $request);

        $appointments = $query->latest()->get();
        
        // Pass current filters back to the view for UI state
        $activeFilters = [
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        return view('backend.appointment.index', compact('appointments', 'activeFilters'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'employee_id' => 'required|exists:employees,id',
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'student_id' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string',
            'booking_date' => 'required|date',
            'booking_time' => 'required',
            'status' => 'required|string',
        ]);

            // Set user_id if not provided but user is authenticated
        // if (auth()->check() && !$request->has('user_id')) {
        //     $validated['user_id'] = auth()->id();
        // }

        $isPrivilegedRole = false;
        if (Auth::check()) {
            $authUser = Auth::user();
            /** @var \App\Models\User $authUser */
            if (method_exists($authUser, 'hasAnyRole')) {
                $isPrivilegedRole = $authUser->hasAnyRole(['admin', 'moderator', 'employee']);
            }
        }

            // If admin/moderator/employee is booking, user_id should be null
        if ($isPrivilegedRole) {
            $validated['user_id'] = null;
        } elseif (Auth::check() && !$request->has('user_id')) {
            // Otherwise, assign user_id to the authenticated user
            $validated['user_id'] = Auth::id();
        }


        // Generate unique booking ID
        $validated['booking_id'] = 'BK-' . strtoupper(uniqid());


        $appointment = Appointment::create($validated);

        event(new BookingCreated($appointment));

        return response()->json([
            'success' => true,
            'message' => 'Appointment booked successfully!',
            'booking_id' => $appointment->booking_id,
            'appointment' => $appointment
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Appointment $appointment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        //
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'status' => 'required|string',
        ]);

        $user = Auth::user();
        /** @var \App\Models\User $user */
        $appointment = Appointment::findOrFail($request->appointment_id);
        
        // Check if user can update this appointment
        if (!(method_exists($user, 'hasRole') && $user->hasRole('admin')) && $appointment->employee_id !== $user->employee?->id) {
            return redirect()->back()->with('error', 'You can only update your own appointments.');
        }
        
        $appointment->status = $request->status;
        $appointment->save();

        event(new StatusUpdated($appointment));

        return redirect()->back()->with('success', 'Appointment status updated successfully.');
    }

    /**
     * Apply query filters for appointments (status and date range)
     */
    private function applyAppointmentFilters($query, Request $request): void
    {
        $allowedStatuses = [
            'Processing', 'Confirmed', 'Cancelled', 'Completed', 'On Hold', 'No Show'
        ];

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if (in_array($status, $allowedStatuses, true)) {
                $query->where('status', $status);
            }
        }

        // Filter by date range (booking_date)
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('booking_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->whereDate('booking_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->whereDate('booking_date', '<=', $dateTo);
        }
    }
}
