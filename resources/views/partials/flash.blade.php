@if (session('status'))
    <div class="alert alert-light border-1 border-left-3 border-left-success d-flex" role="alert">
        <i class="material-icons text-success mr-3">check_circle</i>
        <div class="text-body">{{ session('status') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-light border-1 border-left-3 border-left-danger d-flex" role="alert">
        <i class="material-icons text-danger mr-3">error_outline</i>
        <div class="text-body">{{ session('error') }}</div>
    </div>
@endif
