@extends('main-dashboard')

@section('new-import')
<div id="kt_app_content_container" class="app-container container-xxl">
    <!-- Upload Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-upload fs-3 text-primary me-3"></i>
                <div>
                    <h4 class="mb-0 fw-bold">Import Product -> Shopify Store</h4>
                    <p class="text-muted mb-0">Upload CSV file to import products to Shopify</p>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <!-- File Upload Area -->
                        <div class="upload-area border-2 border-dashed rounded-3 p-5 text-center mb-3" 
                             id="uploadArea"
                             ondrop="dropHandler(event);" 
                             ondragover="dragOverHandler(event);" 
                             ondragleave="dragLeaveHandler(event);">
                            <div id="uploadContent">
                                <i class="bi bi-cloud-upload display-1 text-primary mb-3"></i>
                                <h5 class="mb-3">Drop your CSV file here or click to browse</h5>
                                <!-- <p class="text-muted mb-3">Maximum file size: 10MB. Only CSV files are allowed.</p> -->
                                <input type="file" id="csvFile" name="csv_file" accept=".csv" class="d-none">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('csvFile').click()">
                                    <i class="bi bi-folder2-open me-2"></i>Choose File
                                </button>
                            </div>
                            
                            <!-- File Preview (Hidden initially) -->
                            <div id="filePreview" class="d-none">
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                                        <i class="bi bi-file-earmark-check fs-2 text-success"></i>
                                    </div>
                                    <div class="text-start">
                                        <h6 class="mb-1 fw-semibold" id="fileName"></h6>
                                        <small class="text-muted" id="fileSize"></small>
                                        <br>
                                        <small class="text-success" id="fileStatus">Ready to upload</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="removeFile()">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Error Display -->
                        <span id="fileError" class="text-danger"></span>
                        
                        <!-- Upload Button -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                
                            </div>
                            <button type="submit" id="uploadBtn" class="btn btn-success px-4" disabled>
                                <i class="bi bi-upload me-2"></i>Upload & Process
                            </button>
                        </div>
                    </div>
                    
                    <!-- Instructions -->
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-question-circle me-2"></i>Upload Instructions
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success me-2"></i>
                                        File must be in CSV format
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success me-2"></i>
                                        Maximum file size: 10MB
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success me-2"></i>
                                        Include proper column headers
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success me-2"></i>
                                        Products will be uploaded to Shopify automatically
                                    </li>
                                </ul>
                                
                                <hr class="my-3">
                                
                                
                                <a href="/download-sample-csv" class="btn btn-outline-info btn-sm w-100">
                                    <i class="bi bi-download me-2"></i>Download Sample
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Progress Card (Hidden initially) -->
    <div class="card shadow-sm border-0 d-none" id="progressCard">
        <div class="card-header bg-white border-bottom py-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-arrow-repeat fs-3 text-warning me-3" id="processingIcon"></i>
                    <div>
                        <h5 class="mb-0 fw-bold">Import Progress</h5>
                        <p class="text-muted mb-0" id="progressStatus">Processing your file...</p>
                    </div>
                </div>
                <div class="text-end">
                    <h4 class="mb-0 text-primary" id="progressPercentage">0%</h4>
                    <small class="text-muted">Complete</small>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Overall Progress -->
            <div class="mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Overall Progress</span>
                    <span class="text-muted" id="progressText">0 of 0 products</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" id="progressBar"></div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-primary bg-opacity-10 border-primary border-opacity-25">
                        <div class="card-body text-center">
                            <i class="bi bi-files fs-2 text-primary mb-2"></i>
                            <h4 class="mb-0 text-primary" id="totalProducts">0</h4>
                            <small class="text-muted">Total Products</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success bg-opacity-10 border-success border-opacity-25">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle fs-2 text-success mb-2"></i>
                            <h4 class="mb-0 text-success" id="successProducts">0</h4>
                            <small class="text-muted">Successful</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger bg-opacity-10 border-danger border-opacity-25">
                        <div class="card-body text-center">
                            <i class="bi bi-x-circle fs-2 text-danger mb-2"></i>
                            <h4 class="mb-0 text-danger" id="failedProducts">0</h4>
                            <small class="text-muted">Failed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning bg-opacity-10 border-warning border-opacity-25">
                        <div class="card-body text-center">
                            <i class="bi bi-clock fs-2 text-warning mb-2"></i>
                            <h4 class="mb-0 text-warning" id="pendingProducts">0</h4>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <button class="btn btn-outline-primary w-100" id="pauseBtn" onclick="pauseImport()">
                        <i class="bi bi-pause-circle me-2"></i>Pause Import
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-outline-secondary w-100" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise me-2"></i>New Import
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="d-none">
    <div class="position-fixed top-0 start-0 w-100 h-100 bg-black bg-opacity-50 d-flex justify-content-center align-items-center" style="z-index: 9999;">
        <div class="text-center text-white">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5>Processing your file...</h5>
            <p class="mb-0">Please wait while we validate and process your CSV file</p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentUploadId = null;
