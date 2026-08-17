@php
    $syncedAt = $status['synced_at'] ?? null;
    $syncedLabel = $syncedAt
        ? \Carbon\Carbon::parse($syncedAt)->timezone(config('app.timezone'))->toDayDateTimeString()
        : 'Never';
    $success = $status['success'] ?? null;
    $resultLabel = $success === null ? '—' : ($success ? 'Success' : 'Failed');
    $resultClass = $success === null
        ? 'text-gray-400'
        : ($success ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400');
    $errorMessage = session('error') ?: ($status['error'] ?? null);
    $syncing = $status['syncing'] ?? false;
    $scheduleLabel = str_replace('_', ' ', $schedule ?? 'daily');
    $sourceLabel = $yotpoSource ?? 'auto';
@endphp

<div class="flex flex-col gap-4">
    <div class="card p-0 overflow-hidden">
        <div class="flex w-full flex-wrap items-center justify-between gap-4 border-b border-gray-200 px-4 py-4 dark:border-gray-700">
            <div class="min-w-0 flex-1">
                <h2 class="text-base font-medium text-gray-900 dark:text-white">{{ $title }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            </div>

            <div class="ml-auto flex shrink-0 flex-wrap items-center justify-end gap-2">
                <form method="POST" action="{{ $testUrl }}">
                    @csrf
                    <button
                        type="submit"
                        @disabled(! $configured)
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:focus:ring-white"
                        @if (! $configured) title="Add Yotpo credentials to .env first" @endif
                    >
                        Test Connection
                    </button>
                </form>

                <form method="POST" action="{{ $syncUrl }}">
                    @csrf
                    <button
                        type="submit"
                        @disabled(! $configured)
                        class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 dark:focus:ring-white"
                        @if (! $configured) title="Add Yotpo credentials to .env first" @endif
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4" aria-hidden="true">
                            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H4.39a.75.75 0 0 0-.75.75v3.842a.75.75 0 0 0 1.5 0v-2.14l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm-9.88-3.91a.75.75 0 0 0 1.45-.388A5.5 5.5 0 0 1 16.19 5.77l.312.31h-2.433a.75.75 0 0 0 0 1.5h3.842a.75.75 0 0 0 .75-.75V3.5a.75.75 0 0 0-1.5 0v2.14l-.31-.31A7 7 0 0 0 5.432 7.514Z" clip-rule="evenodd" />
                        </svg>
                        Sync Now
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">Status</td>
                        <td class="px-4 py-3">
                            @if ($configured)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Connected</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">Needs setup</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">Provider</td>
                        <td class="px-4 py-3 font-medium capitalize text-gray-900 dark:text-white">{{ $provider }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">API source</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $sourceLabel }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">Schedule</td>
                        <td class="px-4 py-3 font-medium capitalize text-gray-900 dark:text-white">{{ $scheduleLabel }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">Collection</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            @if ($collectionUrl)
                                <a href="{{ $collectionUrl }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $collection }}</a>
                            @else
                                {{ $collection }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">Last sync</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $syncedLabel }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">Last result</td>
                        <td class="px-4 py-3 font-medium {{ $resultClass }}">
                            @if ($syncing)
                                <span class="text-blue-600 dark:text-blue-400">Syncing…</span>
                            @else
                                {{ $resultLabel }}
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="overflow-x-auto border-t border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-medium tracking-wide text-gray-500 uppercase dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                        <th class="px-4 py-3 font-medium">Created</th>
                        <th class="px-4 py-3 font-medium">Updated</th>
                        <th class="px-4 py-3 font-medium">Skipped</th>
                        <th class="px-4 py-3 font-medium">Unpublished</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-4 text-xl font-semibold text-gray-900 dark:text-white">{{ $status['created'] ?? 0 }}</td>
                        <td class="px-4 py-4 text-xl font-semibold text-gray-900 dark:text-white">{{ $status['updated'] ?? 0 }}</td>
                        <td class="px-4 py-4 text-xl font-semibold text-gray-900 dark:text-white">{{ $status['skipped'] ?? 0 }}</td>
                        <td class="px-4 py-4 text-xl font-semibold text-gray-900 dark:text-white">{{ $status['unpublished'] ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if (session('success'))
            <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                <div class="rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if ($errorMessage)
            <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                <div class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                    <p class="font-medium">Sync error</p>
                    <p class="mt-1 break-words">{{ $errorMessage }}</p>
                </div>
            </div>
        @endif

        @unless ($configured)
            <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                    <p class="font-medium">Connect Yotpo</p>
                    <p class="mt-1">Add these to your <code class="rounded bg-amber-100 px-1 py-0.5 text-xs dark:bg-amber-900">.env</code>, then reload:</p>
                    <pre class="mt-3 overflow-x-auto rounded-md bg-white/80 p-3 text-xs leading-relaxed text-gray-800 dark:bg-black/30 dark:text-gray-100">PRODUCT_REVIEWS_YOTPO_APP_KEY=your-app-key
PRODUCT_REVIEWS_YOTPO_SECRET=your-api-secret</pre>
                </div>
            </div>
        @endunless
    </div>
</div>
