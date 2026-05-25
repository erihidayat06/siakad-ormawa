<div class="space-y-2">
    {{-- Header untuk Label dan Tombol Open Full --}}
    <div
        class="flex justify-between items-center bg-gray-100 p-2 rounded-t-lg border-t border-l border-r border-gray-200">
        <span class="text-sm font-bold text-gray-700 uppercase tracking-tight">
            {{ $getLabel() }}
        </span>

        @if ($getState())
            <a href="{{ asset('storage/' . $getState()) }}" target="_blank"
                class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-primary-600 rounded hover:bg-primary-500 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Buka Full Screen (Tab Baru)
            </a>
        @endif
    </div>

    {{-- Iframe Container --}}
    <div class="w-full border border-gray-200 rounded-b-lg overflow-hidden shadow-sm bg-gray-50">
        @if ($getState())
            <iframe src="{{ asset('storage/' . $getState()) }}#toolbar=1"
                style="width: 100%; height: 850px; border: none;" allow="autoplay">
            </iframe>
        @else
            <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm">File belum diunggah atau tidak ditemukan.</p>
            </div>
        @endif
    </div>
</div>
