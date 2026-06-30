<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Student Requests</h2>
                        <div class="flex space-x-2">
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                {{ $pendingRequests->count() }} Pending
                            </span>
                        </div>
                    </div>


                    <!-- Request Cards -->
                    @if ($pendingRequests->count() > 0)
                        <div>
                            <div class="space-y-4">
                                @foreach ($pendingRequests as $request)
                                    @php
                                        $group = $request->student->ownedProjects->first();
                                    @endphp
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-4 mb-3">
                                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-lg font-semibold text-gray-900">{{ $request->student->name }}</h4>
                                                        <p class="text-sm text-gray-600">{{ $request->student->course }} - {{ $request->student->section }}</p>
                                                    </div>
                                                </div>

                                                <div class="mb-4 bg-white border border-yellow-100 rounded-lg p-4">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-yellow-700">Group {{ $group ? '#'.$group->id : 'not created yet' }}</p>
                                                    <p class="mt-1 text-base font-semibold text-gray-900">{{ $group->title ?? 'No group title yet' }}</p>
                                                    <p class="mt-1 text-sm text-gray-600 whitespace-pre-line">{{ $group?->description ?: 'No group description yet.' }}</p>
                                                </div>
                                                
                                                @if ($request->message)
                                                    <div class="mb-4">
                                                        <p class="text-sm font-medium text-gray-700 mb-1">Message from student:</p>
                                                        <p class="text-sm text-gray-600 bg-white p-3 rounded border">{{ $request->message }}</p>
                                                    </div>
                                                @endif
                                                
                                                <p class="text-xs text-gray-500">Requested on {{ $request->created_at->format('M j, Y \a\t g:i A') }}</p>
                                            </div>
                                            
                                            <div class="ml-6 flex space-x-2">
                                                @if($group)
                                                    <a href="{{ route('group-description.details', $group) }}"
                                                       class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 px-4 py-2 rounded-md text-sm transition-colors">
                                                        Show
                                                    </a>
                                                @endif
                                                <button onclick="openResponseModal({{ $request->id }}, @js($request->student->name), 'approved')" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm transition-colors">
                                                    Accept
                                                </button>
                                                <button onclick="openResponseModal({{ $request->id }}, @js($request->student->name), 'rejected')" 
                                                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm transition-colors">
                                                    Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">No student requests yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <span id="actionText"></span> Request from <span id="studentName"></span>
                </h3>
                
                <form method="POST" id="responseForm">
                    @csrf
                    <input type="hidden" id="responseStatus" name="status" value="">
                    
                    <div class="mb-4">
                        <label for="response_message" class="block text-sm font-medium text-gray-700 mb-2">
                            Response Message (Optional)
                        </label>
                        <textarea id="response_message" name="response_message" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Add a message to the student..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeResponseModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn"
                                class="px-4 py-2 text-white rounded-md">
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openResponseModal(requestId, studentName, status) {
            document.getElementById('responseForm').action = `/advisers/respond/${requestId}`;
            document.getElementById('responseStatus').value = status;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('actionText').textContent = status === 'approved' ? 'Accept' : 'Reject';
            
            const submitBtn = document.getElementById('submitBtn');
            if (status === 'approved') {
                submitBtn.className = 'px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md';
            } else {
                submitBtn.className = 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md';
            }
            
            document.getElementById('responseModal').classList.remove('hidden');
        }

        function closeResponseModal() {
            document.getElementById('responseModal').classList.add('hidden');
            document.getElementById('response_message').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('responseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeResponseModal();
            }
        });
    </script>
</x-app-layout>
