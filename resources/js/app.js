import $ from 'jquery'
window.$ = window.jQuery = $;
import * as moment from 'moment';
window.moment = moment;
import * as DateRangePicker from 'bootstrap-daterangepicker';
import '@popperjs/core'
import { createPopper } from '@popperjs/core';
import 'bootstrap/js/dist/collapse';
import 'bootstrap/js/dist/tooltip';
import Popover from 'bootstrap/js/dist/popover';
import Modal from 'bootstrap/js/dist/modal';
import { ModuleFactory as bsMultiSelectFactory } from '@dashboardcode/bsmultiselect';

$(function() {

});

// GRID JAVASCRIPT
(function () {
    'use strict'

    if (document.querySelector('.dashboard') !== null) {
        // We are on dashboard, load data
        fetch('/api/dashboard')
            .then(response => response.json())
            .then(data => {
                for (const key in data) {
                    const formatter = new Intl.NumberFormat('en-US');
                    const placeholder = document.querySelector(`.data-${key}`);
                    if (placeholder !== null) {
                        placeholder.innerHTML = formatter.format(data[key]);
                    }
                }
            });
    }

    // Initialize all date pickers
    const datePickerList = [].slice.call(document.querySelectorAll('input.date-picker'));
    datePickerList.forEach(function (datePickerEl) {
        new DateRangePicker(datePickerEl, {
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'MM/DD/YYYY',
                separator: '-',
                cancelLabel: 'Clear',
            },
            linkedCalendars: false,
            showCustomRangeLabel: false,
            alwaysShowCalendars: true,
            opens: "left",
            drops: "auto",
            autoUpdateInput: false,
        });
        console.log(datePickerEl);
        $(datePickerEl).on('apply.daterangepicker', function (event, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + '-' + picker.endDate.format('MM/DD/YYYY'));
        });
        $(datePickerEl).on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
    });

    // Initialize all multiple selects
    const multipleSelectList = [].slice.call(document.querySelectorAll('select[multiple="multiple"]'));
    if (multipleSelectList.length > 0) {
        const environment = { window, createPopper };
        const { BsMultiSelect } = bsMultiSelectFactory(environment);
        multipleSelectList.forEach(function (multipleSelectEl) {
            BsMultiSelect(multipleSelectEl, {
                useChoicesDynamicStyling: true,
            });
        });
    }

    // Initialize all popovers on page
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(function (popoverTriggerEl) {
        new Popover(popoverTriggerEl);
    });

    // Grid controls
    const controlsTriggerList = [].slice.call(document.querySelectorAll('.grid-controls select'));
    controlsTriggerList.forEach(function (controlsTriggerEl) {
        controlsTriggerEl.addEventListener('change', function () {
            controlsTriggerEl.form.submit();
        });
    });

    /** @type {HTMLAnchorElement} */
    const subscribeBtn = document.querySelector('.btn-subscribe');
    if (subscribeBtn !== null) {
        const subscribeModalEl = document.getElementById('subscribeModal');
        /** @type {Modal} */
        const subscribeModal = new Modal(subscribeModalEl);
        // const modalTriggerListener = function (event) {
        //     event.preventDefault();
        //     /** @type {HTMLAnchorElement|HTMLFormElement} */
        //     const target = event.currentTarget;
        //     modalEl.querySelector('.modal-body').textContent = target.dataset['confirm'];
        //     modalEl.querySelector('.modal-confirm').addEventListener('click', function () {
        //         console.log('click');
        //         if (target instanceof HTMLAnchorElement) {
        //             target.removeEventListener('click', modalTriggerListener);
        //             target.click();
        //         } else if (target instanceof HTMLFormElement) {
        //             target.removeEventListener('submit', modalTriggerListener);
        //             target.submit();
        //         }
        //     });
        //     modal.show();
        // };

        subscribeBtn.addEventListener('click', function (event) {
            /** @type {HTMLAnchorElement} */
            const target = event.currentTarget;
            if (!target.href.includes('name=')) {
                event.preventDefault();
                subscribeModalEl.querySelector('.modal-confirm').addEventListener('click', function () {
                    const name = subscribeModalEl.querySelector('[name="name"]').value;
                    target.href = `${target.href}&name=${encodeURIComponent(name)}`;
                    target.click();
                    // fetch(url, {
                    //     headers: {
                    //         'X-Requested-With': 'XMLHttpRequest'
                    //     },
                    // }).then(response => response.json())
                    //     .then(data => {
                    //         if (data === true) {
                    //
                    //         }
                    //     });
                });
                subscribeModal.show();
            }
        });
    }

    // Grid filters
    /** @type {HTMLFormElement} */
    const filtersForm = document.querySelector('.grid-filters-form');
    if (filtersForm !== null) {
        console.log(`Initializing grid filters...`);
        document.querySelector('.grid-filters .btn-filters-apply').addEventListener('click', function () {
            submitFiltersForm();
        });
        document.querySelector('.grid-filters .btn-filters-reset').addEventListener('click', function () {
            filtersForm.submit();
        });
        const filterFormTriggerList = [].slice.call(document.querySelectorAll('.grid-filters input'));
        filterFormTriggerList.forEach(function (filterFormTriggerEl) {
            filterFormTriggerEl.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    submitFiltersForm();
                }
            });
        });

        function submitFiltersForm() {
            const inputs = document.querySelectorAll('.grid-filter > input, .grid-filter > select');
            inputs.forEach(function (input) {
                let duplicate = null;
                if (input instanceof HTMLInputElement) {
                    const value = input.value;
                    if (value !== '') {
                        duplicate = input.cloneNode(true);
                        duplicate.setAttribute('type', 'hidden');
                        filtersForm.appendChild(duplicate);
                    }
                } else if (input instanceof HTMLSelectElement) {
                    if (input.multiple === false) {
                        const value = input.options[input.selectedIndex].value;
                        if (value !== '') {
                            /** @type {HTMLInputElement} */
                            duplicate = document.createElement('input');
                            duplicate.name = input.name;
                            duplicate.value = value;
                            duplicate.setAttribute('type', 'hidden');
                            filtersForm.appendChild(duplicate);
                        }
                    } else {
                        let value = null;
                        [].slice.call(input.options).forEach((/** HTMLOptionElement */ option) => {
                            if (option.selected === true) {
                                value = value === null ? option.value : `${value} | ${option.value}`;
                            }
                        });
                        if (value !== null) {
                            /** @type {HTMLInputElement} */
                            duplicate = document.createElement('input');
                            duplicate.name = input.name;
                            duplicate.value = value;
                            console.log(value);
                            duplicate.setAttribute('type', 'hidden');
                            filtersForm.appendChild(duplicate);
                        }
                    }
                }

                filtersForm.submit();
            });
        }
    }

    // Confirm modals
    const modalEl = document.getElementById('confirmModal');
    if (modalEl !== null) {
        /** @type {Modal} */
        const modal = new Modal(modalEl);
        const modalTriggerListener = function (event) {
            event.preventDefault();
            /** @type {HTMLAnchorElement|HTMLFormElement} */
            const target = event.currentTarget;
            modalEl.querySelector('.modal-body').textContent = target.dataset['confirm'];
            modalEl.querySelector('.modal-confirm').addEventListener('click', function () {
                if (target instanceof HTMLAnchorElement) {
                    target.removeEventListener('click', modalTriggerListener);
                    target.click();
                } else if (target instanceof HTMLFormElement) {
                    target.removeEventListener('submit', modalTriggerListener);
                    target.submit();
                }
            });
            modal.show();
        };
        const confirmTriggerList = [].slice.call(document.querySelectorAll('.data-grid .needs-confirmation'));
        confirmTriggerList.forEach(function (confirmTriggerEl) {
            if (confirmTriggerEl instanceof HTMLAnchorElement) {
                confirmTriggerEl.addEventListener('click', modalTriggerListener);
            } else if (confirmTriggerEl instanceof HTMLFormElement) {
                confirmTriggerEl.addEventListener('submit', modalTriggerListener);
            }
        });
    }
})();
