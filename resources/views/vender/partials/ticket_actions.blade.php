@if (in_array($book->payment_status, ['Paid', 'Refund Rejected']))
    <div class="ticket-actions">
        @if (is_cancel_allowed($book))
            <form action="{{ route('vender.cancel') }}" method="get"
                onsubmit="return confirm(@json(__('all.confirm_cancel_ticket')))">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $book->id }}">
                <input type="hidden" name="actor" value="vender">
                <button type="submit" class="ticket-action-btn ticket-action-btn--danger" title="{{ __('all.cancel_ticket_title') }}">
                    <i class="fas fa-times"></i>
                </button>
            </form>
        @else
            <span class="ticket-action-btn ticket-action-btn--muted" title="{{ __('all.cancel_not_allowed') }}">
                <i class="fas fa-clock"></i>
            </span>
        @endif

        <form action="{{ route('vender.rebook') }}" method="get"
            onsubmit="return confirm(@json(__('all.confirm_rebook_ticket')))">
            @csrf
            <input type="hidden" name="order_id" value="{{ $book->id }}">
            <input type="hidden" name="actor" value="vender">
            <button type="submit" class="ticket-action-btn ticket-action-btn--primary" title="{{ __('all.rebook_title') }}">
                <i class="fas fa-rotate-right"></i>
            </button>
        </form>

        <a href="{{ route('vender.booking.transfer.form', ['booking_id' => $book->id]) }}"
            class="ticket-action-btn ticket-action-btn--success"
            title="{{ __('all.transfer_title') }}"
            onclick="return confirm(@json(__('all.confirm_transfer_ticket')))">
            <i class="fas fa-right-left"></i>
        </a>

        <button type="button" class="ticket-action-btn ticket-action-btn--refund refund-trigger"
            data-refund-modal="refundModal{{ $book->id }}"
            title="{{ __('all.refund_title') }}">
            <i class="fas fa-rotate-left"></i>
        </button>
    </div>
@endif
