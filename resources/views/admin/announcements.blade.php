<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Announcements') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight leading-none">Admin Announcements</h1>
                    <p class="text-sm text-gray-500 mt-2">Posts here appear on every user's dashboard.</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-xs font-semibold transition">
                    Back
                </a>
            </div>

            @if(session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-bold text-gray-900">New Announcement</h3>
                <form method="POST" action="{{ route('admin.announcements.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="message" class="block text-sm font-semibold text-gray-700">Message</label>
                        <textarea id="message" name="message" rows="4" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Announce something important...">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="attachment" class="block text-sm font-semibold text-gray-700">Attachment</label>
                        <input id="attachment" name="attachment" type="file" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                        @error('attachment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Post
                        </button>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                @forelse($announcements as $announcement)
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $announcement->author?->name ?? 'Admin' }}</p>
                                <p class="text-xs text-gray-400">{{ $announcement->created_at->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">
                                    Delete
                                </button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            @method('PATCH')
                            <textarea name="message" rows="3" required class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old("message_{$announcement->id}", $announcement->message) }}</textarea>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    @if($announcement->attachment_path)
                                        <a href="{{ route('announcements.attachment', $announcement) }}" class="inline-flex max-w-full items-center gap-2 truncate rounded-md bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100">
                                            {{ $announcement->attachment_name ?? 'Attachment' }}
                                        </a>
                                    @else
                                        <p class="text-xs text-gray-400">No attachment</p>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <input name="attachment" type="file" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs">
                                    <button type="submit" class="rounded-lg bg-gray-800 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-900">
                                        Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center text-gray-500">
                        No announcements yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
