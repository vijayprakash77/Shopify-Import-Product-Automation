@extends('main-dashboard')

@section('import-list')
 <div id="kt_app_content_container" class="app-container container-xxl">
    <div class="card shadow-sm border-0">
        <!-- Card Header -->
        <div class="card-header bg-white border-bottom py-4">
            <div class="row align-items-center g-3">
                <!-- Search Input -->
                <div class="col-md-4">
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input id="import_search" type="text" class="form-control ps-5 border-2" placeholder="Search Import">
                    </div>
                </div>
                
                <!-- Filters and Actions -->
                <div class="col-md-8">
                    <div class="row g-2 justify-content-end">
                        <!-- Date Filter -->
                        <div class="col-auto">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-calendar3"></i>
                                </span>
                                <input class="form-control border-start-0" placeholder="Pick import date" id="filter_by_importdate"/>
                                <input type="hidden" id="filter_by_import_date" />
                            </div>
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-auto">
                            <select id="filter_by_status" class="form-select border-2" style="min-width: 150px;">
                                <option value="select_status">All Status</option>
                                <option value="completed">Completed</option>
                                <option value="processing">Processing</option>
                                <option value="failed">Failed</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        
                        <!-- Refresh Button -->
                        <div class="col-auto">
                            <button id="refresh_table" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                            </button>
                        </div>
                        
                        <!-- New Import Button -->
                        <div class="col-auto">
                            <a href="{{route('new-import-get')}}" class="btn btn-primary px-2">
                                <i class="bi bi-arrow-repeat me-2"></i>Import
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card Body -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="kt_post_datatable" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-bold text-uppercase text-muted py-3 px-4">
                                <i class="bi bi-file-text me-2"></i>File Name
                            </th>
                            <th class="fw-bold text-uppercase text-muted py-3 px-4">
                                <i class="bi bi-activity me-2"></i>Status
                            </th>
                            <th class="fw-bold text-uppercase text-muted py-3 px-4">
                                <i class="bi bi-play-circle me-2"></i>Started
                            </th>
                            <th class="fw-bold text-uppercase text-muted py-3 px-4">
                                <i class="bi bi-check-circle me-2"></i>Completed
                            </th>
                            <th class="fw-bold text-uppercase text-muted py-3 px-4 text-center">
                                <i class="bi bi-gear me-2"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <!-- Data will be loaded by Yajra DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2"></i>Import Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="d-none">
    <div class="position-fixed top-0 start-0 w-100 h-100 bg-black bg-opacity-50 d-flex justify-content-center align-items-center" style="z-index: 9999;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<!-- Include Required Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script type="text/javascript">
