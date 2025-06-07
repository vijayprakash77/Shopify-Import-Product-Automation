@if(session('success') || session('error'))
  <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999">
    
    @if(session('success'))
      <div id="toast-success" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background-color:#00b33c;">
        <div class="d-flex">
          <div class="toast-body">
            {{ session('success') }}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    @endif

    @if(session('error'))
      <div id="toast-error" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background-color:#ff5c33;">
        <div class="d-flex">
          <div class="toast-body">
            {{ session('error') }}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    @endif

  </div>
@endif


    <script>
  window.addEventListener('DOMContentLoaded', (event) => {
    const successToast = document.getElementById('toast-success');
    const errorToast = document.getElementById('toast-error');

    if (successToast) {
      new bootstrap.Toast(successToast, { delay: 3000 }).show();
    }

    if (errorToast) {
      new bootstrap.Toast(errorToast, { delay: 3000 }).show();
    }
  });
</script>
