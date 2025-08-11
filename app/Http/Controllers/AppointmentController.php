<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Events\BookingCreated;
use App\Events\StatusUpdated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use App\Models\Category;
use App\Models\Service;


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
                    $q->where('appointments.employee_id', $user->employee->id);
                }
                $q->orWhere('appointments.user_id', $user->id);
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

        // Server-side sanitization for additional security
        $validated['name'] = $this->sanitizeName($validated['name']);
        $validated['email'] = $this->sanitizeEmail($validated['email']);
        $validated['student_id'] = $this->sanitizeStudentId($validated['student_id']);
        $validated['phone'] = $this->sanitizePhone($validated['phone']);
        $validated['notes'] = $this->sanitizeNotes($validated['notes']);

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

        // Parse requested time range
        [$requestedStartStr, $requestedEndStr] = array_map('trim', explode('-', $validated['booking_time']));
        try {
            $requestedStart = Carbon::createFromFormat('g:i A', $requestedStartStr);
            $requestedEnd = Carbon::createFromFormat('g:i A', $requestedEndStr);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid booking time format.'
            ], 422);
        }

        try {
            $appointment = DB::transaction(function () use ($validated, $requestedStart, $requestedEnd) {
                // Serialize bookings per employee by locking the employee row
                DB::table('employees')
                    ->where('id', $validated['employee_id'])
                    ->lockForUpdate()
                    ->select('id')
                    ->first();

                // Lock existing appointments for this employee and date to prevent race conditions
                $existing = Appointment::where('employee_id', $validated['employee_id'])
                    ->where('booking_date', $validated['booking_date'])
                    ->whereNotIn('status', ['Cancelled'])
                    ->lockForUpdate()
                    ->get(['booking_time']);

                foreach ($existing as $appt) {
                    // Expect stored format like "06:00 AM - 06:30 AM"
                    $parts = array_map('trim', explode('-', $appt->booking_time));
                    if (count($parts) !== 2) {
                        continue;
                    }
                    try {
                        $existingStart = Carbon::createFromFormat('g:i A', $parts[0]);
                        $existingEnd = Carbon::createFromFormat('g:i A', $parts[1]);
                    } catch (\Exception $e) {
                        continue;
                    }

                    // Overlap if start < otherEnd and end > otherStart
                    if ($requestedStart->lt($existingEnd) && $requestedEnd->gt($existingStart)) {
                        throw new \RuntimeException('slot_conflict');
                    }
                }

                // Create appointment after passing overlap check
                return Appointment::create($validated);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'slot_conflict') {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected time slot is no longer available. Please choose another.'
                ], 422);
            }
            throw $e;
        } catch (QueryException $e) {
            // Handle duplicate slot via DB unique index
            if (str_contains(strtolower($e->getMessage()), 'duplicate') || $e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected time slot has just been booked. Please pick another slot.'
                ], 422);
            }
            throw $e;
        }

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

    public function monitor(Request $request)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $query = Appointment::query();
        if (!(method_exists($user, 'hasRole') && $user->hasRole('admin'))) {
            $query->where(function($q) use ($user) {
                if ($user->employee) {
                    $q->where('appointments.employee_id', $user->employee->id);
                }
                $q->orWhere('appointments.user_id', $user->id);
            });
        }

        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $categoryId = $request->input('category_id');
        $serviceId = $request->input('service_id');

        if ($status) {
            $query->where('appointments.status', $status);
        }
        if ($dateFrom && $dateTo) {
            $query->whereBetween('appointments.booking_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->whereDate('appointments.booking_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->whereDate('appointments.booking_date', '<=', $dateTo);
        }
        if ($serviceId) {
            $query->where('appointments.service_id', $serviceId);
        }
        if ($categoryId) {
            $serviceIdsInCategory = Service::where('category_id', $categoryId)->pluck('id');
            $query->whereIn('service_id', $serviceIdsInCategory);
        }

        $filtered = (clone $query);

        $statusCounts = (clone $filtered)
            ->select(DB::raw('appointments.status as status'), DB::raw('COUNT(*) as total'))
            ->groupBy('appointments.status')
            ->pluck('total', 'status');

        $serviceCounts = (clone $filtered)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select('services.title as name', DB::raw('COUNT(*) as total'))
            ->groupBy('services.title')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $categoryCounts = (clone $filtered)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->join('categories', 'services.category_id', '=', 'categories.id')
            ->select('categories.title as name', DB::raw('COUNT(*) as total'))
            ->groupBy('categories.title')
            ->orderByDesc('total')
            ->get();

        // Staff counts (by employee user name)
        $staffCounts = (clone $filtered)
            ->join('employees', 'appointments.employee_id', '=', 'employees.id')
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->select('users.name as name', DB::raw('COUNT(*) as total'))
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $totalCount = (clone $filtered)->count();

        $categories = Category::orderBy('title')->get();
        $services = Service::orderBy('title')->get();

        $activeFilters = [
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'category_id' => $categoryId,
            'service_id' => $serviceId,
        ];

        return view('backend.monitor.index', compact(
            'statusCounts', 'serviceCounts', 'categoryCounts', 'staffCounts', 'totalCount', 'categories', 'services', 'activeFilters'
        ));
    }

    /**
     * Sanitize customer name - convert to uppercase and remove dangerous characters
     */
    private function sanitizeName($name)
    {
        return strtoupper(trim(strip_tags($name)));
    }

    /**
     * Sanitize email - convert to lowercase and remove dangerous characters
     */
    private function sanitizeEmail($email)
    {
        return strtolower(trim(strip_tags($email)));
    }

    /**
     * Sanitize student ID - remove dangerous characters
     */
    private function sanitizeStudentId($studentId)
    {
        return trim(strip_tags($studentId));
    }

    /**
     * Sanitize phone number - only allow digits, spaces, hyphens, parentheses, and plus sign
     */
    private function sanitizePhone($phone)
    {
        return preg_replace('/[^0-9+\-()\s]/', '', trim($phone));
    }

    /**
     * Sanitize notes - remove dangerous HTML tags and scripts
     */
    private function sanitizeNotes($notes)
    {
        if (empty($notes)) {
            return null;
        }
        
        // Remove potentially dangerous HTML tags and scripts
        $notes = strip_tags($notes);
        $notes = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $notes);
        $notes = preg_replace('/javascript:/i', '', $notes);
        $notes = preg_replace('/on\w+\s*=/i', '', $notes);
        
        return trim($notes);
    }
}
