 @extends('admin.app')

 @section('content')
     <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
     <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
         <div class="bg-white shadow-lg rounded-lg overflow-hidden">
             <div class="p-4 sm:p-6 border-b border-gray-200">
                 <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                     <h1 class="text-xl font-bold text-gray-800">{{ __('vender/schedule.bus_schedules') }}</h1>
                     <a href="{{ route('add_schedule') }}"
                         class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors"
                         aria-label="{{ __('vender/schedule.add_new_schedule_aria') }}">
                         <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             xmlns="http://www.w3.org/2000/svg">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                             </path>
                         </svg>
                         {{ __('vender/schedule.add_new_schedule') }}
                     </a>
                 </div>
             </div>
             <div class="p-4 sm:p-6">
                 @if (session('success'))
                     <div class="mb-4 p-3 bg-green-100 text-green-700 text-sm rounded-md" role="alert">
                         {{ session('success') }}
                     </div>
                 @endif
                 @if (session('error'))
                     <div class="mb-4 p-3 bg-red-100 text-red-700 text-sm rounded-md" role="alert">
                         {{ session('error') }}
                     </div>
                 @endif
                 <div class="overflow-x-auto">
                     <table id="busTable" class="w-full table-auto text-sm text-gray-700 display" cellspacing="0" width="100%">
                         <thead class="bg-gray-100 text-xs uppercase text-gray-500 font-semibold">
                             <tr>
                                 <th class="px-4 py-3 text-left">{{ __('vender/schedule.bus') }}</th>
                                 <th class="px-4 py-3 text-left">{{ __('vender/schedule.from') }}</th>
                                 <th class="px-4 py-3 text-left">{{ __('vender/schedule.to') }}</th>
                                 <th class="px-4 py-3 text-left">{{ __('vender/schedule.time_24hrs') }}</th>
                                 <th class="px-4 py-3 text-left">{{ __('vender/schedule.schedule_date') }}</th>
                                 <th class="px-4 py-3 text-left">{{ __('vender/schedule.action') }}</th>
                             </tr>
                         </thead>
                         <tbody>
                             @forelse ($schedules as $schedule)
                                 <tr class="border-b border-gray-200 hover:bg-gray-50">
                                     <td class="px-4 py-3">{{ $schedule->bus->busname->name ?? __('vender/schedule.na') }}
                                         ({{ $schedule->bus->bus_number ?? __('vender/schedule.na') }})</td>
                                     <td class="px-4 py-3">{{ $schedule->from }}</td>
                                     <td class="px-4 py-3">{{ $schedule->to }}</td>
                                     <td class="px-4 py-3">{{ $schedule->start }} -> {{ $schedule->end }}</td>
                                     <td class="px-4 py-3">{{ $schedule->schedule_date }}</td>
                                     <td class="px-4 py-3">
                                         <div class="flex gap-2">
                                             @php
                                                 $seatMap = $schedule->booked_seat_map ?? [];
                                                 $bookedCount = count($seatMap);
                                                 $totalSeats = (int) ($schedule->bus->total_seats ?? 0);
                                                 $availableCount = max(0, $totalSeats - $bookedCount);
                                                 $bus = $schedule->bus;
                                                 $layoutData = is_string($bus?->seate_json) ? $bus->seate_json : json_encode($bus?->seate_json);
                                             @endphp
                                             <button type="button"
                                                 class="view-seats-btn inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-600 rounded-md hover:bg-indigo-200 transition-colors"
                                                 aria-label="{{ __('system.pages.view_seats') }}"
                                                 data-bus-number="{{ $bus?->bus_number ?? '' }}"
                                                 data-company="{{ $bus?->busname->name ?? '' }}"
                                                 data-route="{{ $schedule->from }} → {{ $schedule->to }}"
                                                 data-date="{{ $schedule->schedule_date }}"
                                                 data-total-seats="{{ $totalSeats }}"
                                                 data-booked-count="{{ $bookedCount }}"
                                                 data-available-count="{{ $availableCount }}"
                                                 data-layout="{{ $layoutData ?? '' }}"
                                                 data-booked-seats="{{ json_encode($seatMap) }}">
                                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 7a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2M4 7V5a2 2 0 012-2h12a2 2 0 012 2v2" />
                                                 </svg>
                                             </button>
                                             <a href="{{ route('edit.schedule', $schedule->id) }}"
                                                 class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-600 rounded-md hover:bg-yellow-200 transition-colors"
                                                 aria-label="{{ __('vender/schedule.edit_schedule') }}">
                                                 <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                         d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                     </path>
                                                 </svg>
                                             </a>
                                             <form action="{{ route('delete.schedule') }}" method="POST"
                                                 onsubmit="return confirm('{{ __('vender/schedule.confirm_delete_schedule') }}');">
                                                 @csrf
                                                 <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
                                                 <button
                                                     class="inline-flex items-center px-3 py-1 bg-red-100 text-red-600 rounded-md hover:bg-red-200 transition-colors"
                                                     aria-label="{{ __('vender/schedule.delete_schedule') }}">
                                                     <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                         viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                         <path stroke-linecap="round" stroke-linejoin="round"
                                                             stroke-width="2"
                                                             d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                         </path>
                                                     </svg>
                                                 </button>
                                             </form>
                                         </div>
                                     </td>
                                 </tr>
                             @empty
                                 <tr>
                                     <td class="px-4 py-3 text-center text-gray-500">{{ __('vender/schedule.no_buses_found') }}</td>
                                     <td></td><td></td><td></td><td></td><td></td>
                                 </tr>
                             @endforelse
                         </tbody>
                     </table>
                 </div>
             </div>
         </div>
         <dialog id="scheduleModal" class="rounded-lg shadow-xl p-0 max-w-lg w-full bg-white">
             <div class="p-4 sm:p-6 border-b border-gray-200">
                 <div class="flex justify-between items-center">
                     <h5 class="text-lg font-semibold text-gray-800">{{ __('vender/schedule.schedule_details') }}</h5>
                     <button class="text-gray-600 hover:text-gray-800" id="closeModal"
                         aria-label="{{ __('vender/schedule.close_modal') }}">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             xmlns="http://www.w3.org/2000/svg">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                 d="M6 18L18 6M6 6l12 12"></path>
                         </svg>
                     </button>
                 </div>
             </div>
             <div class="p-4 sm:p-6">
                 <p class="mb-2"><strong class="text-gray-700">{{ __('vender/schedule.bus') }}:</strong> <span
                         id="modal-bus" class="text-gray-600"></span></p>
                 <p class="mb-2"><strong class="text-gray-700">{{ __('vender/schedule.from') }}:</strong> <span
                         id="modal-from" class="text-gray-600"></span></p>
                 <p class="mb-2"><strong class="text-gray-700">{{ __('vender/schedule.to') }}:</strong> <span
                         id="modal-to" class="text-gray-600"></span></p>
                 <p class="mb-2"><strong class="text-gray-700">{{ __('vender/schedule.via') }}:</strong> <span
                         id="modal-via" class="text-gray-600"></span></p>
                 <p class="mb-2"><strong class="text-gray-700">{{ __('vender/schedule.time_24hrs') }}:</strong> <span
                         id="modal-time" class="text-gray-600"></span></p>
                 <p class="mb-2"><strong class="text-gray-700">{{ __('vender/schedule.schedule_date') }}:</strong>
                     <span id="modal-date" class="text-gray-600"></span></p>
             </div>
             <div class="p-4 sm:p-6 border-t border-gray-200">
                 <button
                     class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors"
                     id="closeModalBtn"
                     aria-label="{{ __('vender/schedule.close_modal') }}">{{ __('vender/schedule.close') }}</button>
             </div>
         </dialog>
     </div>
     <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
     <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
     <script>
         $(document).ready(function() {
             const translations = {
                 empty_table: "{{ __('vender/schedule.no_buses_found') }}",
                 confirm_delete_schedule: "{{ __('vender/schedule.confirm_delete_schedule') }}",
                 na: "{{ __('vender/schedule.na') }}"
             };

             $('#busTable').DataTable({
                 responsive: true,
                 paging: true,
                 pageLength: 10,
                 searching: true,
                 ordering: true,
                 order: [[4, 'asc']],
                 columnDefs: [
                     { orderable: false, targets: -1 }
                 ],
                 language: {
                     emptyTable: translations.empty_table,
                     search: "{{ __('vender/schedule.search') }}:",
                     lengthMenu: "{{ __('vender/schedule.show') }} _MENU_ {{ __('vender/schedule.entries') }}",
                     info: "{{ __('vender/schedule.info') }}",
                     infoEmpty: "{{ __('vender/schedule.info_empty') }}",
                     infoFiltered: "{{ __('vender/schedule.info_filtered') }}",
                     paginate: {
                         first: "{{ __('vender/schedule.first') }}",
                         last: "{{ __('vender/schedule.last') }}",
                         next: "{{ __('vender/schedule.next') }}",
                         previous: "{{ __('vender/schedule.previous') }}"
                     }
                 }
             });

             const modal = document.getElementById('scheduleModal');
             const closeModal = document.getElementById('closeModal');
             const closeModalBtn = document.getElementById('closeModalBtn');

             $('.view-schedule').on('click', function() {
                 const schedule = $(this).data('schedule');
                 $('#modal-bus').text(
                     `${schedule.bus.busname?.name ?? translations.na} (${schedule.bus.bus_number ?? translations.na})`
                     );
                 $('#modal-from').text(schedule.from ?? translations.na);
                 $('#modal-to').text(schedule.to ?? translations.na);
                 $('#modal-time').text(`${schedule.route.route_start} -> ${schedule.route.route_end}`);
                 $('#modal-date').text(schedule.schedule_date ?? translations.na);
                 $('#modal-via').text(schedule.route.via?.name ?? translations.na);
                 modal.showModal();
             });

             closeModal.addEventListener('click', () => modal.close());
             closeModalBtn.addEventListener('click', () => modal.close());
         });
     </script>

     @include('partials.seat_arrangement_modal')
 @endsection
