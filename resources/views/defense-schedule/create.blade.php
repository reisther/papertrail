<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Schedule Defense
            </h2>
            <a href="{{ route('defense-schedule.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                ← Back to Calendar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('defense-schedule.store') }}" class="space-y-6">
                        @csrf
                        
                        <!-- Defense Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Defense Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., Final Defense - John Doe">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Student Selection -->
                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Student <span class="text-red-500">*</span>
                            </label>
                            <select id="student_id" 
                                    name="student_id" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="loadStudentProjects()">
                                <option value="">Select a student</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                        {{ $student->name }} ({{ $student->course }})
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Adviser Selection -->
                        <div>
                            <label for="adviser_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Adviser <span class="text-red-500">*</span>
                            </label>
                            <select id="adviser_id" 
                                    name="adviser_id" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select an adviser</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('adviser_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }} ({{ $teacher->course }})
                                    </option>
                                @endforeach
                            </select>
                            @error('adviser_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Project Selection -->
                        <div>
                            <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Project (Optional)
                            </label>
                            <select id="project_id" 
                                    name="project_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select a project</option>
                            </select>
                            @error('project_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Defense Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                Defense Type <span class="text-red-500">*</span>
                            </label>
                            <select id="type" 
                                    name="type" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select defense type</option>
                                <option value="proposal" {{ old('type') === 'proposal' ? 'selected' : '' }}>Proposal Defense</option>
                                <option value="final" {{ old('type') === 'final' ? 'selected' : '' }}>Final Defense</option>
                                <option value="oral_exam" {{ old('type') === 'oral_exam' ? 'selected' : '' }}>Oral Examination</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Date and Time -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" 
                                       id="start_time" 
                                       name="start_time" 
                                       value="{{ old('start_time') }}"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('start_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    End Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" 
                                       id="end_time" 
                                       name="end_time" 
                                       value="{{ old('end_time') }}"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('end_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Location -->
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                Location
                            </label>
                            <input type="text" 
                                   id="location" 
                                   name="location" 
                                   value="{{ old('location') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., Conference Room A, Building 1">
                            @error('location')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Meeting Platform Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Meeting Platform
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="meeting_platform" 
                                           value="manual" 
                                           {{ old('meeting_platform', 'manual') === 'manual' ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                           onchange="toggleMeetingOptions()">
                                    <span class="ml-2 text-sm text-gray-700">Manual Link Entry</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="meeting_platform" 
                                           value="google_meet" 
                                           {{ old('meeting_platform') === 'google_meet' ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                           onchange="toggleMeetingOptions()">
                                    <span class="ml-2 text-sm text-gray-700">
                                        <svg class="w-4 h-4 inline mr-1" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        Google Meet (Auto-generate)
                                    </span>
                                </label>
                            </div>
                            @error('meeting_platform')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Google Meet Options -->
                        <div id="google-meet-options" class="hidden">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-900 mb-2">Google Meet Integration</h4>
                                        <p class="text-sm text-blue-700 mb-3">
                                            A Google Meet link will be automatically created and calendar invites will be sent to all participants.
                                        </p>
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="auto_create_meet" 
                                                   value="1"
                                                   {{ old('auto_create_meet') ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <span class="ml-2 text-sm text-blue-700">Send calendar invites to participants</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Meeting Link -->
                        <div id="manual-meeting-link">
                            <label for="meeting_link" class="block text-sm font-medium text-gray-700 mb-2">
                                Online Meeting Link (Optional)
                            </label>
                            <input type="text"
                                   id="meeting_link"
                                   name="meeting_link"
                                   value="{{ old('meeting_link') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="https://meet.google.com/abc-defg-hij">
                            <p class="mt-1 text-sm text-gray-500">
                                Paste a real meeting link. Random Google Meet codes cannot be joined unless Google creates the meeting.
                            </p>
                            @error('meeting_link')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Panel Members -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Panel Members (Optional)
                            </label>
                            <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-300 rounded-md p-3">
                                @foreach($teachers as $teacher)
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="panel_members[]" 
                                               value="{{ $teacher->id }}"
                                               {{ in_array($teacher->id, old('panel_members', [])) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-700">{{ $teacher->name }} ({{ $teacher->course }})</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('panel_members')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Additional details about the defense...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Internal Notes
                            </label>
                            <textarea id="notes" 
                                      name="notes" 
                                      rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Private notes for organizers...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <a href="{{ route('defense-schedule.index') }}" 
                               class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition-colors">
                                Schedule Defense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadStudentProjects() {
            const studentId = document.getElementById('student_id').value;
            const projectSelect = document.getElementById('project_id');
            
            // Clear existing options
            projectSelect.innerHTML = '<option value="">Select a project</option>';
            
            if (studentId) {
                fetch(`/students/${studentId}/projects`)
                    .then(response => response.json())
                    .then(projects => {
                        projects.forEach(project => {
                            const option = document.createElement('option');
                            option.value = project.id;
                            option.textContent = project.title;
                            projectSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading projects:', error);
                    });
            }
        }

        // Auto-fill end time when start time is selected (default 2 hours)
        document.getElementById('start_time').addEventListener('change', function() {
            const startTime = new Date(this.value);
            if (startTime) {
                const endTime = new Date(startTime.getTime() + (2 * 60 * 60 * 1000)); // Add 2 hours
                const endTimeString = endTime.toISOString().slice(0, 16);
                document.getElementById('end_time').value = endTimeString;
            }
        });

        // Toggle meeting options based on platform selection
        function toggleMeetingOptions() {
            const googleMeetRadio = document.querySelector('input[name="meeting_platform"][value="google_meet"]');
            const googleMeetOptions = document.getElementById('google-meet-options');
            const manualMeetingLink = document.getElementById('manual-meeting-link');
            
            if (googleMeetRadio && googleMeetRadio.checked) {
                googleMeetOptions.classList.remove('hidden');
                manualMeetingLink.classList.add('hidden');
            } else {
                googleMeetOptions.classList.add('hidden');
                manualMeetingLink.classList.remove('hidden');
            }
        }

        // Pre-fill date if passed from calendar
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const selectedDate = urlParams.get('date');
            
            if (selectedDate) {
                // Set the date part of the datetime-local input
                const startTimeInput = document.getElementById('start_time');
                const endTimeInput = document.getElementById('end_time');
                
                // Set default time to 9:00 AM
                const startDateTime = selectedDate + 'T09:00';
                const endDateTime = selectedDate + 'T11:00'; // 2 hours later
                
                startTimeInput.value = startDateTime;
                endTimeInput.value = endDateTime;
            }

            // Initialize meeting options display
            toggleMeetingOptions();
        });
    </script>
</x-app-layout>