$(document).ready(function() {
    // Initialize Yajra DataTable
    var table = $('#kt_post_datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("import-list-ajax") }}',
            type: 'POST',
            data: function(d) {
                d._token = '{{ csrf_token() }}';
                d.status_filter = $('#filter_by_status').val();
                d.date_filter = $('#filter_by_import_date').val();
                d.search_term = $('#import_search').val();
            }
        },
        columns: [
            {
                data: 'file_info',
                name: 'file_info',
                orderable: true,
                searchable: false
            },
            {
                data: 'status_badge',
                name: 'status_badge',
                orderable: true,
                searchable: false
            },
            {
                data: 'started_formatted',
                name: 'started_formatted',
                orderable: true,
                searchable: false
            },
            {
                data: 'completed_formatted',
                name: 'completed_formatted',
                orderable: true,
                searchable: false
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ],
        order: [[2, 'desc']], // Order by Started column
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<div class="d-flex justify-content-center align-items-center"><div class="spinner-border text-primary me-2" role="status"></div>Loading imports...</div>',
            search: "",
            searchPlaceholder: "Search imports...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ imports",
            infoEmpty: "No imports found",
            infoFiltered: "(filtered from _MAX_ total imports)",
            paginate: {
                first: '<i class="bi bi-chevron-double-left"></i>',
                last: '<i class="bi bi-chevron-double-right"></i>',
                next: '<i class="bi bi-chevron-right"></i>',
                previous: '<i class="bi bi-chevron-left"></i>'
            },
            emptyTable: `
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">No imports found</h5>
                    <p class="text-muted">Start by creating your first import</p>
                    <a href="{{route('new-import-get')}}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>New Import
                    </a>
                </div>
            `
        },
        drawCallback: function(settings) {
            // Re-initialize Bootstrap tooltips and popovers after each draw
            $('[data-bs-toggle="tooltip"]').tooltip();
            $('[data-bs-toggle="popover"]').popover();
        }
    });

    // Custom search functionality with debounce
    let searchTimeout;
    $('#import_search').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            table.draw();
        }, 500); // 500ms delay
    });

    // Status filter
    $('#filter_by_status').on('change', function() {
        table.draw();
    });

    // Initialize date picker
    $('#filter_by_importdate').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        orientation: 'bottom auto',
        clearBtn: true
    }).on('changeDate clearDate', function(e) {
        if (e.type === 'clearDate') {
            $('#filter_by_import_date').val('');
        } else {
            $('#filter_by_import_date').val(e.format());
        }
        table.draw();
    });

    // Clear date filter when input is manually cleared
    $('#filter_by_importdate').on('keyup', function() {
        if ($(this).val() === '') {
            $('#filter_by_import_date').val('');
            table.draw();
        }
    });

    // Refresh table button
    $('#refresh_table').on('click', function() {
        table.ajax.reload(null, false); // false = don't reset pagination
    });

    // Auto-refresh every 30 seconds for processing imports
    setInterval(function() {
        // Only auto-refresh if there are processing imports visible
        if ($('.badge:contains("Processing")').length > 0) {
            table.ajax.reload(null, false);
        }
    }, 30000);
});

// Action functions
function viewDetails(id) {
    $('#loadingOverlay').removeClass('d-none');
    
    $.ajax({
        url: '/import-details/' + id,
        type: 'GET',
        success: function(response) {
            const progressBar = response.total_rows > 0 ? `
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: ${response.progress_percentage}%" 
                         aria-valuenow="${response.progress_percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">${response.progress_percentage}% Complete</small>
            ` : '';

            $('#modalContent').html(`
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-primary mb-3"><i class="bi bi-file-text me-2"></i>File Information</h6>
                                <div class="row g-2">
                                    <div class="col-4"><strong>Filename:</strong></div>
                                    <div class="col-8">${response.filename || 'N/A'}</div>
                                    <div class="col-4"><strong>Status:</strong></div>
                                    <div class="col-8"><span class="badge bg-info">${response.status || 'N/A'}</span></div>
                                    <div class="col-4"><strong>Created:</strong></div>
                                    <div class="col-8">${response.created_at || 'N/A'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-success mb-3"><i class="bi bi-bar-chart me-2"></i>Progress Statistics</h6>
                                ${progressBar}
                                <div class="row g-2 mt-2">
                                    <div class="col-6"><strong>Total Rows:</strong></div>
                                    <div class="col-6">${response.total_rows}</div>
                                    <div class="col-6"><strong>Processed:</strong></div>
                                    <div class="col-6">${response.processed_rows}</div>
                                    <div class="col-6"><strong>Successful:</strong></div>
                                    <div class="col-6 text-success">${response.successful_rows}</div>
                                    <div class="col-6"><strong>Failed:</strong></div>
                                    <div class="col-6 text-danger">${response.failed_rows}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${response.products && response.products.length > 0 ? `
                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-warning mb-3"><i class="bi bi-box me-2"></i>Products (${response.products.length})</h6>
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th>No.</th>
                                                <th>Product Title</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${response.products.map((product, index) => `
                                            <tr>
                                                <td>${index + 1}</td>
                                                <td>${product.title || 'N/A'}</td>
                                                <td>
                                                    <span class="badge ${product.import_status === 'success' ? 'bg-success' : 
                                                        product.import_status === 'pending' ? 'bg-secondary' : 
                                                        product.import_status === 'failed' ? 'bg-danger' : 'bg-info'}">
                                                        ${product.import_status || 'N/A'}
                                                    </span>
                                                </td>
                                            </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-info mb-3"><i class="bi bi-clock-history me-2"></i>Timeline</h6>
                                <div class="row g-2">
                                    <div class="col-md-4"><strong>Started At:</strong></div>
                                    <div class="col-md-8">${response.started_at || 'Not started'}</div>
                                    <div class="col-md-4"><strong>Completed At:</strong></div>
                                    <div class="col-md-8">${response.completed_at || 'Not completed'}</div>
                                    ${response.duration ? `
                                    <div class="col-md-4"><strong>Duration:</strong></div>
                                    <div class="col-md-8">${response.duration}</div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    ${response.errors ? `
                    <div class="col-12">
                        <div class="card bg-danger bg-opacity-10 border-danger border-opacity-25">
                            <div class="card-body">
                                <h6 class="text-danger mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Errors</h6>
                                <pre class="text-danger small mb-0" style="white-space: pre-wrap;">${response.errors}</pre>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `);
            $('#detailsModal').modal('show');
        },
        error: function(xhr) {
            alert('Error loading import details: ' + (xhr.responseJSON?.message || 'Unknown error'));
        },
        complete: function() {
            $('#loadingOverlay').addClass('d-none');
        }
    });
}

function importAgain(id) {
    if (confirm('Are you sure you want to import this file again? This will reset all progress.')) {
        $('#loadingOverlay').removeClass('d-none');
        
        $.ajax({
            url: '/import-again/' + id,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Import restarted successfully!');
                    $('#kt_post_datatable').DataTable().ajax.reload(null, false);
                } else {
                    alert('Error: ' + (response.message || 'Failed to restart import'));
                }
            },
            error: function(xhr) {
                alert('Error restarting import: ' + (xhr.responseJSON?.message || 'Unknown error'));
            },
            complete: function() {
                $('#loadingOverlay').addClass('d-none');
            }
        });
    }
}

function deleteImport(id) {
    if (confirm('Are you sure you want to delete this import? This action cannot be undone.')) {
        $('#loadingOverlay').removeClass('d-none');
        
        $.ajax({
            url: '/delete-import/' + id,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Import deleted successfully!');
                    $('#kt_post_datatable').DataTable().ajax.reload(null, false);
                } else {
                    alert('Error: ' + (response.message || 'Failed to delete import'));
                }
            },
            error: function(xhr) {
                alert('Error deleting import: ' + (xhr.responseJSON?.message || 'Unknown error'));
            },
            complete: function() {
                $('#loadingOverlay').addClass('d-none');
            }
        });
    }
}

