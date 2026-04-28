@if(session('success'))
<div id="notif-success" class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
    <div class="flex-1">
        <p class="text-sm leading-6 text-emerald-800">{{ session('success') }}</p>
    </div>
    <button type="button" data-dismiss-target="notif-success" class="text-sm font-medium text-emerald-600 transition hover:text-emerald-800">
        Tutup
    </button>
</div>
@endif

@if(session('error'))
<div id="notif-error" class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
    <div class="flex-1">
        <p class="text-sm leading-6 text-red-800">{{ session('error') }}</p>
    </div>
    <button type="button" data-dismiss-target="notif-error" class="text-sm font-medium text-red-600 transition hover:text-red-800">
        Tutup
    </button>
</div>
@endif

@if($errors->any())
<div id="notif-validation" class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
    <div class="flex-1">
        <p class="text-sm leading-6 text-amber-800">{{ $errors->first() }}</p>
    </div>
    <button type="button" data-dismiss-target="notif-validation" class="text-sm font-medium text-amber-700 transition hover:text-amber-900">
        Tutup
    </button>
</div>
@endif

