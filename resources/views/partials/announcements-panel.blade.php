@php
    $dashboardAnnouncements = \Illuminate\Support\Facades\Schema::hasTable('announcements')
        ? \App\Models\Announcement::with('author')
            ->latest()
            ->take(3)
            ->get()
        : collect();
@endphp

<div class="mb-6 rounded-2xl border border-blue-100 bg-blue-50 p-5">
    <div class="mb-4 flex items-center justify-between gap-4">
        <div>
            <h4 class="font-bold text-blue-900">Announcements</h4>
            <p class="text-sm text-blue-700">Important updates from the admin.</p>
        </div>
        @if(auth()->user()?->role === 'Admin')
            <a href="{{ route('admin.announcements') }}" class="shrink-0 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                Manage
            </a>
        @endif
    </div>

    <div class="space-y-3">
        @if($dashboardAnnouncements->isNotEmpty())
            @foreach($dashboardAnnouncements as $announcement)
                <div class="rounded-xl border border-blue-100 bg-white p-4">
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-900">{{ $announcement->author?->name ?? 'Admin' }}</p>
                        <p class="shrink-0 text-xs text-gray-400">{{ $announcement->created_at->diffForHumans() }}</p>
                    </div>
                    <p class="whitespace-pre-line text-sm text-gray-700">{{ $announcement->message }}</p>
                    @if($announcement->attachment_path)
                        <a href="{{ route('announcements.attachment', $announcement) }}" class="mt-3 inline-flex max-w-full items-center gap-2 truncate rounded-md bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100">
                            {{ $announcement->attachment_name ?? 'Attachment' }}
                        </a>
                    @endif
                </div>
            @endforeach
        @else
            <div class="rounded-xl border border-dashed border-blue-200 bg-white/70 p-4 text-sm text-blue-700">
                No announcements yet.
            </div>
        @endif
    </div>
</div>
