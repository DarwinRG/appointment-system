@extends('adminlte::page')

@section('title', 'All Appointments')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>All Apointments</h1>
        </div>

    </div>
@stop

@section('content')
    <!-- Modal -->
    <form id="appointmentStatusForm" method="POST" action="{{ route('appointments.update.status') }}">
        @csrf
        <input type="hidden" name="appointment_id" id="modalAppointmentId">

        <div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Appointment Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p><strong>Client:</strong> <span id="modalAppointmentName">N/A</span></p>
                        <p><strong>Student ID:</strong> <span id="modalStudentId">N/A</span></p>
                        <p><strong>Service:</strong> <span id="modalService">N/A</span></p>
                        <p><strong>Email:</strong> <span id="modalEmail">N/A</span></p>
                        <p><strong>Phone:</strong> <span id="modalPhone">N/A</span></p>
                        <p><strong>Staff:</strong> <span id="modalStaff">N/A</span></p>
                        <p><strong>Start:</strong> <span id="modalStartTime">N/A</span></p>

                        <p><strong>Notes:</strong> <span id="modalNotes">N/A</span></p>
                        <p><strong>Current Status:</strong> <span id="modalStatusBadge">N/A</span></p>


                        <div class="form-group ">
                            <label><strong>Status:</strong></label>
                            <select name="status" class="form-control" id="modalStatusSelect">

                                <option value="Processing">Processing</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Completed">Completed</option>
                                <option value="On Hold">On Hold</option>
                                {{-- <option value="Rescheduled">Rescheduled</option> --}}
                                <option value="No Show">No Show</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" onclick="return confirm('Are you sure you want to update booking status?')"
                            class="btn btn-danger">Update Status</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>
    </form>
    <div class="">
        @if (session('success'))
            <div class="alert alert-success alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong>{{ session('success') }}</strong>
            </div>
        @endif
        <!-- Content Header (Page header) -->
        <!-- Content Header (Page header) -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card py-2 px-2">

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-striped projects">
                                        <thead>
                                            <tr>
                                                <th style="width: 1%">
                                                    #
                                                </th>
                                                <th style="width: 15%">
                                                    User
                                                </th>
                                                <th style="width: 10%" class="d-none-xs">
                                                    Student ID
                                                </th>
                                                <th style="width: 15%" class="d-none-xs">
                                                    Email
                                                </th>
                                                <th style="width: 10%" class="d-none-xs">
                                                    Phone
                                                </th>
                                                <th style="width: 10%">
                                                    Staff
                                                </th>
                                                <th style="width: 10%">
                                                    Service
                                                </th>
                                                <th style="width: 10%">
                                                    Date
                                                </th>
                                                <th style="width: 10%">
                                                    Time
                                                </th>
                                                <th style="width: 15%" class="text-center">
                                                    Status
                                                </th>
                                                <th style="width: 18%">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                    <tbody>
                                        @php
                                            $statusColors = [
    
                                                'Processing' => '#3498db',
                                                'Confirmed' => '#2ecc71',
                                                'Cancelled' => '#ff0000',
                                                'Completed' => '#008000',
                                                'On Hold' => '#95a5a6',
                                                'Rescheduled' => '#f1c40f',
                                                'No Show' => '#e67e22',
                                            ];
                                        @endphp
                                        @foreach ($appointments as $appointment)
                                            <tr>
                                                <td data-label="#">
                                                    {{ $loop->iteration }}
                                                </td>
                                                <td data-label="User">
                                                    <a>
                                                        {{ $appointment->name }}
                                                    </a>
                                                    <br>
                                                    <small>
                                                        {{ $appointment->created_at->format('d M Y') }}
                                                    </small>
                                                </td>
                                                <td data-label="Student ID" class="d-none-xs">
                                                    {{ $appointment->student_id ?? 'N/A' }}
                                                </td>
                                                <td data-label="Email" class="d-none-xs">
                                                    {{ $appointment->email }}
                                                </td>
                                                <td data-label="Phone" class="d-none-xs">
                                                    {{ $appointment->phone }}
                                                </td>
                                                <td data-label="Staff">
                                                    {{ $appointment->employee->user->name }}
                                                </td>
                                                <td data-label="Service">
                                                    {{ $appointment->service->title ?? 'NA' }}
                                                </td>
                                                <td data-label="Date">
                                                    {{ $appointment->booking_date }}
                                                </td>
                                                <td data-label="Time">
                                                    {{ $appointment->booking_time }}
                                                </td>
                                                <td data-label="Status">
                                                    @php
                                                        $status = $appointment->status;
                                                        $color = $statusColors[$status] ?? '#7f8c8d';
                                                    @endphp
                                                    <span class="badge px-2 py-1"
                                                        style="background-color: {{ $color }}; color: white;">
                                                        {{ $status }}
                                                    </span>
                                                </td>
                                                <td data-label="Action">
                                                    <button class="btn btn-primary btn-sm py-0 px-1 view-appointment-btn"
                                                        data-toggle="modal" data-target="#appointmentModal"
                                                        data-id="{{ $appointment->id }}"
                                                        data-name="{{ $appointment->name }}"
                                                        data-student-id="{{ $appointment->student_id ?? 'N/A' }}"
                                                        data-service="{{ $appointment->service->title ?? 'MA' }}"
                                                        data-email="{{ $appointment->email }}"
                                                        data-phone="{{ $appointment->phone }}"
                                                        data-employee="{{ $appointment->employee->user->name }}"
                                                        data-start="{{ $appointment->booking_date . ' ' . $appointment->booking_time }}"
                                
                                                        data-notes="{{ $appointment->notes }}"
                                                        data-status="{{ $appointment->status }}">View</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>

                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                    <!-- /.col -->

                </div>
                <!-- /.row -->
            </div><!-- /.container-fluid -->
        </section>
    </div>
@stop

@section('css')

@stop

@section('js')

    {{-- hide notifcation --}}
    <script>
        $(document).ready(function() {
            $(".alert").delay(6000).slideUp(300);
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#myTable').DataTable({
                responsive: true,
                scrollX: true,
                scrollCollapse: true,
                autoWidth: false,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                columnDefs: [
                    {
                        targets: [2, 3, 4], // Student ID, Email, Phone columns
                        className: 'd-none-xs'
                    }
                ]
            });
        });
    </script>



    <script>
        $(document).on('click', '.view-appointment-btn', function() {
            // Set modal fields
            $('#modalAppointmentId').val($(this).data('id'));
            $('#modalAppointmentName').text($(this).data('name'));
            $('#modalStudentId').text($(this).data('student-id'));
            $('#modalService').text($(this).data('service'));
            $('#modalEmail').text($(this).data('email'));
            $('#modalPhone').text($(this).data('phone'));
            $('#modalStaff').text($(this).data('employee'));
            $('#modalStartTime').text($(this).data('start'));
            
            $('#modalNotes').text($(this).data('notes'));

            // Set status select dropdown
            var status = $(this).data('status');
            $('#modalStatusSelect').val(status);

            // Set status badge
            var statusColors = {
        
                'Processing': '#3498db',
                'Confirmed': '#2ecc71',
                'Cancelled': '#ff0000',
                'Completed': '#008000',
                'On Hold': '#95a5a6',
                'Rescheduled': '#f1c40f',
                'No Show': '#e67e22',
            };

            var badgeColor = statusColors[status] || '#7f8c8d';
            $('#modalStatusBadge').html(
                `<span class="badge px-2 py-1" style="background-color: ${badgeColor}; color: white;">${status}</span>`
            );
        });
    </script>
@endsection
