document.addEventListener('DOMContentLoaded', function () {
    const i18n = window.HighlinkI18n || {};
    function t(key, fallback) {
        return i18n[key] || fallback;
    }
    function amountLabel(amount, currency) {
        const template = t('amount_label', 'Amount: :amount :currency');
        return template.replace(':amount', amount).replace(':currency', currency);
    }

    const list = document.querySelector('.home-search-results__list');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function normalizePhoneTo255(str) {
        if (!str) return '';
        var trimmed = String(str).trim();
        if (!trimmed) return '';
        var digits = trimmed.replace(/\D/g, '');
        while (digits.indexOf('00') === 0 && digits.length > 2) {
            digits = digits.substring(2);
        }
        if (digits.length === 12 && digits.substring(0, 3) === '225') {
            var r = digits.substring(3);
            if (r.charAt(0) === '6' || r.charAt(0) === '7') {
                digits = '255' + r;
            }
        }
        if (digits.length === 12 && digits.substring(0, 3) === '255'
            && (digits.charAt(3) === '6' || digits.charAt(3) === '7')) {
            return digits;
        }
        if (digits.length === 10 && digits.charAt(0) === '0') {
            digits = '255' + digits.substring(1);
            if (digits.length === 12 && (digits.charAt(3) === '6' || digits.charAt(3) === '7')) return digits;
        }
        if (digits.length === 9 && (digits.charAt(0) === '6' || digits.charAt(0) === '7')) {
            digits = '255' + digits;
            if (digits.length === 12) return digits;
        }
        if (digits.charAt(0) === '0') {
            digits = '255' + digits.substring(1);
            if (digits.length === 12 && (digits.charAt(3) === '6' || digits.charAt(3) === '7')) return digits;
        }
        if (digits.substring(0, 3) !== '255') {
            digits = '255' + digits;
            if (digits.length === 12 && (digits.charAt(3) === '6' || digits.charAt(3) === '7')) return digits;
        }
        return '';
    }

    function getInlineContactFields(root) {
        const scope = root.closest('.inline-payment') || root;
        return {
            countrycode: scope.querySelector('#countrycode')?.value || '+255',
            contactNumber: normalizePhoneTo255(scope.querySelector('#contactNumber')?.value || ''),
            contactEmail: scope.querySelector('#contactEmail')?.value.trim() || '',
        };
    }

    function appendContactFieldsToForm(form, contact) {
        ['countrycode', 'contactNumber', 'contactEmail'].forEach(function (name) {
            form.querySelectorAll('input[name="' + name + '"]').forEach(function (el) { el.remove(); });
        });
        Object.keys(contact).forEach(function (name) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = contact[name];
            form.appendChild(input);
        });
    }

    function syncInlinePaymentTabs(paymentRoot, activeKey) {
        if (!paymentRoot) return;
        paymentRoot.querySelectorAll('[data-inline-pay-tab]').forEach(function (btn) {
            const active = btn.dataset.inlinePayTab === activeKey;
            btn.classList.toggle('inline-payment__tab--active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        paymentRoot.querySelectorAll('[data-inline-pay-pane]').forEach(function (pane) {
            const isActive = pane.dataset.inlinePayPane === activeKey;
            pane.classList.toggle('inline-payment__pane--active', isActive);
            if (isActive) {
                pane.removeAttribute('hidden');
            } else {
                pane.setAttribute('hidden', '');
            }
        });
        const nextBtn = paymentRoot.querySelector('[data-inline-pay-next]');
        if (nextBtn) {
            if (!nextBtn.dataset.defaultLabel) {
                nextBtn.dataset.defaultLabel = nextBtn.textContent.trim();
            }
            if (activeKey === 'reserve') {
                nextBtn.textContent = t('reserve_ticket_button', 'Reserve Ticket');
            } else {
                nextBtn.textContent = nextBtn.dataset.defaultLabel;
            }
        }
    }

    function updateInlinePayPhoneDisplay(paymentRoot) {
        if (!paymentRoot) return;
        const contact = getInlineContactFields(paymentRoot);
        const display = contact.contactNumber || paymentRoot.querySelector('#contactNumber')?.value.trim() || '—';
        paymentRoot.querySelectorAll('[data-inline-pay-phone-display]').forEach(function (el) {
            el.textContent = display;
        });
    }

    function appendPaymentContactToForm(form, contact) {
        appendContactFieldsToForm(form, contact);
        form.querySelectorAll('input[name="payment_contact"]').forEach(function (el) { el.remove(); });
        const paymentContact = document.createElement('input');
        paymentContact.type = 'hidden';
        paymentContact.name = 'payment_contact';
        paymentContact.value = contact.contactNumber;
        form.appendChild(paymentContact);
    }

    function initInlinePayment(root) {
        const paymentRoot = root?.querySelector?.('[data-inline-payment]') || root;
        if (!paymentRoot || !paymentRoot.matches?.('[data-inline-payment]')) return;

        syncInlinePaymentTabs(paymentRoot, 'mixx');
        updateInlinePayPhoneDisplay(paymentRoot);

        const contactInput = paymentRoot.querySelector('#contactNumber');
        if (contactInput && !contactInput.dataset.inlinePayPhoneBound) {
            contactInput.dataset.inlinePayPhoneBound = '1';
            contactInput.addEventListener('input', function () {
                updateInlinePayPhoneDisplay(paymentRoot);
            });
        }

        if (paymentRoot.dataset.inlinePaymentBound === '1') return;
        paymentRoot.dataset.inlinePaymentBound = '1';

        const baseTotal = parseFloat(paymentRoot.dataset.inlinePaymentTotal || '0');

        function termsAccepted() {
            const terms = paymentRoot.querySelector('#inlinePaymentTerms');
            return !terms || terms.checked;
        }

        function bindVerifyForm(formId) {
            const form = paymentRoot.querySelector('#' + formId);
            if (!form || form.dataset.bound === '1') return;
            form.dataset.bound = '1';
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!termsAccepted()) {
                    alert(t('accept_terms_required', 'Please accept the terms and conditions.'));
                    return;
                }
                const contact = getInlineContactFields(paymentRoot);
                if (!contact.contactNumber) {
                    alert(t('enter_phone_required', 'Please enter phone number'));
                    return;
                }
                appendPaymentContactToForm(form, contact);
                const termsInput = document.createElement('input');
                termsInput.type = 'hidden';
                termsInput.name = 'payment_term_0';
                termsInput.value = '1';
                form.appendChild(termsInput);
                form.submit();
            });
        }

        bindVerifyForm('inlineMixxForm');
        bindVerifyForm('inlineDpoForm');
        bindVerifyForm('inlineClickpesaForm');
        bindVerifyForm('inlineWalletForm');
        bindVerifyForm('inlineCashForm');
        bindVerifyForm('inlineResaveForm');

        const testForm = paymentRoot.querySelector('[id^="test-mode-checkout-form"]');
        if (testForm && testForm.dataset.testModeBound !== '1') {
            testForm.dataset.testModeBound = '1';
            testForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const contact = getInlineContactFields(paymentRoot);
                if (!contact.contactNumber) {
                    alert(t('enter_phone_required', 'Please enter phone number'));
                    return;
                }
                appendContactFieldsToForm(testForm, contact);
                testForm.submit();
            });
        }

        const nextBtn = paymentRoot.querySelector('[data-inline-pay-next]');
        if (nextBtn && !nextBtn.dataset.bound) {
            nextBtn.dataset.bound = '1';
            nextBtn.addEventListener('click', function () {
                if (!termsAccepted()) {
                    alert(t('accept_terms_required', 'Please accept the terms and conditions.'));
                    return;
                }

                const activePane = paymentRoot.querySelector('[data-inline-pay-pane].inline-payment__pane--active');

                if (!activePane) {
                    const testForm = paymentRoot.querySelector('[id^="test-mode-checkout-form"]');
                    if (testForm) {
                        testForm.requestSubmit();
                    }
                    return;
                }

                const paneKey = activePane.dataset.inlinePayPane;
                if (paneKey === 'airtel') {
                    triggerInlineAirtelPay(paymentRoot, baseTotal);
                    return;
                }

                const form = activePane.querySelector('form');
                if (form) form.requestSubmit();
            });
        }

        function triggerInlineAirtelPay(scope, total) {
            const statusEl = scope.querySelector('[data-inline-airtel-status]');
            const nextButton = scope.querySelector('[data-inline-pay-next]');
            const contact = getInlineContactFields(scope);
            const phone = contact.contactNumber;

            if (!termsAccepted()) {
                alert(t('accept_terms_required', 'Please accept the terms and conditions.'));
                return;
            }

            if (!phone) {
                alert(t('enter_mobile_contact_details', 'Please enter your mobile number in Contact Details.'));
                return;
            }

            if (nextButton) {
                nextButton.disabled = true;
                nextButton.textContent = t('processing', 'Processing…');
            }
            if (statusEl) {
                statusEl.classList.remove('hidden');
                statusEl.textContent = t('sending_payment_request', 'Sending payment request…');
                statusEl.className = 'inline-payment__airtel-status inline-payment__airtel-status--info';
            }

            fetch(scope.dataset.airtelUrl || '/airtel/booking-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    amount: Math.round(total),
                    phone_number: normalizePhoneTo255(phone),
                    contact_number: contact.contactNumber || normalizePhoneTo255(phone),
                    contact_email: contact.contactEmail,
                    booking_code: '',
                }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status === 'success') {
                        if (statusEl) {
                            statusEl.textContent = data.message || t('prompt_sent_phone', 'Prompt sent! Approve on your phone.');
                            statusEl.className = 'inline-payment__airtel-status inline-payment__airtel-status--success';
                        }
                    } else {
                        if (statusEl) {
                            statusEl.textContent = data.message || t('payment_failed_try_again', 'Payment failed. Please try again.');
                            statusEl.className = 'inline-payment__airtel-status inline-payment__airtel-status--error';
                        }
                        if (nextButton) {
                            nextButton.disabled = false;
                            nextButton.textContent = t('next', 'Next');
                        }
                    }
                })
                .catch(function () {
                    if (statusEl) {
                        statusEl.textContent = t('network_error_try_again', 'Network error. Please try again.');
                        statusEl.className = 'inline-payment__airtel-status inline-payment__airtel-status--error';
                    }
                    if (nextButton) {
                        nextButton.disabled = false;
                        nextButton.textContent = 'Next';
                    }
                });
        }
    }

    function showPanelError(panel, message) {
        const el = panel?.querySelector('.inline-booking-panel__error')
            || panel?.querySelector('.inline-wizard')?.parentElement?.querySelector('.inline-booking-panel__error');
        if (!el) return;
        el.textContent = message;
        el.classList.remove('hidden');
    }

    function clearPanelError(panel) {
        const el = panel?.querySelector('.inline-booking-panel__error')
            || panel?.closest('.home-bus-row__expand-inner')?.querySelector('.inline-booking-panel__error');
        if (el) el.classList.add('hidden');
    }

    function collapseInlinePanel(wrap) {
        if (!wrap) return;
        const expand = wrap.querySelector('.home-bus-row__expand');
        const btn = wrap.querySelector('[data-inline-book]');
        const article = wrap.querySelector('.home-bus-row');
        if (expand) expand.hidden = true;
        if (btn) btn.setAttribute('aria-expanded', 'false');
        if (article) article.classList.remove('home-bus-row--expanded');
    }

    function collapseAllExcept(wrap) {
        document.querySelectorAll('.home-bus-row-wrap').forEach(function (row) {
            if (row === wrap) return;
            collapseInlinePanel(row);
        });
    }

    function readPickupFormState(inner) {
        const form = inner.querySelector('[data-inline-form="pickup"]');
        if (!form) return null;

        const uid = form.dataset.inlineUid;
        if (!uid) return null;

        return {
            uid: uid,
            pickup: form.querySelector('#pickupPoint_' + uid)?.value || '',
            dropoff: form.querySelector('#dropoffPoint_' + uid)?.value || '',
            routeDistance: form.querySelector('#routeDistance_' + uid)?.value || '',
            droppingPointAmount: form.querySelector('#droppingPointAmount_' + uid)?.value || '',
        };
    }

    function applyPickupFormState(inner, state) {
        if (!state?.uid) return;

        const form = inner.querySelector('[data-inline-form="pickup"]');
        if (!form) return;

        const uid = state.uid;
        const pickup = form.querySelector('#pickupPoint_' + uid);
        const drop = form.querySelector('#dropoffPoint_' + uid);
        const distanceField = form.querySelector('#routeDistance_' + uid);
        const amountField = form.querySelector('#droppingPointAmount_' + uid);
        const hint = inner.querySelector('#routeDistanceHint_' + uid);

        if (pickup && state.pickup) {
            pickup.value = state.pickup;
            Array.from(pickup.options).forEach(function (option) {
                option.selected = option.value === state.pickup;
            });
        }

        if (drop && state.dropoff) {
            drop.value = state.dropoff;
            Array.from(drop.options).forEach(function (option) {
                option.selected = option.value === state.dropoff;
            });
        }

        if (distanceField && state.routeDistance) {
            distanceField.value = state.routeDistance;
        }

        if (amountField && state.droppingPointAmount) {
            amountField.value = state.droppingPointAmount;
        }

        if (hint && state.routeDistance && parseFloat(state.routeDistance) >= 1) {
            hint.hidden = false;
            hint.innerHTML = '<i class="fas fa-road" aria-hidden="true"></i> '
                + parseFloat(state.routeDistance).toFixed(1) + ' km total distance';
        }
    }

    function syncPickupSelectValuesToDom(inner) {
        inner.querySelectorAll('[data-inline-form="pickup"]').forEach(function (form) {
            const uid = form.dataset.inlineUid;
            if (!uid) return;

            ['pickupPoint_', 'dropoffPoint_'].forEach(function (prefix) {
                const select = form.querySelector('#' + prefix + uid);
                if (!select) return;
                const current = select.value;
                Array.from(select.options).forEach(function (option) {
                    option.selected = option.value === current;
                });
            });
        });
    }

    function refreshInlineSelect2Values(panel, state) {
        if (!state?.uid || typeof window.jQuery === 'undefined') return;

        const $pickup = window.jQuery(panel).find('#pickupPoint_' + state.uid);
        const $drop = window.jQuery(panel).find('#dropoffPoint_' + state.uid);

        if (state.pickup && $pickup.length) {
            $pickup.val(state.pickup).trigger('change');
        }
        if (state.dropoff && $drop.length) {
            $drop.val(state.dropoff).trigger('change');
        }
    }

    function restoreInlinePickupPanel(inner) {
        if (!inner._pickupHtml) return;

        inner.innerHTML = inner._pickupHtml;
        resetInlinePickupPanelState(inner);
        applyPickupFormState(inner, inner._pickupState);
        bindInlineForms(inner);

        inner.querySelectorAll('.inline-booking-panel').forEach(function (panel) {
            if (panel.querySelector('[data-inline-form="pickup"]')) {
                refreshInlineSelect2Values(panel, inner._pickupState);
            }
        });

        clearPanelError(inner);
        inner.closest('.home-bus-row-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function cleanupInlineSelect2(container) {
        if (typeof window.jQuery === 'undefined') return;
        window.jQuery(container).find('.inline-select2').each(function () {
            const $el = window.jQuery(this);
            if ($el.data('select2')) {
                try { $el.select2('destroy'); } catch (e) { /* already torn down */ }
            }
            $el.next('.select2-container').remove();
            $el.removeClass('select2-hidden-accessible')
                .removeAttr('data-select2-id')
                .removeAttr('aria-hidden')
                .removeAttr('tabindex')
                .css('width', '');
        });
    }

    function resetInlinePickupPanelState(inner) {
        inner.querySelectorAll('.inline-booking-panel').forEach(function (panel) {
            if (!panel.querySelector('[data-inline-form="pickup"]')) return;
            cleanupInlineSelect2(panel);
            delete panel.dataset.inlineDistanceBound;
            delete panel._inlineDistanceCalc;
            delete panel._inlineDistancePromise;
        });
        inner.querySelectorAll('[data-inline-form="pickup"]').forEach(function (form) {
            delete form.dataset.bound;
        });
    }

    function capturePickupHtml(inner) {
        inner._pickupState = readPickupFormState(inner);
        syncPickupSelectValuesToDom(inner);
        resetInlinePickupPanelState(inner);
        return inner.innerHTML;
    }

    function initInlineSelect2(container) {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.select2) return;

        delete container.dataset.inlineDistanceBound;
        delete container._inlineDistanceCalc;
        delete container._inlineDistancePromise;

        cleanupInlineSelect2(container);

        window.jQuery(container).find('.inline-select2').each(function () {
            window.jQuery(this).select2({
                placeholder: t('select_option_placeholder', 'Select an option'),
                allowClear: true,
                width: '100%',
                dropdownParent: window.jQuery(container).closest('.home-bus-row__expand'),
            });
        });

        initInlineRouteDistance(container);

        if (typeof container._inlineDistanceCalc === 'function') {
            container._inlineDistanceCalc();
        }
    }

    function haversineKm(lat1, lon1, lat2, lon2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
            * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function geocodeInlinePlace(place, retryCount) {
        retryCount = retryCount || 0;
        var query = String(place || '').trim();
        if (!query) return Promise.resolve(null);

        if (/^-?\d+\.\d+\s*,\s*-?\d+\.\d+$/.test(query)) {
            var parts = query.split(',').map(function (c) { return parseFloat(c.trim()); });
            return Promise.resolve({ lat: parts[0], lon: parts[1] });
        }

        var searchQuery = /tanzania/i.test(query) ? query : query + ', Tanzania';

        return fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(searchQuery) + '&limit=1', {
            headers: { 'Accept': 'application/json', 'User-Agent': 'HighlinkBooking/1.0' },
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data && data.length > 0) {
                    return { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon) };
                }
                return null;
            })
            .catch(function () {
                if (retryCount < 1) {
                    return new Promise(function (resolve) { setTimeout(resolve, 1100); })
                        .then(function () { return geocodeInlinePlace(place, 1); });
                }
                return null;
            });
    }

    function routeDistanceInlineKm(fromCoords, toCoords) {
        var url = 'https://router.project-osrm.org/route/v1/driving/'
            + fromCoords.lon + ',' + fromCoords.lat + ';'
            + toCoords.lon + ',' + toCoords.lat + '?overview=false';

        return fetch(url)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data && data.code === 'Ok' && data.routes && data.routes[0] && data.routes[0].distance) {
                    return parseFloat((data.routes[0].distance / 1000).toFixed(2));
                }
                return parseFloat(haversineKm(fromCoords.lat, fromCoords.lon, toCoords.lat, toCoords.lon).toFixed(2));
            })
            .catch(function () {
                return parseFloat(haversineKm(fromCoords.lat, fromCoords.lon, toCoords.lat, toCoords.lon).toFixed(2));
            });
    }

    function initInlineRouteDistance(container) {
        var uid = container.querySelector('[data-inline-uid]')?.dataset.inlineUid
            || container.querySelector('[data-inline-form="pickup"]')?.dataset.inlineUid;
        if (!uid || container.dataset.inlineDistanceBound === '1') return;
        container.dataset.inlineDistanceBound = '1';

        var pickup = container.querySelector('#pickupPoint_' + uid);
        var drop = container.querySelector('#dropoffPoint_' + uid);
        var distanceField = container.querySelector('#routeDistance_' + uid);
        var hint = container.querySelector('[data-inline-distance-hint]');
        var amountField = container.querySelector('#droppingPointAmount_' + uid);
        var calcToken = 0;

        function setHint(text, visible) {
            if (!hint) return;
            if (!visible || !text) {
                hint.hidden = true;
                hint.textContent = '';
                return;
            }
            hint.hidden = false;
            hint.innerHTML = '<i class="fas fa-road" aria-hidden="true"></i> ' + text;
        }

        async function updateDistance() {
            var from = pickup?.value;
            var to = drop?.value;
            if (!from || !to || !distanceField) {
                distanceField.value = '';
                setHint('', false);
                container._inlineDistancePromise = Promise.resolve(null);
                return null;
            }

            var token = ++calcToken;
            setHint(t('calculating_distance', 'Calculating distance…'), true);

            container._inlineDistancePromise = (async function () {
                var fromCoords = await geocodeInlinePlace(from);
                await new Promise(function (resolve) { setTimeout(resolve, 1100); });
                var toCoords = await geocodeInlinePlace(to);

                if (token !== calcToken) return null;
                if (!fromCoords || !toCoords) {
                    setHint('', false);
                    return null;
                }

                var km = await routeDistanceInlineKm(fromCoords, toCoords);
                if (token !== calcToken) return null;

                if (km >= 1) {
                    distanceField.value = km.toFixed(2);
                    setHint(Number(km).toFixed(1) + ' km total distance', true);
                } else {
                    distanceField.value = '';
                    setHint('', false);
                }

                return km;
            })();

            return container._inlineDistancePromise;
        }

        if (drop && amountField) {
            drop.addEventListener('change', function () {
                var opt = this.options[this.selectedIndex];
                amountField.value = opt?.getAttribute('data-amount') || amountField.value || '0';
            });
        }

        pickup?.addEventListener('change', updateDistance);
        drop?.addEventListener('change', updateDistance);

        container._inlineDistanceCalc = updateDistance;
    }

    function updateTimeline(timeline, activeKey) {
        if (!timeline) return;
        const order = ['pickup', 'seats', 'extras', 'payment'];
        const icons = {
            pickup: 'fa-map-marker-alt',
            seats: 'fa-chair',
            extras: 'fa-user',
            payment: 'fa-credit-card',
        };
        const activeIdx = order.indexOf(activeKey);
        if (activeIdx < 0) return;

        timeline.querySelectorAll('[data-booking-step]').forEach(function (btn) {
            const key = btn.dataset.bookingStep;
            const idx = order.indexOf(key);
            const isDone = idx >= 0 && idx < activeIdx;
            const isActive = idx === activeIdx;

            btn.disabled = !isDone;
            btn.classList.toggle('booking-steps__item--active', isActive);
            btn.classList.toggle('booking-steps__item--done', isDone);
            btn.setAttribute('aria-current', isActive ? 'step' : 'false');

            const icon = btn.querySelector('.booking-steps__dot i');
            if (icon) {
                icon.className = isDone ? 'fas fa-check' : 'fas ' + (icons[key] || 'fa-circle');
            }
        });
    }

    function setWizardStep(wizard, stepName) {
        wizard.querySelectorAll('[data-wizard-pane]').forEach(function (pane) {
            pane.classList.toggle('hidden', pane.dataset.wizardPane !== stepName);
        });
        updateTimeline(wizard.querySelector('[data-inline-timeline]'), stepName);
        clearPanelError(wizard);
        wizard.closest('.home-bus-row-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function navigateInlineStep(inner, stepKey) {
        const wizard = inner.querySelector('[data-inline-wizard]');
        const wrap = inner.closest('.home-bus-row-wrap');

        if (stepKey === 'pickup') {
            if (inner._pickupHtml) {
                restoreInlinePickupPanel(inner);
                return;
            }

            const url = inner._inlineFormUrl || wrap?.querySelector('[data-inline-book]')?.dataset.inlineUrl;
            if (!url) return;

            inner.innerHTML = '<div class="inline-booking-loading"><i class="fas fa-spinner fa-spin"></i> ' + t('loading', 'Loading…') + '</div>';
            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                });
                if (!res.ok) return;
                inner.innerHTML = await res.text();
                inner._pickupHtml = capturePickupHtml(inner);
                inner._inlineFormUrl = url;
                bindInlineForms(inner);
            } catch (err) {
                showPanelError(inner, t('network_error_try_again', 'Network error. Please try again.'));
            }
            return;
        }

        if (!wizard) return;

        if (stepKey === 'seats' || stepKey === 'extras') {
            setWizardStep(wizard, stepKey);
            return;
        }

        if (stepKey === 'payment') {
            const pane = wizard.querySelector('[data-wizard-pane="payment"]');
            if (pane && pane.innerHTML.trim()) {
                initInlinePayment(pane);
                setWizardStep(wizard, 'payment');
            }
        }
    }

    function initInlineWizard(wizard) {
        const configEl = wizard.querySelector('.inline-seat-config');
        if (!configEl) return;

        let config;
        try {
            config = JSON.parse(configEl.textContent);
        } catch (e) {
            return;
        }

        const uid = config.inlineUid;

        if (!wizard.__inlineSeatState) {
            wizard.__inlineSeatState = { selectedSeats: [] };
        }
        const selectedSeats = wizard.__inlineSeatState.selectedSeats;

        const grid = document.getElementById('seatMapGrid_' + uid);
        const fallback = document.getElementById('seatMapFallback_' + uid);
        const passengersWrap = document.getElementById('passengersWrap_' + uid);
        const passengersList = document.getElementById('passengersList_' + uid);
        const nextExtrasBtn = document.getElementById('wizardNextExtras_' + uid);
        const fareDisplay = document.getElementById('fareDisplay_' + uid);
        const extrasForm = wizard.querySelector('[data-inline-extras-form]');
        const hiddenSeats = document.getElementById('hiddenSelectedSeats_' + uid);
        const hiddenTotal = document.getElementById('hiddenTotalAmount_' + uid);
        const hiddenPassengers = document.getElementById('hiddenPassengers_' + uid);
        const walletKey = document.getElementById('walletKey_' + uid);
        const walletAmount = document.getElementById('walletAmount_' + uid);
        const walletAmountHidden = document.getElementById('walletAmountHidden_' + uid);
        const insuranceToggle = document.getElementById('insurance_' + uid);
        const insuranceFieldsWrap = extrasForm?.querySelector('[data-inline-insurance-fields]');
        const insuranceType = document.getElementById('insuranceType_' + uid);
        const insuranceDate = document.getElementById('insuranceDate_' + uid);

        let layout = null;
        try {
            layout = typeof config.layoutRaw === 'string' ? JSON.parse(config.layoutRaw) : config.layoutRaw;
        } catch (e) {
            layout = null;
        }

        function formatMoney(total) {
            return config.useUsd ? (total / config.usdToTzs).toFixed(2) : total;
        }

        function collectPassengersFromDom() {
            return selectedSeats.map(function (seat) {
                const nameEl = passengersList?.querySelector('[data-passenger-name="' + seat + '"]');
                const phoneEl = passengersList?.querySelector('[data-passenger-phone="' + seat + '"]');
                const ageEl = passengersList?.querySelector('[data-passenger-age-group="' + seat + '"]');
                return {
                    seat: seat,
                    name: nameEl?.value.trim() || '',
                    phone: phoneEl?.value.trim() || '',
                    age_group: ageEl?.value || 'Adult',
                };
            });
        }

        function syncWizardState() {
            const total = selectedSeats.length * config.seatPrice;
            const passengers = collectPassengersFromDom();

            if (hiddenSeats) hiddenSeats.value = selectedSeats.join(',');
            if (hiddenTotal) hiddenTotal.value = String(total);
            if (hiddenPassengers) hiddenPassengers.value = JSON.stringify(passengers);
        }

        function updateTotals() {
            const count = selectedSeats.length;
            const total = count * config.seatPrice;
            const listEl = document.getElementById('selectedSeatsList_' + uid);
            const totalEl = document.getElementById('totalAmount_' + uid);

            if (listEl) listEl.textContent = count > 0 ? selectedSeats.join(', ') : config.noneLabel;
            if (totalEl) totalEl.textContent = formatMoney(total);
            if (fareDisplay) fareDisplay.value = formatMoney(total);
            syncWizardState();
        }

        function toggleInlineInsuranceFields() {
            if (!insuranceToggle || !insuranceFieldsWrap) return;
            const enabled = !!insuranceToggle.checked;
            insuranceFieldsWrap.classList.toggle('hidden', !enabled);
            if (insuranceType) insuranceType.disabled = !enabled;
            if (insuranceDate) insuranceDate.disabled = !enabled;
        }

        function applySeatSelectionUI() {
            if (!grid && !fallback) return;
            selectedSeats.forEach(function (seat) {
                const seatDiv = grid?.querySelector('[data-seat="' + seat + '"]')
                    || Array.from(fallback?.querySelectorAll('.seat') || []).find(function (el) {
                        return el.textContent.trim() === seat;
                    });
                if (seatDiv && !seatDiv.classList.contains('seat-booked')) {
                    seatDiv.classList.remove('seat-available');
                    seatDiv.classList.add('seat-selected');
                }
            });
        }

        function validatePassengers() {
            if (selectedSeats.length === 0) return false;
            return selectedSeats.every(function (seat) {
                const nameEl = passengersList?.querySelector('[data-passenger-name="' + seat + '"]');
                const phoneEl = passengersList?.querySelector('[data-passenger-phone="' + seat + '"]');
                const ageEl = passengersList?.querySelector('[data-passenger-age-group="' + seat + '"]');
                return nameEl?.value.trim() && phoneEl?.value.trim() && ageEl?.value;
            });
        }

        function updateContinueBtn() {
            if (nextExtrasBtn) {
                nextExtrasBtn.disabled = !validatePassengers();
            }
        }

        function renderPassengerFields() {
            if (!passengersList || !passengersWrap) return;

            const emptyEl = document.getElementById('passengersEmpty_' + uid);

            if (selectedSeats.length === 0) {
                passengersList.innerHTML = '';
                if (emptyEl) emptyEl.classList.remove('hidden');
                updateContinueBtn();
                return;
            }

            if (emptyEl) emptyEl.classList.add('hidden');

            selectedSeats.forEach(function (seat) {
                if (passengersList.querySelector('[data-passenger-seat="' + seat + '"]')) return;

                const card = document.createElement('div');
                card.className = 'inline-passenger-card';
                card.dataset.passengerSeat = seat;
                card.innerHTML =
                    '<p class="inline-passenger-card__seat"><i class="fas fa-chair"></i> Seat ' + seat + '</p>' +
                    '<div class="inline-passenger-card__fields">' +
                    '<div class="booking-field">' +
                    '<label class="booking-field__label">Full name <span class="text-red-500">*</span></label>' +
                    '<input type="text" class="page-input" data-passenger-name="' + seat + '" maxlength="30" required>' +
                    '</div>' +
                    '<div class="booking-field">' +
                    '<label class="booking-field__label">Phone <span class="text-red-500">*</span></label>' +
                    '<input type="tel" class="page-input" data-passenger-phone="' + seat + '" maxlength="12" required>' +
                    '</div>' +
                    '<div class="booking-field booking-field--full">' +
                    '<label class="booking-field__label">' + (config.ageGroupLabel || 'Age Group') + ' <span class="text-red-500">*</span></label>' +
                    '<select class="page-input" data-passenger-age-group="' + seat + '" required>' +
                    '<option value="Adult">Adult</option>' +
                    '<option value="Child">Child</option>' +
                    '<option value="Senior">Senior</option>' +
                    '</select>' +
                    '</div>' +
                    '</div>';
                passengersList.appendChild(card);

                card.querySelectorAll('input, select').forEach(function (input) {
                    input.addEventListener('input', function () {
                        updateContinueBtn();
                        syncWizardState();
                    });
                    input.addEventListener('change', function () {
                        updateContinueBtn();
                        syncWizardState();
                    });
                });
            });

            passengersList.querySelectorAll('[data-passenger-seat]').forEach(function (card) {
                if (!selectedSeats.includes(card.dataset.passengerSeat)) {
                    card.remove();
                }
            });

            syncWizardState();
            updateContinueBtn();
        }

        function toggleSeat(seat, seatDiv) {
            if (seatDiv.classList.contains('seat-booked')) return;
            if (selectedSeats.includes(seat)) {
                selectedSeats.splice(selectedSeats.indexOf(seat), 1);
                seatDiv.classList.remove('seat-selected');
                seatDiv.classList.add('seat-available');
            } else {
                if (selectedSeats.length >= config.maxSeats) {
                    alert(config.maxSeatsMsg);
                    return;
                }
                selectedSeats.push(seat);
                seatDiv.classList.remove('seat-available');
                seatDiv.classList.add('seat-selected');
            }
            updateTotals();
            renderPassengerFields();
        }

        function renderFromLayout() {
            if (!grid) return;
            if (!layout || !Number.isInteger(layout.rows) || !Number.isInteger(layout.cols)) {
                grid.style.display = 'none';
                if (fallback) {
                    fallback.style.display = '';
                    renderFallback();
                }
                return;
            }

            let rows = Math.max(1, layout.rows | 0);
            let cols = Math.max(1, layout.cols | 0);
            const aisles = Array.isArray(layout.aisles) ? layout.aisles : [];
            const seats = Array.isArray(layout.seats) ? layout.seats : [];

            // Expand grid to accommodate house seats placed outside declared bounds
            seats.forEach(function (s) {
                if (s.row > rows) rows = s.row;
                if (s.col > cols) cols = s.col;
            });

            grid.innerHTML = '';
            grid.style.display = 'grid';
            grid.style.setProperty('--rows', rows);
            grid.style.setProperty('--cols', cols);
            grid.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
            grid.style.gridTemplateRows = 'repeat(' + rows + ', 1fr)';
            grid.style.aspectRatio = cols + ' / ' + rows;

            const isAisle = function (r, c) {
                return aisles.some(function (a) { return a.row === r && a.col === c; });
            };
            const seatAt = function (r, c) {
                return seats.find(function (s) { return s.row === r && s.col === c; });
            };

            for (let r = 1; r <= rows; r++) {
                for (let c = 1; c <= cols; c++) {
                    const cell = document.createElement('div');
                    cell.className = 'grid-cell' + (isAisle(r, c) ? ' cell-aisle' : '');
                    const s = seatAt(r, c);
                    if (s) {
                        const lbl = s.label ?? '';
                        const seatDiv = document.createElement('div');
                        seatDiv.className = 'seat seat-available';
                        seatDiv.textContent = lbl;
                        seatDiv.dataset.seat = lbl;
                        if ((config.bookedSeats || []).includes(lbl)) {
                            seatDiv.classList.remove('seat-available');
                            seatDiv.classList.add('seat-booked');
                        } else {
                            seatDiv.onclick = function () { toggleSeat(lbl, seatDiv); };
                        }
                        cell.appendChild(seatDiv);
                    }
                    grid.appendChild(cell);
                }
            }
        }

        function renderFallback() {
            if (!fallback) return;
            fallback.innerHTML = '';
            let made = 0, rowNo = 0;
            const total = config.totalSeats || 40;
            while (made < total) {
                const L = String.fromCharCode(65 + rowNo);
                const remain = total - made;
                const rowDiv = document.createElement('div');
                rowDiv.className = 'seat-row';
                const row = remain >= 4
                    ? [L + '4', L + '3', '', L + '2', L + '1']
                    : [L + '2', L + '1', '', '', ''];
                row.forEach(function (seat) {
                    if (seat === '') {
                        const aisle = document.createElement('div');
                        aisle.className = 'aisle';
                        rowDiv.appendChild(aisle);
                    } else {
                        const seatDiv = document.createElement('div');
                        seatDiv.className = 'seat seat-available';
                        seatDiv.textContent = seat;
                        if ((config.bookedSeats || []).includes(seat)) {
                            seatDiv.classList.add('seat-booked');
                        } else {
                            seatDiv.onclick = function () { toggleSeat(seat, seatDiv); };
                        }
                        rowDiv.appendChild(seatDiv);
                        made++;
                    }
                });
                fallback.appendChild(rowDiv);
                rowNo++;
            }
        }

        renderFromLayout();
        applySeatSelectionUI();
        renderPassengerFields();
        updateTotals();

        if (nextExtrasBtn && !nextExtrasBtn.dataset.bound) {
            nextExtrasBtn.dataset.bound = '1';
            nextExtrasBtn.addEventListener('click', function () {
                if (!validatePassengers()) return;
                syncWizardState();
                clearPanelError(wizard);
                setWizardStep(wizard, 'extras');
            });
        }

        if (walletKey && config.walletLookupUrl) {
            let walletTimer;
            walletKey.addEventListener('input', function () {
                clearTimeout(walletTimer);
                walletTimer = setTimeout(async function () {
                    const key = walletKey.value.trim();
                    if (!key) {
                        if (walletAmount) walletAmount.textContent = amountLabel('0.00', config.currency);
                        if (walletAmountHidden) walletAmountHidden.value = '0';
                        return;
                    }
                    try {
                        const res = await fetch(config.walletLookupUrl + '?key=' + encodeURIComponent(key), {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        const amt = data.amount || 0;
                        if (walletAmount) walletAmount.textContent = amountLabel(formatMoney(amt), config.currency);
                        if (walletAmountHidden) walletAmountHidden.value = String(amt);
                    } catch (e) {
                        /* ignore */
                    }
                }, 400);
            });
        }

        if (insuranceToggle && !insuranceToggle.dataset.bound) {
            insuranceToggle.dataset.bound = '1';
            insuranceToggle.addEventListener('change', toggleInlineInsuranceFields);
            toggleInlineInsuranceFields();
        }

        if (extrasForm && !extrasForm.dataset.bound) {
            extrasForm.dataset.bound = '1';
            extrasForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                clearPanelError(wizard);

                syncWizardState();

                const seatsValue = hiddenSeats?.value || selectedSeats.join(',');
                const totalValue = hiddenTotal?.value || String(selectedSeats.length * config.seatPrice);
                let passengers = collectPassengersFromDom();

                if (passengers.length === 0 && hiddenPassengers?.value) {
                    try {
                        passengers = JSON.parse(hiddenPassengers.value) || [];
                    } catch (err) {
                        passengers = [];
                    }
                }

                if (!seatsValue) {
                    showPanelError(wizard, t('select_at_least_one_seat', 'Please select at least one seat.'));
                    return;
                }

                if (insuranceToggle?.checked) {
                    if (!insuranceType?.value) {
                        showPanelError(wizard, t('select_insurance_type', 'Please select insurance type.'));
                        return;
                    }
                    if (!insuranceDate?.value) {
                        showPanelError(wizard, t('select_insurance_date', 'Please select insurance date.'));
                        return;
                    }
                }

                const fd = new FormData(extrasForm);
                fd.set('inline', '1');
                fd.set('selected_seats', seatsValue);
                fd.set('total_amount', totalValue);
                fd.set('passengers', JSON.stringify(passengers));

                const submitBtn = extrasForm.querySelector('[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                try {
                    const res = await fetch(config.prepareUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Inline-Booking': '1',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: fd,
                    });

                    const data = await res.json().catch(function () { return {}; });

                    if (!res.ok || !data.ok) {
                        showPanelError(wizard, data.message || t('something_went_wrong', 'Something went wrong. Please try again.'));
                        return;
                    }

                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }

                    const paymentPane = wizard.querySelector('[data-wizard-pane="payment"]');
                    if (paymentPane && data.html) {
                        paymentPane.innerHTML = data.html;
                        initInlinePayment(paymentPane);
                        setWizardStep(wizard, 'payment');
                    }
                } catch (err) {
                    showPanelError(wizard, t('network_error_try_again', 'Network error. Please try again.'));
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        }
    }

    function bindInlineForms(inner) {
        inner.querySelectorAll('[data-inline-form]').forEach(function (form) {
            if (form.dataset.bound) return;
            form.dataset.bound = '1';

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const panel = form.closest('.inline-booking-panel');
                clearPanelError(panel);

                if (form.dataset.inlineForm === 'pickup') {
                    const distancePanel = panel || form.closest('.inline-booking-panel');
                    if (distancePanel?._inlineDistanceCalc) {
                        await distancePanel._inlineDistanceCalc();
                    }
                    if (distancePanel?._inlineDistancePromise) {
                        await distancePanel._inlineDistancePromise;
                    }
                    inner._pickupState = readPickupFormState(inner);
                }

                const fd = new FormData(form);
                fd.set('inline', '1');

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Inline-Booking': '1',
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });

                    const data = await res.json().catch(function () { return {}; });

                    if (!res.ok || !data.ok) {
                        showPanelError(panel, data.message || t('something_went_wrong', 'Something went wrong. Please try again.'));
                        return;
                    }

                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }

                    if (data.html) {
                        if (form.dataset.inlineForm === 'pickup') {
                            inner._pickupHtml = capturePickupHtml(inner);
                        }
                        inner.innerHTML = data.html;
                        bindInlineForms(inner);
                        inner.querySelectorAll('[data-inline-wizard]').forEach(initInlineWizard);
                        inner.querySelector('.home-bus-row-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                } catch (err) {
                    showPanelError(panel, t('network_error_try_again', 'Network error. Please try again.'));
                }
            });
        });

        inner.querySelectorAll('.inline-booking-panel').forEach(function (panel) {
            if (panel.querySelector('[data-inline-form="pickup"]')) {
                initInlineSelect2(panel);
            }
        });

        inner.querySelectorAll('[data-inline-wizard]').forEach(initInlineWizard);
    }

    list?.addEventListener('click', async function (e) {
        const tabBtn = e.target.closest('[data-inline-pay-tab]');
        if (tabBtn) {
            e.preventDefault();
            e.stopPropagation();
            const paymentRoot = tabBtn.closest('[data-inline-payment]');
            if (paymentRoot && tabBtn.dataset.inlinePayTab) {
                syncInlinePaymentTabs(paymentRoot, tabBtn.dataset.inlinePayTab);
            }
            return;
        }

        const backBtn = e.target.closest('[data-inline-nav-back]');
        if (backBtn) {
            e.preventDefault();
            const target = backBtn.dataset.inlineNavBack;
            const inner = backBtn.closest('.home-bus-row__expand-inner');
            const wrap = backBtn.closest('.home-bus-row-wrap');

            if (target === 'collapse') {
                collapseInlinePanel(wrap);
                return;
            }

            if (inner) {
                await navigateInlineStep(inner, target);
            }
            return;
        }

        const stepBtn = e.target.closest('[data-booking-step]:not(:disabled)');
        if (stepBtn && stepBtn.closest('[data-inline-timeline]')) {
            e.preventDefault();
            const inner = stepBtn.closest('.home-bus-row__expand-inner');
            if (inner) {
                await navigateInlineStep(inner, stepBtn.dataset.bookingStep);
            }
            return;
        }

        const btn = e.target.closest('[data-inline-book]');
        if (!btn) return;

        const wrap = btn.closest('.home-bus-row-wrap');
        const expand = wrap?.querySelector('.home-bus-row__expand');
        const inner = expand?.querySelector('.home-bus-row__expand-inner');
        const article = wrap?.querySelector('.home-bus-row');
        if (!expand || !inner) return;

        const isOpen = !expand.hidden && btn.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            collapseInlinePanel(wrap);
            return;
        }

        collapseAllExcept(wrap);
        expand.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        article?.classList.add('home-bus-row--expanded');

        inner.innerHTML = '<div class="inline-booking-loading"><i class="fas fa-spinner fa-spin"></i> ' + t('loading', 'Loading…') + '</div>';

        try {
            const res = await fetch(btn.dataset.inlineUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!res.ok) {
                const err = await res.json().catch(function () { return {}; });
                inner.innerHTML = '<div class="inline-booking-panel__error booking-alert booking-alert--error">' +
                    (err.message || t('unable_load_booking_form', 'Unable to load booking form.')) + '</div>';
                return;
            }

            inner.innerHTML = await res.text();
            inner._pickupHtml = capturePickupHtml(inner);
            inner._inlineFormUrl = btn.dataset.inlineUrl;
            bindInlineForms(inner);
            expand.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (err) {
            inner.innerHTML = '<div class="inline-booking-panel__error booking-alert booking-alert--error">' + t('network_error_try_again', 'Network error. Please try again.') + '</div>';
        }
    });

    document.addEventListener('click', function (e) {
        if (list?.contains(e.target)) return;

        const tabBtn = e.target.closest('[data-inline-pay-tab]');
        if (tabBtn) {
            e.preventDefault();
            const paymentRoot = tabBtn.closest('[data-inline-payment]');
            if (paymentRoot && tabBtn.dataset.inlinePayTab) {
                syncInlinePaymentTabs(paymentRoot, tabBtn.dataset.inlinePayTab);
            }
        }
    });

    document.querySelectorAll('[data-inline-payment]').forEach(function (paymentRoot) {
        if (paymentRoot.closest('.home-search-results__list')) return;
        initInlinePayment(paymentRoot);
    });
});
