<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Defense Schedule
            </h2>
            <a href="{{ route('defense-schedule.show', $defenseSchedule) }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                ← Back to Details
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('defense-schedule.update', $defenseSchedule) }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <!-- Defense Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Defense Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $defenseSchedule->title) }}"
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
                                    <option value="{{ $student->id }}" {{ old('student_id', $defenseSchedule->student_id) == $student->id ? 'selected' : '' }}>
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
                                    <option value="{{ $teacher->id }}" {{ old('adviser_id', $defenseSchedule->adviser_id) == $teacher->id ? 'selected' : '' }}>
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
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id', $defenseSchedule->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Defense Type and Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Defense Type <span class="text-red-500">*</span>
                                </label>
                                <select id="type" 
                                        name="type" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select defense type</option>
                                    <option value="proposal" {{ old('type', $defenseSchedule->type) === 'proposal' ? 'selected' : '' }}>Proposal Defense</option>
                                    <option value="final" {{ old('type', $defenseSchedule->type) === 'final' ? 'selected' : '' }}>Final Defense</option>
                                    <option value="oral_exam" {{ old('type', $defenseSchedule->type) === 'oral_exam' ? 'selected' : '' }}>Oral Examination</option>
                                </select>
                                @error('type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="status" 
                                        name="status" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="scheduled" {{ old('status', $defenseSchedule->status) === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="completed" {{ old('status', $defenseSchedule->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('status', $defenseSchedule->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    <option value="rescheduled" {{ old('status', $defenseSchedule->status) === 'rescheduled' ? 'selected' : '' }}>Rescheduled</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
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
                                       value="{{ old('start_time', $defenseSchedule->start_time->format('Y-m-d\TH:i')) }}"
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
                                       value="{{ old('end_time', $defenseSchedule->end_time->format('Y-m-d\TH:i')) }}"
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
                                   value="{{ old('location', $defenseSchedule->location) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., Conference Room A, Building 1">
                            @error('location')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Meeting Link -->
                        <div>
                            <label for="meeting_link" class="block text-sm font-medium text-gray-700 mb-2">
                                Online Meeting Link (Optional)
                            </label>
                            <input type="text" 
                                   id="meeting_link" 
                                   name="meeting_link" 
                                   value="{{ old('meeting_link', $defenseSchedule->meeting_link) }}"
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
                                               {{ in_array($teacher->id, old('panel_members', $defenseSchedule->panel_members ?? [])) ? 'checked' : '' }}
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
                                      placeholder="Additional details about the defense...">{{ old('description', $defenseSchedule->description) }}</textarea>
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
                                      placeholder="Private notes for organizers...">{{ old('notes', $defenseSchedule->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <a href="{{ route('defense-schedule.show', $defenseSchedule) }}" 
                               class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition-colors">
                                Update Schedule
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
            const currentProjectId = '{{ $defenseSchedule->project_id }}';
            
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
                            if (project.id == currentProjectId) {
                                option.selected = true;
                            }
                            projectSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading projects:', error);
                    });
            }
        }

        // Auto-fill end time when start time is changed (default 2 hours)
        document.getElementById('start_time').addEventListener('change', function() {
            const startTime = new Date(this.value);
            const endTimeInput = document.getElementById('end_time');
            
            // Only auto-fill if end time is empty or if start time is after current end time
            if (startTime && (!endTimeInput.value || new Date(endTimeInput.value) <= startTime)) {
                const endTime = new Date(startTime.getTime() + (2 * 60 * 60 * 1000)); // Add 2 hours
                const endTimeString = endTime.toISOString().slice(0, 16);
                endTimeInput.value = endTimeString;
            }
        });

        // Load projects on page load if student is already selected
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('student_id').value) {
                loadStudentProjects();
            }
        });
    </script>
</x-app-layout>