let progressTimer = null;

$(document).ready(function() {
    // File input change handler
    $('#csvFile').on('change', function() {
        handleFileSelect(this.files[0]);
    });
    
    // Form submit handler
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        uploadFile();
    });
    
    // Check for existing import on page load
    checkExistingImport();
});

// Drag and drop handlers
function dragOverHandler(ev) {
    ev.preventDefault();
    $('#uploadArea').addClass('border-primary bg-primary bg-opacity-5');
}

function dragLeaveHandler(ev) {
    ev.preventDefault();
    $('#uploadArea').removeClass('border-primary bg-primary bg-opacity-5');
}

function dropHandler(ev) {
    ev.preventDefault();
    $('#uploadArea').removeClass('border-primary bg-primary bg-opacity-5');
    
    const files = ev.dataTransfer.files;
    if (files.length > 0) {
        handleFileSelect(files[0]);
    }
}

// File selection handler
function handleFileSelect(file) {
    $('#fileError').text('');
    
    if (!file) return;
    
    // Validate file type
    if (!file.name.toLowerCase().endsWith('.csv')) {
        $('#fileError').text('Error: Only CSV files are allowed.');
        return;
    }
    
    // Validate file size (10MB = 10 * 1024 * 1024 bytes)
    if (file.size > 10 * 1024 * 1024) {
        $('#fileError').text('Error: File size must be less than 10MB.');
        return;
    }
    
    // Show file preview
    $('#uploadContent').addClass('d-none');
    $('#filePreview').removeClass('d-none');
    $('#fileName').text(file.name);
    $('#fileSize').text(formatFileSize(file.size));
    $('#uploadBtn').prop('disabled', false);
}

// Remove selected file
function removeFile() {
    $('#csvFile').val('');
    $('#filePreview').addClass('d-none');
    $('#uploadContent').removeClass('d-none');
    $('#uploadBtn').prop('disabled', true);
    $('#fileError').text('');
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Upload file
function uploadFile() {
    const fileInput = $('#csvFile')[0];
    const file = fileInput.files[0];
    
    if (!file) {
        $('#fileError').text('Error: Please select a file.');
        return;
    }
    
    const formData = new FormData();
    formData.append('csv_file', file);
    formData.append('_token', $('input[name="_token"]').val());
    
    $('#loadingOverlay').removeClass('d-none');
    $('#uploadBtn').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("process-import") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                currentUploadId = response.upload_id;
                showProgressCard();
                startProgressTracking();
                showNotification('File uploaded successfully! Import process started.', 'success');
            } else {
                $('#fileError').text('Error: ' + (response.message || 'Upload failed'));
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Upload failed. Please try again.';
            $('#fileError').text('Error: ' + errorMsg);
            showNotification('Upload failed: ' + errorMsg, 'error');
        },
        complete: function() {
            $('#loadingOverlay').addClass('d-none');
            $('#uploadBtn').prop('disabled', false);
        }
    });
}

// Show progress card
function showProgressCard() {
    $('#progressCard').removeClass('d-none');
    $('html, body').animate({
        scrollTop: $('#progressCard').offset().top - 100
    }, 500);
}

// Start progress tracking
function startProgressTracking() {
    if (!currentUploadId) return;
    
    progressTimer = setInterval(function() {
        updateProgress();
    }, 2000); // Update every 2 seconds
    
    // Initial update
    updateProgress();
}

// Update progress
function updateProgress() {
    if (!currentUploadId) return;
    
    $.ajax({
        url: '/import-progress/' + currentUploadId,
        type: 'GET',
        success: function(response) {
            updateProgressUI(response);
            
            // Stop tracking if completed
            if (response.status === 'completed' || response.status === 'failed') {
                clearInterval(progressTimer);
                updateCompletedStatus(response.status);
            }
        },
        error: function(xhr) {
            console.error('Error fetching progress:', xhr);
        }
    });
}

