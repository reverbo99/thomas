@php
    $refundRoute = $refundRoute ?? 'vender.refund';
    $refundActor = $refundActor ?? 'vender';
@endphp
<div class="tickets-modal fixed inset-0 z-[100] hidden" id="refundModal{{ $book->id }}"
    aria-labelledby="refundModalLabel{{ $book->id }}" aria-modal="true" role="dialog">
    <div class="tickets-modal__backdrop" data-close-refund-modal="refundModal{{ $book->id }}"></div>
    <div class="tickets-modal__dialog">
        <div class="customer-panel tickets-modal__panel">
            <div class="customer-panel__header flex justify-between items-center gap-3">
                <h5 class="text-base" id="refundModalLabel{{ $book->id }}">{{ __('all.refund_title') }}</h5>
                <button type="button" class="tickets-modal__close"
                    data-close-refund-modal="refundModal{{ $book->id }}" aria-label="{{ __('all.close') }}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="customer-panel__body">
                <form action="{{ route($refundRoute) }}" method="POST"
                    id="refundForm{{ $book->id }}" class="needs-validation refund-form space-y-4" novalidate
                    data-book-id="{{ $book->id }}">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $book->id }}">
                    <input type="hidden" name="actor" value="{{ $refundActor }}">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('all.refund_mobile_or_bank_hint') }}</p>

                    <div>
                        <label for="fullname{{ $book->id }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('all.full_name') }}</label>
                        <input type="text" class="page-input" id="fullname{{ $book->id }}" name="fullname"
                            value="{{ old('booking_id') == $book->id ? old('fullname') : '' }}" required
                            placeholder="{{ __('all.enter_full_name') }}">
                        @error('fullname')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mobile_number{{ $book->id }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('all.mobile_number') }}</label>
                        <input type="tel" class="page-input" id="mobile_number{{ $book->id }}" name="mobile_number"
                            pattern="[0-9]{9,15}" placeholder="{{ $book->customer_phone ?? __('all.enter_mobile_number') }}"
                            value="{{ old('booking_id') == $book->id ? old('mobile_number') : '' }}">
                        <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">{{ __('all.mobile_must_match_booking') ?? 'Must match the phone number used for this booking.' }}</p>
                        @error('mobile_number')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bank_number{{ $book->id }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('all.bank_account_number') }}</label>
                        <input type="text" class="page-input" id="bank_number{{ $book->id }}" name="bank_number"
                            placeholder="{{ __('all.enter_bank_account_number') }}"
                            value="{{ old('booking_id') == $book->id ? old('bank_number') : '' }}">
                        @error('bank_number')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="refundFormError{{ $book->id }}" class="customer-alert customer-alert--error hidden" role="alert"></div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-2">
                        <button type="button" class="page-btn page-btn--outline"
                            data-close-refund-modal="refundModal{{ $book->id }}">{{ __('all.close_button') }}</button>
                        <button type="submit" class="page-btn">{{ __('all.submit_refund_request') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
