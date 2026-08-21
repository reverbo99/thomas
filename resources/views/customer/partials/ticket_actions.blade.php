@if (in_array($book->payment_status, ['Paid', 'Refund Rejected']))
    <div class="ticket-actions">
        @if (is_cancel_allowed($book))
            <form action="{{ route('customer.cancel') }}" method="get"
                onsubmit="return confirm(@json(__('all.confirm_cancel_ticket')))">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $book->id }}">
                <button type="submit" class="ticket-action-btn ticket-action-btn--danger" title="{{ __('all.cancel_ticket_title') }}">
                    <i class="fas fa-times"></i>
                </button>
            </form>
        @else
            <span class="ticket-action-btn ticket-action-btn--muted" title="{{ __('all.cancel_not_allowed') }}">
                <i class="fas fa-clock"></i>
            </span>
        @endif

        <form action="{{ route('customer.rebook') }}" method="get"
            onsubmit="return confirm(@json(__('all.confirm_rebook_ticket')))">
            @csrf
            <input type="hidden" name="order_id" value="{{ $book->id }}">
            <button type="submit" class="ticket-action-btn ticket-action-btn--primary" title="{{ __('all.rebook_title') }}">
                <i class="fas fa-rotate-right"></i>
            </button>
        </form>

        <a href="{{ route('customer.booking.transfer.form', ['booking_id' => $book->id]) }}"
            class="ticket-action-btn ticket-action-btn--success"
            title="{{ __('all.transfer_title') }}"
            onclick="return confirm(@json(__('all.confirm_transfer_ticket')))">
            <i class="fas fa-right-left"></i>
        </a>

        <form action="{{ route('customer.edit', ['id' => $book->id]) }}" method="get">
            @csrf
            <input type="hidden" name="booking_id" value="{{ $book->id }}">
            <button type="submit" class="ticket-action-btn ticket-action-btn--success" title="{{ __('all.edit_title') }}">
                <i class="fas fa-pen"></i>
            </button>
        </form>

        <form action="{{ route('ticket.print') }}" method="post">
            @csrf
            <input type="hidden" name="data" value='@json(["id" => $book->id])'>
            <button type="submit" class="ticket-action-btn ticket-action-btn--warning" title="{{ __('all.print_title') }}">
                <i class="fas fa-print"></i>
            </button>
        </form>

        <button type="button" class="ticket-action-btn ticket-action-btn--refund refund-trigger"
            data-refund-modal="refundModal{{ $book->id }}"
            title="{{ __('all.refund_title') }}">
            <i class="fas fa-rotate-left"></i>
        </button>
    </div>
@elseif(in_array($book->payment_status, ['Refund Pending', 'refunded', 'Refund']))
    <span class="ticket-actions__empty">—</span>
@elseif($book->payment_status == 'Unpaid')
    <div class="ticket-actions">
        <span class="ticket-action-btn ticket-action-btn--muted" title="{{ __('all.fail_title') }}">
            <i class="fas fa-circle-info"></i>
        </span>
    </div>
@elseif($book->payment_status == 'resaved')
    <div class="ticket-actions">
        <form action="{{ route('customer.edit', ['id' => $book->id]) }}" method="get">
            @csrf
            <button type="submit" class="ticket-action-btn ticket-action-btn--success" title="{{ __('all.edit_title') }}">
                <i class="fas fa-pen"></i>
            </button>
        </form>

        <form action="{{ route('customer.cancel.resaved', ['id' => $book->id]) }}" method="POST"
            onsubmit="return confirm(@json(__('all.confirm_cancel_resaved_ticket')))">
            @csrf
            <button type="submit" class="ticket-action-btn ticket-action-btn--danger" title="{{ __('all.cancel_title') }}">
                <i class="fas fa-times"></i>
            </button>
        </form>

        <form action="{{ route('customer.pay.resaved', ['id' => $book->id]) }}" method="get">
            @csrf
            <button type="submit" class="ticket-action-btn ticket-action-btn--primary" title="{{ __('all.pay_button') }}">
                <i class="fas fa-credit-card"></i>
            </button>
        </form>
    </div>
@else
    <div class="ticket-actions">
        <span class="ticket-action-btn ticket-action-btn--muted" title="{{ __('all.cancelled') }}">
            <i class="fas fa-ban"></i>
        </span>
    </div>
@endif
