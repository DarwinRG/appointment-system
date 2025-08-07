@extends('adminlte::page')

@section('title', 'All Users')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>All Users</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('user.create') }}">+ Add New</a> |</li>
                <li class=""> &nbsp; <a href="{{ route('user.trash') }}">View Trash</a></li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    <section class="content">
        <div class="container-fluid">
            @if (count($errors) > 0)
            <div class="alert alert-dismissable alert-danger mt-3">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong>Whoops!</strong> There were some problems with your input.<br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong>{{ session('success') }}</strong>
            </div>
        @endif
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
                                            <th style="width: 10%">
                                                Name
                                            </th>
                                            <th style="width: 10%">
                                                Email
                                            </th>
                                            <th style="width: 10%" class="d-none-xs">
                                                Image
                                            </th>
                                            <th style="width: 10%">
                                                Role
                                            </th>
                                            <th style="width: 6%">
                                                Status
                                            </th>
                                            <th style="width: 5%">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td data-label="#">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td data-label="Name">
                                                <a>
                                                    {{ $user->name }}
                                                </a>
                                                <br>
                                                <small>
                                                    {{ $user->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                             <td data-label="Email">{{ $user->email }}
                                            </td>
                                            <td data-label="Image" class="d-none-xs">
                                               <img style="width:50px;" class="rounded-pill" src="{{ $user->profileImage() }}" alt="">
                                            </td>
                                            <td data-label="Role">
                                                @foreach ($user->getRoleNames() as $role)
                                                    {{ ucfirst($role) }}@if(!$loop->last),@endif
                                                @endforeach
                                            </td>

                                            <td data-label="Status" class="project-state">
                                                @if ($user->status)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">In-Active</span>
                                                @endif
                                            </td>
                                            <td data-label="Action" class="project-actions">
                                                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                                                    <a class="btn btn-info btn-sm"
                                                        href="{{ route('user.edit', $user->id) }}">
                                                        <i class="fas fa-pencil-alt"></i>
                                                        <span class="d-none d-sm-inline">Edit</span>
                                                    </a>
                                                    <form action="{{ route('user.destroy', $user->id) }}"
                                                        method="post" class="d-inline">
                                                        @csrf
                                                        @method('delete')
                                                        <button
                                                            onclick="return confirm('Are you sure you want to delete this item?');"
                                                            type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                            <span class="d-none d-sm-inline">Trash</span>
                                                        </button>
                                                    </form>
                                                </div>
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
                        targets: [3], // Image column
                        className: 'd-none-xs'
                    }
                ]
            });
        });
    </script>


    {{-- Sucess and error notification alert --}}
    <script>
        $(document).ready(function() {
        // show error message
        @if ($errors->any())
            //var errorMessage = @json($errors->any()); // Get the first validation error message
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5500
            });

            Toast.fire({
                icon: 'error',
                title: 'There are form validation errors. Please fix them.'
            });
        @endif

        // success message
        @if (session('success'))
            var successMessage = @json(session('success')); // Get the first sucess message
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5500
            });

            Toast.fire({
                icon: 'success',
                title: successMessage
            });
        @endif

        });
    </script>
@endsection
