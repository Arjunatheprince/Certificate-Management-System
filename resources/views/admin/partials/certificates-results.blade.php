@php
    $sortIcon = function (string $column) use ($sortBy, $sortDir) {
        if ($sortBy !== $column) {
            return 'bi-arrow-down-up';
        }

        return $sortDir === 'asc' ? 'bi-sort-down' : 'bi-sort-up';
    };

    $sortUrl = function (string $column) use ($sortBy, $sortDir) {
        $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';

        return route('admin.certificates', array_merge(request()->query(), [
            'sort_by' => $column,
            'sort_dir' => $nextDir,
        ]));
    };

    $highlight = function (?string $value) use ($search) {
        $text = (string) $value;
        $escapedText = e($text);

        if ($search === '') {
            return $escapedText;
        }

        $pattern = '/' . preg_quote($search, '/') . '/iu';

        return preg_replace($pattern, '<mark class="bg-amber-100 text-amber-900 px-1 rounded">$0</mark>', $escapedText) ?? $escapedText;
    };
@endphp

<div class="overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('name') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>Nama</span>
                        <i class="bi {{ $sortIcon('name') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('npm') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>NPM</span>
                        <i class="bi {{ $sortIcon('npm') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('certificate_number') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>Nomor Sertifikat</span>
                        <i class="bi {{ $sortIcon('certificate_number') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('certificate_type') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>Jenis</span>
                        <i class="bi {{ $sortIcon('certificate_type') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ $sortUrl('created_at') }}" class="inline-flex items-center gap-2 hover:text-blue-600 transition">
                        <span>Tanggal</span>
                        <i class="bi {{ $sortIcon('created_at') }}"></i>
                    </a>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SHA-256</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($certificates as $cert)
            @php
                $user = auth()->user();
                $isOwner = (int) $cert->created_by === (int) auth()->id();
                $canEdit = $user->isSuperAdmin() || ($user->isAdmin() && $isOwner);
                $canDelete = $user->isSuperAdmin();
            @endphp
            <tr class="hover:bg-gray-50 {{ !$isOwner ? 'opacity-90' : '' }}">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                    {!! $highlight($cert->nama) !!}
                    @if(!$isOwner)
                    <span class="ml-1 text-xs text-gray-400" title="Diupload oleh {{ $cert->uploader?->name ?? 'Admin lain' }}">
                        <i class="bi bi-person"></i> {{ $cert->uploader?->name ?? 'Admin lain' }}
                    </span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{!! $highlight($cert->npm) !!}</td>
                <td class="px-6 py-4 text-sm text-gray-600 font-mono">{!! $highlight($cert->nomor_sertifikat) !!}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{!! $highlight($cert->jenis_sertifikat) !!}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $cert->tanggal_terbit }}</td>
                <td class="px-6 py-4">
                    @if($cert->file_hash)
                    <span class="text-xs font-mono text-gray-400 bg-gray-50 px-2 py-1 rounded" title="{{ $cert->file_hash }}">
                        {{ substr($cert->file_hash, 0, 12) }}...
                    </span>
                    @else
                    <span class="text-xs text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="/{{ $cert->file_path }}" target="_blank" class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100" title="Lihat Sertifikat">
                            Lihat
                        </a>
                        @if($cert->qr_code_path)
                        <a href="/{{ $cert->qr_code_path }}" target="_blank" class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700 transition hover:bg-green-100" title="Lihat QR Code">
                            Lihat QR
                        </a>
                        @endif
                        @if($canEdit)
                        <a href="{{ route('admin.certificates.edit', $cert) }}" class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100" title="Edit Sertifikat">
                            Edit
                        </a>
                        @else
                        <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed" title="Admin hanya dapat mengedit sertifikat yang diupload sendiri">
                            Edit
                        </span>
                        @endif
                        @if($canDelete)
                        <form method="POST" action="{{ route('admin.certificates.delete', $cert) }}" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="delete-cert-btn inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100" title="Hapus Sertifikat" data-name="{{ $cert->nama }}">
                                Hapus
                            </button>
                        </form>
                        @else
                        <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed" title="Hanya super admin yang dapat menghapus">
                            Hapus
                        </span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                    {{ $search !== '' || $type !== '' || $owner !== 'all' || $dateFrom !== '' || $dateTo !== '' ? 'Tidak ada sertifikat yang cocok dengan filter' : 'Belum ada sertifikat' }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-6 border-t flex justify-between items-center">
    {{ $certificates->links() }}
    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
        <span>Kembali</span>
    </a>
</div>
