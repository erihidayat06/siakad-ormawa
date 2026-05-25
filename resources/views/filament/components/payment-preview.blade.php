<div class="space-y-2">
    {{-- Header --}}
    <div class="flex justify-between items-center bg-gray-100 p-2 rounded-t-lg border border-gray-200">
        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider pl-2">
            Pratinjau Bukti Pembayaran
        </span>

        @if ($getState())
            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($getState()) }}" target="_blank"
                class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-500 transition shadow-sm">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Buka Ukuran Penuh
            </a>
        @endif
    </div>

    {{-- Image Container --}}
    <div
        class="w-full border-b border-l border-r border-gray-200 rounded-b-lg overflow-hidden shadow-sm bg-white flex justify-center p-4">
        @if ($getState())
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($getState()) }}"
                style="width: 100%; max-height: 600px; object-fit: contain;"
                class="rounded-lg shadow-sm border border-gray-100">
        @else
            <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm italic">Bukti pembayaran belum tersedia.</p>
            </div>
        @endif
    </div>
</div>