// Update progress UI
function updateProgressUI(data) {
    // Update numbers
    $('#totalProducts').text(data.total_rows || 0);
    $('#successProducts').text(data.successful_rows || 0);
    $('#failedProducts').text(data.failed_rows || 0);
    $('#pendingProducts').text((data.total_rows || 0) - (data.processed_rows || 0));
    
    // Update progress bar
    const percentage = data.total_rows > 0 ? Math.round((data.processed_rows / data.total_rows) * 100) : 0;
    $('#progressPercentage').text(percentage + '%');
    $('#progressBar').css('width', percentage + '%');
    $('#progressText').text(`${data.processed_rows || 0} of ${data.total_rows || 0} products`);
    
    // Update status
    if (data.status === 'processing') {
        $('#progressStatus').text('Uploading products to Shopify...');
        $('#processingIcon').addClass('fa-spin');
    }
}

// Update completed status
function updateCompletedStatus(status) {
    const icon = $('#processingIcon');
    icon.removeClass('fa-spin bi-arrow-repeat');
    
    if (status === 'completed') {
        icon.removeClass('text-warning').addClass('text-success').addClass('bi-check-circle');
        $('#progressStatus').text('Import completed successfully!');
        showNotification('Import completed successfully!', 'success');
    } else if (status === 'failed') {
        icon.removeClass('text-warning').addClass('text-danger').addClass('bi-x-circle');
        $('#progressStatus').text('Import failed. Please check the logs.');
        showNotification('Import failed. Please check the logs.', 'error');
    }
}

// Check for existing import
function checkExistingImport() {
    // Check if there's an ongoing import when page loads
    const urlParams = new URLSearchParams(window.location.search);
    const uploadId = urlParams.get('upload_id');
    
    if (uploadId) {
        currentUploadId = uploadId;
        showProgressCard();
        startProgressTracking();
    }
}

// Pause import
function pauseImport() {
    if (!currentUploadId) return;
    
    $.ajax({
        url: '/pause-import/' + currentUploadId,
        type: 'POST',
        data: {
            _token: $('input[name="_token"]').val()
        },
        success: function(response) {
            if (response.success) {
                clearInterval(progressTimer);
                showNotification('Import paused successfully', 'success');
                $('#pauseBtn').html('<i class="bi bi-play-circle me-2"></i>Resume Import')
                    .attr('onclick', 'resumeImport()');
            }
        },
        error: function(xhr) {
            showNotification('Failed to pause import', 'error');
        }
    });
}

// Resume import
function resumeImport() {
    if (!currentUploadId) return;
    
    $.ajax({
        url: '/resume-import/' + currentUploadId,
        type: 'POST',
        data: {
            _token: $('input[name="_token"]').val()
        },
        success: function(response) {
            if (response.success) {
                startProgressTracking();
                showNotification('Import resumed successfully', 'success');
                $('#pauseBtn').html('<i class="bi bi-pause-circle me-2"></i>Pause Import')
                    .attr('onclick', 'pauseImport()');
            }
        },
        error: function(xhr) {
            showNotification('Failed to resume import', 'error');
        }
    });
}

// Notification function
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
    
    setTimeout(function() {
        notification.alert('close');
    }, 5000);
}

// Handle page visibility change
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden (tab switched or minimized)
        if (progressTimer) {
            clearInterval(progressTimer);
        }
    } else {
        // Page is visible again
        if (currentUploadId && !progressTimer) {
            startProgressTracking();
        }
    }
});

// Handle page unload
window.addEventListener('beforeunload', function(e) {
    if (currentUploadId && progressTimer) {
        // Import is running, warn user
        e.preventDefault();
        e.returnValue = 'Import is in progress. Are you sure you want to leave?';
    }
});
</script>

<style>
.upload-area {
    transition: all 0.3s ease;
    cursor: pointer;
    background-color: #f8f9fa;
}

.upload-area:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
    border-color: var(--bs-primary) !important;
}

.upload-area.dragover {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    border-color: var(--bs-primary) !important;
    transform: scale(1.02);
}

.progress {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    border-radius: 10px;
}

.progress-bar {
    transition: width 0.6s ease;
}

#processingIcon.fa-spin {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Custom animations */
.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    0% { opacity: 0; transform: translateY(-10px); }
    100% { opacity: 1; transform: translateY(0); }
}
</style>

@endsection