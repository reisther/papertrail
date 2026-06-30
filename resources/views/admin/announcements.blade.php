<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Announcement') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8" x-data="{ 
        openModal: false, 
        posts: [], 
        currentMessage: '', 
        currentAttachment: null, 
        editingPostId: null,
        activeDropdownId: null 
    }">
        <div class="max-w-7xl mx-auto relative">
            
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight leading-none">Admin Announcement</h1>
                    <p class="text-sm text-gray-500 mt-2 font-normal">Manuscript template updates, schedule changes, and more.</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 bg-[#e2e8f0] hover:bg-gray-300 text-gray-700 px-4 py-1.5 rounded-lg text-xs font-semibold transition shadow-sm">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"></path>
                    </svg>
                    Back
                </a>
            </div>

            <div class="flex justify-center items-center pt-6 pb-10">
                <button @click="editingPostId = null; currentMessage = ''; currentAttachment = null; openModal = true" 
                        type="button"
                        style="background-color: #4b75b0 !important; width: 280px !important; height: 46px !important; opacity: 1 !important; display: flex !important;"
                        class="items-center justify-center gap-2 text-white rounded-xl shadow-md hover:brightness-95 transition-all font-semibold text-sm border-none outline-none mx-auto">
                    <svg class="w-4 h-4 text-white opacity-100 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"></path>
                    </svg>
                    <span class="text-white opacity-100 font-semibold whitespace-nowrap">New Announcement</span>
                </button>
            </div>

            <div class="space-y-4 max-w-5xl mx-auto mt-4">
                <template x-for="(post, index) in posts" :key="post.id">
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 flex gap-4 items-start overflow-visible relative">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white shrink-0 mt-0.5">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        
                        <div class="w-full flex flex-col justify-start">
                            <div class="flex justify-between items-start w-full mb-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-800 leading-none">Admin</h4>
                                    <span class="text-xs text-gray-400 mt-1.5 block" x-text="post.date"></span>
                                </div>

                                <div class="relative" @click.away="if(activeDropdownId === post.id) activeDropdownId = null">
                                    <button @click="activeDropdownId = (activeDropdownId === post.id ? null : post.id)" type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded-full hover:bg-gray-100 transition duration-150">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 10.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0-6a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 12a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                        </svg>
                                    </button>

                                    <div x-show="activeDropdownId === post.id" 
                                         x-cloak 
                                         class="absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                        <button @click="activeDropdownId = null; editingPostId = post.id; currentMessage = post.message; currentAttachment = post.attachment; openModal = true;" type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-medium transition flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button @click="activeDropdownId = null; posts = posts.filter(p => p.id !== post.id);" type="button" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium transition flex items-center gap-2">
                                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 mb-3 whitespace-pre-wrap font-medium" x-text="post.message"></p>
                            
                            <template x-if="post.attachment">
                                <div class="inline-flex items-center gap-1.5 text-xs text-[#1d5aa8] font-bold hover:underline cursor-pointer bg-blue-50/50 px-2.5 py-1 rounded-md self-start">
                                    <svg class="w-3.5 h-3.5 text-[#1d5aa8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32a1.5 1.5 0 01-2.121-2.121l7.694-7.694" />
                                    </svg>
                                    <span x-text="post.attachment.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

        </div>

        <div x-show="openModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             @keydown.escape.window="openModal = false">
            
            <div class="bg-white w-full max-w-3xl rounded-2xl p-8 shadow-xl border border-gray-100 relative flex flex-col justify-start" @click.away="openModal = false">
                
                <div class="w-full mb-5">
                    <textarea 
                        x-model="currentMessage"
                        rows="4" 
                        placeholder="Announce something..." 
                        class="w-full text-base text-gray-700 border border-gray-300 rounded-md p-4 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none placeholder-gray-400 font-medium shadow-inner"
                    ></textarea>
                </div>

                <div class="flex items-center gap-3 w-full mb-6 pl-0.5">
                    <label class="inline-flex items-center gap-2 text-base text-[#1d5aa8] font-bold cursor-pointer hover:text-blue-800 transition select-none">
                        <svg class="w-5 h-5 text-[#1d5aa8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32a1.5 1.5 0 01-2.121-2.121l7.694-7.694" />
                        </svg>
                        <span>Add attachment</span>
                        <input type="file" class="hidden" @change="currentAttachment = $event.target.files[0]">
                    </label>
                    <span x-show="currentAttachment" class="text-sm text-gray-500 font-medium ml-2" x-text="currentAttachment ? currentAttachment.name : ''"></span>
                </div>

                <div class="flex justify-center w-full">
                    <button 
                        @click="if(currentMessage.trim() !== '') { 
                            if(editingPostId !== null) {
                                posts = posts.map(p => p.id === editingPostId ? { ...p, message: currentMessage, attachment: currentAttachment } : p);
                            } else {
                                posts.push({
                                    id: Date.now(),
                                    message: currentMessage,
                                    attachment: currentAttachment,
                                    date: new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
                                });
                            }
                            openModal = false; 
                            currentMessage = '';
                            currentAttachment = null;
                            editingPostId = null;
                        }"
                        type="button"
                        style="background-color: #4b75b0 !important; width: 140px !important; height: 44px !important; opacity: 1 !important;"
                        class="text-white rounded-xl font-bold shadow-md hover:brightness-95 transition text-base border-none outline-none flex items-center justify-center">
                        Post
                    </button>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>