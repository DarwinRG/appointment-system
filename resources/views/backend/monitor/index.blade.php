@extends('adminlte::page')

@section('title', 'Monitor')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Monitor</h1>
        </div>
    </div>
@stop

@section('content')
    <form method="GET" action="{{ route('appointments.monitor') }}" class="mb-3">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Filters</span>
                <div class="d-flex align-items-center">
                    <a href="{{ route('appointments.monitor', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-primary mr-2">Today</a>
                    <a href="{{ route('appointments.monitor') }}" class="btn btn-sm btn-outline-secondary">Refresh</a>
                </div>
            </div>
            <div class="card-body">
                @php
                    $hasFilters = !empty($activeFilters['status']) || !empty($activeFilters['date_from']) || !empty($activeFilters['date_to']) || !empty($activeFilters['category_id']) || !empty($activeFilters['service_id']);
                @endphp
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            @foreach(['Processing','Confirmed','Cancelled','Completed','On Hold','No Show'] as $s)
                                <option value="{{ $s }}" {{ ($activeFilters['status'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date from</label>
                        <input type="date" name="date_from" value="{{ $activeFilters['date_from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date to</label>
                        <input type="date" name="date_to" value="{{ $activeFilters['date_to'] ?? '' }}" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">All</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($activeFilters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Service</label>
                        <select name="service_id" class="form-control">
                            <option value="">All</option>
                            @foreach($services as $svc)
                                <option value="{{ $svc->id }}" {{ ($activeFilters['service_id'] ?? '') == $svc->id ? 'selected' : '' }}>{{ $svc->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-9 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">Apply</button>
                        @if($hasFilters)
                            <a href="{{ route('appointments.monitor') }}" class="btn btn-outline-danger">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-12 mb-2">
            <div class="small text-muted">Total appointments: <strong>{{ number_format($totalCount) }}</strong></div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card">
                <div class="card-header py-2">Statuses</div>
                <div class="card-body py-2">
                    <canvas id="statusChart" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card">
                <div class="card-header py-2">Services</div>
                <div class="card-body py-2">
                    <canvas id="serviceChart" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card">
                <div class="card-header py-2">Categories</div>
                <div class="card-body py-2">
                    <canvas id="categoryChart" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card">
                <div class="card-header py-2">Staff</div>
                <div class="card-body py-2">
                    <canvas id="staffChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function(){
            const statusCounts = @json($statusCounts);
            const serviceCounts = @json($serviceCounts);
            const categoryCounts = @json($categoryCounts);
            const staffCounts = @json($staffCounts);

            const palette = ['#2563eb','#16a34a','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#22c55e','#94a3b8','#a78bfa'];
            const pieOptions = { responsive: true, plugins: { legend: { position: 'bottom' } } };

            // Helper to compact to max slices and add Other
            function compactSlices(items, labelKey, valueKey, max = 8) {
                const sorted = [...items].sort((a,b) => (b[valueKey]||0) - (a[valueKey]||0));
                const top = sorted.slice(0, max);
                const rest = sorted.slice(max);
                const otherTotal = rest.reduce((sum, it) => sum + (it[valueKey]||0), 0);
                const labels = top.map(i => i[labelKey]);
                const data = top.map(i => i[valueKey]);
                if (otherTotal > 0) { labels.push('Other'); data.push(otherTotal); }
                return { labels, data };
            }

            // Status doughnut
            (function(){
                const labels = Object.keys(statusCounts);
                const data = Object.values(statusCounts);
                new Chart(document.getElementById('statusChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels, datasets: [{ data, backgroundColor: palette }] },
                    options: pieOptions
                });
            })();

            // Services doughnut (compact)
            (function(){
                const compact = compactSlices(serviceCounts, 'name', 'total', 8);
                new Chart(document.getElementById('serviceChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: compact.labels, datasets: [{ data: compact.data, backgroundColor: palette }] },
                    options: pieOptions
                });
            })();

            // Categories doughnut (compact)
            (function(){
                const compact = compactSlices(categoryCounts, 'name', 'total', 8);
                new Chart(document.getElementById('categoryChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: compact.labels, datasets: [{ data: compact.data, backgroundColor: palette }] },
                    options: pieOptions
                });
            })();

            // Staff doughnut (compact)
            (function(){
                const compact = compactSlices(staffCounts, 'name', 'total', 8);
                new Chart(document.getElementById('staffChart').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: compact.labels, datasets: [{ data: compact.data, backgroundColor: palette }] },
                    options: pieOptions
                });
            })();
        })();
    </script>
@stop


