@php
    $highlinkI18n = [
        'accept_terms_required' => __('all.accept_terms_required'),
        'enter_phone_required' => __('all.enter_phone_required'),
        'enter_mobile_contact_details' => __('all.enter_mobile_contact_details'),
        'processing' => __('all.processing'),
        'sending_payment_request' => __('all.sending_payment_request'),
        'prompt_sent_phone' => __('all.prompt_sent_phone'),
        'payment_failed_try_again' => __('all.payment_failed_try_again'),
        'network_error_try_again' => __('all.network_error_try_again'),
        'next' => __('all.next'),
        'select_option_placeholder' => __('all.select_option_placeholder'),
        'calculating_distance' => __('all.calculating_distance'),
        'loading' => __('all.loading'),
        'select_at_least_one_seat' => __('all.select_at_least_one_seat'),
        'select_insurance_type' => __('all.select_insurance_type'),
        'select_insurance_date' => __('all.select_insurance_date'),
        'something_went_wrong' => __('all.error_try_again'),
        'unable_load_booking_form' => __('all.unable_load_booking_form'),
        'amount_label' => __('all.amount_label'),
        'reserve_ticket_button' => __('customer/busroot.resave_ticket_button'),
    ];
@endphp
<script>
window.HighlinkI18n = @json($highlinkI18n);
</script>