// Additional utility functions
function showNotification(message, type = 'success') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'bi-check-circle' : 'bi-x-circle';
    
    const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            <i class="${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        notification.alert('close');
    }, 5000);
}

// Export function (optional)
function exportImportList() {
    const filters = {
        status_filter: $('#filter_by_status').val(),
        date_filter: $('#filter_by_import_date').val(),
        search_term: $('#import_search').val()
    };
    
    const queryString = new URLSearchParams(filters).toString();
    window.location.href = '/export-imports?' + queryString;
}

// Statistics refresh function
function refreshStats() {
    $.ajax({
        url: '/import-stats',
        type: 'GET',
        success: function(response) {
            // Update stats if you have a stats dashboard
            console.log('Import Statistics:', response);
        },
        error: function(xhr) {
            console.error('Error fetching stats:', xhr.responseJSON?.message || 'Unknown error');
        }
    });
}

// Initialize tooltips and setup event handlers
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Handle modal cleanup
    $('#detailsModal').on('hidden.bs.modal', function() {
        $('#modalContent').html('');
    });
    
    // Handle keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + R to refresh table
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
            e.preventDefault();
            $('#refresh_table').click();
        }
        
        // Escape to close modal
        if (e.keyCode === 27 && $('#detailsModal').hasClass('show')) {
            $('#detailsModal').modal('hide');
        }
    });
    
    // Handle window focus to refresh processing imports
    $(window).on('focus', function() {
        if ($('.badge:contains("Processing")').length > 0) {
            $('#kt_post_datatable').DataTable().ajax.reload(null, false);
        }
    });
});
</script>

<!-- Additional CSS for better styling -->


@endsection