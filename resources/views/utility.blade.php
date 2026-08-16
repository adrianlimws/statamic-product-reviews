<div class="card p-0 overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
        <div>
            <h2 class="font-bold text-lg">{{ $title }}</h2>
            <p class="text-gray-600 text-sm mt-1">{{ $description }}</p>
        </div>
        <form method="POST" action="{{ $syncUrl }}">
            @csrf
            <button type="submit" class="btn-primary" @if (! $configured) disabled @endif>
                Sync Now
            </button>
        </form>
    </div>

    <div class="p-4 space-y-3 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-600">Provider</span>
            <span class="font-medium">{{ $provider }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">Configured</span>
            <span class="font-medium">{{ $configured ? 'Yes' : 'No' }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">Last sync</span>
            <span class="font-medium">{{ $status['synced_at'] ?? 'Never' }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600">Last result</span>
            <span class="font-medium">
                @if ($status['success'] === null)
                    —
                @elseif ($status['success'])
                    Success
                @else
                    Failed
                @endif
            </span>
        </div>
        @if (! empty($status['error']))
            <div class="rounded bg-red-100 text-red-800 p-3">
                {{ $status['error'] }}
            </div>
        @endif
        @if ($status['synced_at'])
            <div class="grid grid-cols-4 gap-3 pt-2">
                <div>
                    <div class="text-gray-600">Created</div>
                    <div class="font-bold text-lg">{{ $status['created'] ?? 0 }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Updated</div>
                    <div class="font-bold text-lg">{{ $status['updated'] ?? 0 }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Skipped</div>
                    <div class="font-bold text-lg">{{ $status['skipped'] ?? 0 }}</div>
                </div>
                <div>
                    <div class="text-gray-600">Unpublished</div>
                    <div class="font-bold text-lg">{{ $status['unpublished'] ?? 0 }}</div>
                </div>
            </div>
        @endif
        @unless ($configured)
            <div class="rounded bg-amber-50 text-amber-900 p-3">
                Set <code>PRODUCT_REVIEWS_YOTPO_APP_KEY</code> and
                <code>PRODUCT_REVIEWS_YOTPO_SECRET</code> in your <code>.env</code> file.
            </div>
        @endunless
    </div>
</div>
