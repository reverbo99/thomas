 <!DOCTYPE html>
 <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>{{ __('all.highlink_isgc') }} - @yield('title')</title>
     <script>
         (function () {
             try {
                 var stored = localStorage.getItem('hl-theme');
                 var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                 var theme = stored || (prefersDark ? 'dark' : 'light');
                 if (theme === 'dark') document.documentElement.classList.add('dark');
                 else document.documentElement.classList.remove('dark');
             } catch (e) {}
         })();
     </script>
     <!-- Tailwind CSS CDN -->
     <script src="https://cdn.tailwindcss.com"></script>
     <script>
         tailwind.config = { darkMode: 'class' };
     </script>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
     <style>
         html.dark { color-scheme: dark; }
         /* Custom scrollbar for sidebar */
         .sidebar::-webkit-scrollbar {
             width: 6px;
         }

         .sidebar::-webkit-scrollbar-track {
             background: transparent;
         }

         .sidebar::-webkit-scrollbar-thumb {
             background: rgba(255, 255, 255, 0.3);
             border-radius: 3px;
         }

         .sidebar::-webkit-scrollbar-thumb:hover {
             background: rgba(255, 255, 255, 0.5);
         }
     </style>
 </head>

 <body class="bg-gray-100 text-gray-900 font-sans dark:bg-slate-900 dark:text-gray-100">
     <!-- Sidebar Overlay (for mobile) -->
     <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-[999] hidden"></div>

     <div class="flex min-h-screen">
         <!-- Sidebar -->
         <nav id="sidebar"
             class="sidebar fixed top-0 bottom-0 left-0 w-64 bg-teal-800/80 dark:bg-teal-900 backdrop-blur-lg text-white p-4 overflow-y-auto transition-all duration-300 md:w-64 -translate-x-full md:translate-x-0 z-[1000]">
             <button id="sidebar-close-btn" class="absolute top-4 right-4 text-white hover:text-gray-300 md:hidden">
                 <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                     <path d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
             @include('admin.sidebar')
         </nav>

         <!-- Main Content -->
         <div class="main-content flex-1 ml-0 md:ml-64 transition-all duration-300">
             <nav class="bg-white dark:bg-slate-800 shadow-md sticky top-0 z-[999]">
                 <div class="container-fluid px-4 py-3">
                     <div class="flex items-center justify-between">
                         <button id="sidebar-toggle"
                             class="navbar-toggler text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 md:hidden focus:outline-none">
                             <svg id="hamburger-icon" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                 <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z" />
                             </svg>
                             <svg id="close-icon" class="w-6 h-6 hidden" fill="currentColor" viewBox="0 0 24 24">
                                 <path d="M6 18L18 6M6 6l12 12" />
                             </svg>
                         </button>
                         <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('all.highlink_isgc') }}</h1>
                         <div class="flex items-center gap-2">
                             <button type="button" id="theme-toggle"
                                 class="inline-flex items-center justify-center h-9 w-9 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-teal-600 dark:text-teal-300 hover:bg-gray-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-teal-500"
                                 aria-label="{{ __('all.toggle_theme') }}">
                                 <svg id="theme-icon-moon" class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                         d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                                 </svg>
                                 <svg id="theme-icon-sun" class="h-5 w-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                         d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364 6.364l-1.414-1.414M7.05 7.05L5.636 5.636m12.728 0L16.95 7.05M7.05 16.95l-1.414 1.414M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                 </svg>
                             </button>
                             <div class="relative">
                                 <select
                                     class="block appearance-none w-full bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 hover:border-gray-500 dark:hover:border-slate-500 px-4 py-2 pr-8 rounded shadow leading-tight text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500"
                                     onchange="window.location.href = '{{ route('set.locale', ['lang' => '']) }}' + this.value">
                                     <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>{{ __('all.english') }}
                                     </option>
                                     <option value="sw" {{ app()->getLocale() == 'sw' ? 'selected' : '' }}>{{ __('all.kiswahili') }}
                                     </option>
                                 </select>
                                 <div
                                     class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                                     <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                         viewBox="0 0 20 20">
                                         <path
                                             d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                     </svg>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </nav>

             <main class="container-fluid py-4">
                 @yield('content')
             </main>

             @include('admin.footer')
         </div>
     </div>

     <!-- jQuery and Toastr for notifications -->
     <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
     @stack('scripts')
     <script>
         toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right" };
         @if (session('success'))
             toastr.success("{{ session('success') }}");
         @endif
         @if (session('error'))
             toastr.error("{{ session('error') }}");
         @endif
         @if ($errors->any())
             @foreach ($errors->all() as $error)
                 toastr.error("{{ $error }}");
             @endforeach
         @endif
     </script>
     <!-- Custom JS for sidebar toggle -->
     <script>
         document.addEventListener('DOMContentLoaded', function() {
             const sidebar = document.getElementById('sidebar');
             const sidebarToggle = document.getElementById('sidebar-toggle');
             const hamburgerIcon = document.getElementById('hamburger-icon');
             const closeIcon = document.getElementById('close-icon');
             const sidebarOverlay = document.getElementById('sidebar-overlay');

             // Toggle sidebar when navbar toggler is clicked
             sidebarToggle.addEventListener('click', function() {
                 sidebar.classList.toggle('-translate-x-full');
                 sidebarOverlay.classList.toggle('hidden');
                 hamburgerIcon.classList.toggle('hidden');
                 closeIcon.classList.toggle('hidden');
             });

             // Close sidebar when close button or overlay is clicked
             const closeSidebar = function() {
                 sidebar.classList.add('-translate-x-full');
                 sidebarOverlay.classList.add('hidden');
                 hamburgerIcon.classList.remove('hidden');
                 closeIcon.classList.add('hidden');
             };

             document.getElementById('sidebar-close-btn').addEventListener('click', closeSidebar);
             sidebarOverlay.addEventListener('click', closeSidebar);

             // Close sidebar when clicking outside on mobile
             document.addEventListener('click', function(event) {
                 const isClickInsideSidebar = sidebar.contains(event.target);
                 const isClickOnToggler = sidebarToggle.contains(event.target);

                 if (!isClickInsideSidebar && !isClickOnToggler && window.innerWidth < 768) {
                     closeSidebar();
                 }
             });

             var themeToggle = document.getElementById('theme-toggle');
             if (themeToggle) {
                 themeToggle.addEventListener('click', function () {
                     var root = document.documentElement;
                     var next = root.classList.contains('dark') ? 'light' : 'dark';
                     root.classList.toggle('dark', next === 'dark');
                     try { localStorage.setItem('hl-theme', next); } catch (e) {}
                 });
             }
         });
     </script>
 </body>

 </html> 
