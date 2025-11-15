jQuery(document).ready(function($) {
    'use strict';

    const PayoutManager = {
        init: function() {
            this.currentPage = 1;
            this.consultantsCurrentPage = 1;
            this.itemsPerPage = 20;
            this.currentFilters = {};
            this.selectedPayouts = [];
            
            this.loadPayouts();
            this.loadConsultantsWithPendingCommissions()
            this.bindEvents();
        },

        bindEvents: function() {
            // فیلترها
            $('#apply-filters').on('click', () => this.applyFilters());
            $('#reset-filters').on('click', () => this.resetFilters());
            
            // پاگیشن
            $(document).on('click', '.pagination-btn:not(.active):not(:disabled)', (e) => {
                const page = $(e.target).data('page');
                if (page) this.changePage(page);
            });
            
            $('.payout-tab-button').on('click', (e) => this.switchTab(e));
            
            // دکمه ایجاد تسویه از جدول مشاوران
            $(document).on('click', '.create-payout-for-consultant', (e) => this.createPayoutForConsultant(e));            
            
            // مدیریت بستن modal ها
            $(document).on('click', '.modal-close, .modal-cancel, .modal .modal-close-btn', () => this.closeModals());
            
            // جلوگیری از بستن modal با کلیک روی محتوای آن
            $(document).on('click', '.modal-content', (e) => {
                e.stopPropagation();
            });
            
            // بستن modal با کلید ESC
            $(document).on('keydown', (e) => {
                if (e.keyCode === 27) { // ESC key
                    this.closeModals();
                }
            });            

            // دکمه‌های action
            $(document).on('click', '.view-payout', (e) => this.viewPayoutDetails(e));
            $(document).on('click', '.pay-payout', (e) => this.payPayout(e));
            $(document).on('click', '.export-payout', (e) => this.exportPayout(e));
            $(document).on('click', '.delete-payout', (e) => this.deletePayout(e));

            // دکمه‌های هدر
            $('#create-payout').on('click', () => this.showCreatePayoutModal());
            $('#export-csv').on('click', () => this.exportCSV());
            $('#refresh-data').on('click', () => this.refreshData());

            // مودال‌ها
            $('.modal-close, .modal-cancel').on('click', () => this.closeModals());
            $(document).on('click', (e) => {
                if ($(e.target).hasClass('modal')) {
                    this.closeModals();
                }
            });

            // انتخاب مشاور در مودال ایجاد
            $('#consultant-select').on('change', () => this.loadUnpaidCommissions());
            
            // ثبت تسویه جدید
            $('#create-payout-form').on('submit', (e) => this.createPayout(e));
            
            // تأیید پرداخت
            $('#confirm-payment-form').on('submit', (e) => this.confirmPayment(e));
        },
        
        
        switchTab: function(e) {
            const tabButton = $(e.currentTarget);
            const tabId = tabButton.data('tab');
            
            // غیرفعال کردن همه تب‌ها
            $('.payout-tab-button').removeClass('active');
            $('.payout-tab-content').removeClass('active');
            
            // فعال کردن تب انتخاب شده
            tabButton.addClass('active');
            $(`#${tabId}`).addClass('active');
            
            // اگر تب مشاوران انتخاب شد، داده‌ها رو لود کن
            // if (tabId === 'consultants-tab') {
            //     this.loadConsultantsWithPendingCommissions();
            // }
        },     
        
        // تابع برای لود کردن مشاوران با کمیسیون پرداخت نشده
        loadConsultantsWithPendingCommissions: function() {
            const self = this;
            const $tableBody = $('#consultants-table-body');
            
            $tableBody.html(`
                <tr>
                    <td colspan="6" class="payout-loading">
                        <div class="spinner"></div>
                        در حال بارگذاری مشاوران...
                    </td>
                </tr>
            `);
        
            $.ajax({
                url: admin_payout_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_get_consultants_with_pending',
                    nonce: admin_payout_ajax.nonce,
                    page: this.consultantsCurrentPage,
                    per_page: this.itemsPerPage,
                    filters: this.currentFilters
                },
                success: function(response) {
                    if (response.success) {
                        self.renderConsultantsTable(response.data);
                    } else {
                        self.showMessage('خطا در بارگذاری مشاوران: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('خطا در ارتباط با سرور', 'error');
                }
            });
        },        
        
        
        // تابع برای رندر کردن جدول مشاوران
        renderConsultantsTable: function(data) {
            const $tableBody = $('#consultants-table-body');
            const $pagination = $('.consultants-pagination');
            
            if (data.consultants.length === 0) {
                $tableBody.html(`
                    <tr>
                        <td colspan="6" class="payout-empty-state">
                            <i class="fas fa-user-slash"></i>
                            <p>هیچ مشاوری با کمیسیون پرداخت نشده یافت نشد</p>
                        </td>
                    </tr>
                `);
                $pagination.hide();
                return;
            }
        
            let html = '';
            data.consultants.forEach(consultant => {
                html += `
                    <tr data-consultant-id="${consultant.id}">
                        <td>
                            <div class="consultant-info">
                                <span class="consultant-name">${consultant.name}</span>
                                <span class="consultant-email">${consultant.email}</span>
                                <span class="consultant-id">ID: ${consultant.id}</span>
                            </div>
                        </td>
                        <td class="count-cell">
                            <span class="count-badge">${consultant.pending_count}</span>
                        </td>
                        <td class="amount-cell pending">
                            ${this.formatNumber(consultant.total_pending)} تومان
                        </td>
                        <td class="amount-cell">
                            ${this.formatNumber(consultant.average_amount)} تومان
                        </td>
                        <td>
                            <span class="oldest-date">${consultant.oldest_date || '---'}</span>
                        </td>
                        <td>
                            <div class="consultant-actions">
                                <button class="consultant-action-btn success create-payout-for-consultant" 
                                        data-consultant-id="${consultant.id}">
                                    <i class="fas fa-money-bill-wave"></i>
                                    ایجاد تسویه
                                </button>
                                <button class="consultant-action-btn primary view-consultant-details" 
                                        data-consultant-id="${consultant.id}">
                                    <i class="fas fa-eye"></i>
                                    جزئیات
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        
            $tableBody.html(html);
            this.renderConsultantsPagination(data.pagination);
            $pagination.show();
        },
        
        // تابع برای رندر پاگینیشن مشاوران
        renderConsultantsPagination: function(pagination) {
            const $pagination = $('.consultants-pagination .pagination-buttons');
            let html = '';
        
            if (pagination.total_pages > 1) {
                // دکمه قبلی
                html += `
                    <button class="pagination-btn" data-page="${pagination.current_page - 1}" 
                            ${pagination.current_page === 1 ? 'disabled' : ''}>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                `;
        
                // صفحات
                for (let i = 1; i <= pagination.total_pages; i++) {
                    if (i === 1 || i === pagination.total_pages || 
                        (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                        html += `
                            <button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                                    data-page="${i}">
                                ${i}
                            </button>
                        `;
                    } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                        html += `<span class="pagination-dots">...</span>`;
                    }
                }
        
                // دکمه بعدی
                html += `
                    <button class="pagination-btn" data-page="${pagination.current_page + 1}" 
                            ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                `;
            }
        
            $pagination.html(html);
            $('.consultants-pagination .pagination-info').text(
                `نمایش ${pagination.start_item}-${pagination.end_item} از ${pagination.total_items} مورد`
            );
            
            // bind pagination events
            $('.consultants-pagination .pagination-btn:not(.active):not(:disabled)').on('click', (e) => {
                const page = $(e.target).data('page');
                if (page) this.changeConsultantsPage(page);
            });
        },
        
        // تابع برای تغییر صفحه در جدول مشاوران
        changeConsultantsPage: function(page) {
            this.consultantsCurrentPage = parseInt(page);
            this.loadConsultantsWithPendingCommissions();
        },
        
        // تابع برای ایجاد تسویه از جدول مشاوران
        createPayoutForConsultant: function(e) {
            const consultantId = $(e.currentTarget).data('consultant-id');
        
            // showCreatePayoutModal() حالا Promise برمی‌گرداند
            this.showCreatePayoutModal()
                .then(() => {
                    // وقتی لود کامل شد، مقدار را تنظیم کن و change را تریگر کن
                    if ($('#consultant-select option[value="' + consultantId + '"]').length > 0) {
                        $('#consultant-select').val(consultantId).trigger('change');
                    } else {
                        // اگر مشاور پیدا نشد، ممکنه بخواهی پیام بدهی یا گزینه‌ای را اضافه کنی
                        this.showMessage('مشاور مورد نظر یافت نشد', 'warning');
                    }
                })
                .catch((err) => {
                    // خطا در لود مشاوران — می‌تونی پیام نشان بدی
                    console.error('loadConsultants error:', err);
                    this.showMessage('امکان بارگذاری لیست مشاوران وجود ندارد', 'error');
                });
        },


        loadPayouts: function() {
            const self = this;
            const $tableBody = $('#payouts-table-body');
            
            $tableBody.html(`
                <tr>
                    <td colspan="11" class="payout-loading">
                        <div class="spinner"></div>
                        در حال بارگذاری داده‌ها...
                    </td>
                </tr>
            `);

            $.ajax({
                url: admin_payout_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_get_payouts',
                    nonce: admin_payout_ajax.nonce,
                    page: this.currentPage,
                    per_page: this.itemsPerPage,
                    filters: this.currentFilters
                },
                success: function(response) {
                    if (response.success) {
                        self.renderPayoutsTable(response.data);
                        self.updateSummary(response.data.summary);
                    } else {
                        self.showMessage('خطا در بارگذاری داده‌ها: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('خطا در ارتباط با سرور', 'error');
                }
            });
        },

        renderPayoutsTable: function(data) {
            const $tableBody = $('#payouts-table-body');
            const $pagination = $('.payout-pagination');
            
            if (data.payouts.length === 0) {
                $tableBody.html(`
                    <tr>
                        <td colspan="11" class="payout-empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>هیچ تسویه‌ای یافت نشد</p>
                        </td>
                    </tr>
                `);
                $pagination.hide();
                return;
            }

            let html = '';
            data.payouts.forEach(payout => {
                html += `
                    <tr data-payout-id="${payout.id}">
                        <td>${payout.id}</td>
                        <td>
                            <strong>${payout.consultant_name}</strong>
                            <br><small>${payout.consultant_email}</small>
                        </td>
                        <td>
                            ${payout.period_start} تا ${payout.period_end}
                        </td>
                        <td>${this.formatNumber(payout.amount)} تومان</td>
                        <td>${payout.commissions_count}</td>
                        <td>
                            <span class="status-badge status-${payout.status}">
                                ${this.getStatusText(payout.status)}
                            </span>
                        </td>
                        <td>${this.getPaymentMethodText(payout.payment_method)}</td>
                        <td>${payout.reference_code || '---'}</td>
                        <td>
                            ${payout.paid_at ? payout.paid_at : payout.created_at}
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn view view-payout" data-payout-id="${payout.id}">
                                    <i class="fas fa-eye"></i> مشاهده
                                </button>
                                ${payout.status === 'pending' ? `
                                    <button class="action-btn pay pay-payout" data-payout-id="${payout.id}">
                                        <i class="fas fa-money-bill-wave"></i> تسویه
                                    </button>
                                ` : ''}
                                <button class="action-btn export export-payout" data-payout-id="${payout.id}">
                                    <i class="fas fa-download"></i> خروجی
                                </button>
                                ${payout.status === 'pending' ? `
                                    <button class="action-btn delete delete-payout" data-payout-id="${payout.id}">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });

            $tableBody.html(html);
            this.renderPagination(data.pagination);
            $pagination.show();
        },

        renderPagination: function(pagination) {
            const $pagination = $('.pagination-buttons');
            let html = '';

            if (pagination.total_pages > 1) {
                // دکمه قبلی
                html += `
                    <button class="pagination-btn" data-page="${pagination.current_page - 1}" 
                            ${pagination.current_page === 1 ? 'disabled' : ''}>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                `;

                // صفحات
                for (let i = 1; i <= pagination.total_pages; i++) {
                    if (i === 1 || i === pagination.total_pages || 
                        (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                        html += `
                            <button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                                    data-page="${i}">
                                ${i}
                            </button>
                        `;
                    } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                        html += `<span class="pagination-dots">...</span>`;
                    }
                }

                // دکمه بعدی
                html += `
                    <button class="pagination-btn" data-page="${pagination.current_page + 1}" 
                            ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                `;
            }

            $pagination.html(html);
            $('.pagination-info').text(
                `نمایش ${pagination.start_item}-${pagination.end_item} از ${pagination.total_items} مورد`
            );
        },

        updateSummary: function(summary) {
            $('#total-pending').text(this.formatNumber(summary.total_pending));
            $('#total-paid-month').text(this.formatNumber(summary.total_paid_month));
            $('#consultants-with-balance').text(summary.consultants_with_balance);
            
            if (summary.last_payout) {
                $('#last-payout').html(`
                    ${summary.last_payout.consultant_name}<br>
                    <small>${this.formatNumber(summary.last_payout.amount)} تومان - ${summary.last_payout.paid_at}</small>
                `);
            } else {
                $('#last-payout').text('---');
            }
        },

applyFilters: function() {
    // جمع‌آوری فیلترها
    this.currentFilters = {
        search: $('#filter-search').val(),
        status: $('#filter-status').val(),
        date_from: $('#filter-date-from').val(),
        date_to: $('#filter-date-to').val(),
        min_amount: $('#filter-min-amount').val(),
        max_amount: $('#filter-max-amount').val()
    };
    
    this.currentPage = 1;

    // پیدا کردن تب فعال
    const $activeTab = $('.payout-tab-button.active');
    const activeTabId = $activeTab.data('tab');

    // بر اساس تب فعال، داده‌ها را لود کن
    if (activeTabId === 'payouts-tab') {
        this.loadPayouts(this.currentFilters);
    } else if (activeTabId === 'consultants-tab') {
        this.loadConsultantsWithPendingCommissions(this.currentFilters);
    }
},


        resetFilters: function() {
            $('#payout-filters input, #payout-filters select').val('');
            this.currentFilters = {};
            this.currentPage = 1;
            this.loadPayouts();
        },

        changePage: function(page) {
            this.currentPage = parseInt(page);
            this.loadPayouts();
        },

        viewPayoutDetails: function(e) {
            const payoutId = $(e.currentTarget).data('payout-id');
            this.showPayoutDetailsModal(payoutId);
        },

        showPayoutDetailsModal: function(payoutId) {
            const self = this;
            
            // نمایش loading در modal
            $('#payout-details-modal .modal-body').html(`
                <div class="payout-loading">
                    <div class="spinner"></div>
                    در حال بارگذاری جزئیات...
                </div>
            `);
            $('#payout-details-modal').fadeIn(300);
                        
            
            $.ajax({
                url: admin_payout_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_get_payout_details',
                    nonce: admin_payout_ajax.nonce,
                    payout_id: payoutId
                },
                beforeSend: function() {
                    $('#payout-details-modal .modal-body').html(`
                        <div class="payout-loading">
                            <div class="spinner"></div>
                            در حال بارگذاری جزئیات...
                        </div>
                    `);
                },
                success: function(response) {
                    if (response.success) {
                        self.renderPayoutDetails(response.data);
                        $('#payout-details-modal').show();
                    } else {
                        self.showMessage('خطا در بارگذاری جزئیات', 'error');
                    }
                },
                error: function() {
                    self.showMessage('خطا در ارتباط با سرور', 'error');
                }
            });
        },

        renderPayoutDetails: function(data) {
            const modalBody = $('#payout-details-modal .modal-body');
            let commissionsHtml = '';
            
            data.commissions.forEach(commission => {
                commissionsHtml += `
                    <tr>
                        <td>${commission.id}</td>
                        <td>#${commission.request_id}</td>
                        <td>${commission.generated_at}</td>
                        <td>${commission.approved_at || '---'}</td>
                        <td>${this.formatNumber(commission.base_amount)} تومان</td>
                        <td>${commission.commission_type === 'percent' ? 'درصدی' : 'ثابت'}</td>
                        <td>${commission.commission_value}</td>
                        <td>${commission.delay_hours || '---'}</td>
                        <td>${commission.penalty_multiplier || '---'}</td>
                        <td class="commission-amount">${this.formatNumber(commission.final_commission)} تومان</td>
                    </tr>
                `;
            });

            modalBody.html(`
                <div class="payout-summary">
                    <h4>خلاصه تسویه</h4>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <label>شناسه:</label>
                            <span>${data.payout.id}</span>
                        </div>
                        <div class="summary-item">
                            <label>وضعیت:</label>
                            <span class="status-badge status-${data.payout.status}">
                                ${this.getStatusText(data.payout.status)}
                            </span>
                        </div>
                        <div class="summary-item">
                            <label>مشاور:</label>
                            <span>${data.payout.consultant_name} (${data.payout.consultant_email})</span>
                        </div>
                        <div class="summary-item">
                            <label>مبلغ کل:</label>
                            <span class="amount">${this.formatNumber(data.payout.amount)} تومان</span>
                        </div>
                        <div class="summary-item">
                            <label>بازه زمانی:</label>
                            <span>${data.payout.period_start} تا ${data.payout.period_end}</span>
                        </div>
                        <div class="summary-item">
                            <label>روش پرداخت:</label>
                            <span>${this.getPaymentMethodText(data.payout.payment_method)}</span>
                        </div>
                        <div class="summary-item">
                            <label>شماره پیگیری:</label>
                            <span>${data.payout.reference_code || '---'}</span>
                        </div>
                        <div class="summary-item">
                            <label>تاریخ پرداخت:</label>
                            <span>${data.payout.paid_at || '---'}</span>
                        </div>
                    </div>
                </div>

                <div class="commissions-section">
                    <h4>کمیسیون‌ها (${data.commissions.length} مورد)</h4>
                    <div class="table-wrapper">
                        <table class="commissions-table">
                            <thead>
                                <tr>
                                    <th>ID کمیسیون</th>
                                    <th>درخواست</th>
                                    <th>تاریخ تولید</th>
                                    <th>تاریخ تایید</th>
                                    <th>مبلغ پایه</th>
                                    <th>نوع</th>
                                    <th>مقدار</th>
                                    <th>تأخیر</th>
                                    <th>ضریب</th>
                                    <th>مبلغ نهایی</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${commissionsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>

                ${data.payout.status === 'pending' ? `
                    <div class="modal-actions">
                        <button class="admin-payout-btn success" id="confirm-payment-btn" 
                                data-payout-id="${data.payout.id}">
                            <i class="fas fa-check"></i> تأیید پرداخت
                        </button>
                    </div>
                ` : ''}
            `);

            // bind confirm payment button
            $('#confirm-payment-btn').on('click', () => {
                this.showConfirmPaymentModal(data.payout.id);
            });
        },

        payPayout: function(e) {
            const payoutId = $(e.currentTarget).data('payout-id');
            this.showConfirmPaymentModal(payoutId);
        },

        showConfirmPaymentModal: function(payoutId) {
            $('#confirm-payment-modal').data('payout-id', payoutId).fadeIn(300);
            $('#payment-reference').focus();
        },
        
        confirmPayment: function(e) {
            e.preventDefault();
            
            const payoutId = $('#confirm-payment-modal').data('payout-id');
            const referenceCode = $('#payment-reference').val();
            
            if (!referenceCode.trim()) {
                this.showMessage('لطفاً شماره پیگیری را وارد کنید', 'warning');
                return;
            }

            const self = this;
            
            $.ajax({
                url: admin_payout_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_mark_payout_done',
                    nonce: admin_payout_ajax.nonce,
                    payout_id: payoutId,
                    reference_code: referenceCode
                },
                beforeSend: function() {
                    $('#confirm-payment-btn').prop('disabled', true).html(`
                        <div class="spinner"></div> در حال پردازش...
                    `);
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage('پرداخت با موفقیت تأیید شد', 'success');
                        self.closeModals();
                        self.loadPayouts();
                    } else {
                        self.showMessage('خطا در تأیید پرداخت: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('خطا در ارتباط با سرور', 'error');
                },
                complete: function() {
                    $('#confirm-payment-btn').prop('disabled', false).html(`
                        <i class="fas fa-check"></i> تأیید پرداخت
                    `);
                }
            });
        },
        
        loadConsultants: function() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: admin_payout_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'admin_get_consultants',
                        nonce: admin_payout_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value="">انتخاب مشاور...</option>';
                            response.data.forEach(consultant => {
                                options += `<option value="${consultant.id}">${consultant.name}</option>`;
                            });
                            $('#consultant-select').html(options);
                            resolve(response.data); // ✅
                        } else {
                            reject('load error');
                        }
                    },
                    error: function() {
                        reject('ajax error');
                    }
                });
            });
        },
       

showCreatePayoutModal: function() {
    const $modal = $('#create-payout-modal');
    const $modalBody = $modal.find('.modal-body');

    // نمایش مودال
    $modal.fadeIn(300);

    // حالت تاریخ‌ها
    const today = new Date().toISOString().split('T')[0];
    const firstDayOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 2)
        .toISOString().split('T')[0];
    $('#period-start').val(firstDayOfMonth);
    $('#period-end').val(today);

    // اضافه کردن overlay برای قفل کردن محتوا
    $modalBody.append(`
        <div class="loading-overlay" style="
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        ">
            در حال بارگذاری مشاوران...
        </div>
    `);

    // بازگرداندن Promise از loadConsultants
    return this.loadConsultants()
        .then(() => {
            // برداشتن overlay وقتی لود تمام شد
            $modalBody.find('.loading-overlay').remove();
        })
        .catch((err) => {
            $modalBody.find('.loading-overlay').remove();
            console.error('loadConsultants error:', err);
            this.showMessage('امکان بارگذاری لیست مشاوران وجود ندارد', 'error');
        });
},

        
        
        loadUnpaidCommissions: function() {
            const consultantId = $('#consultant-select').val();
            if (!consultantId) return;
        
            const self = this;
            const $modalBody = $('.modal-body');
        
            // اضافه کردن overlay برای قفل کردن محتوا
            const $overlay = $(`
                <div class="modal-overlay" style="
                    position:absolute;
                    top:0;
                    left:0;
                    width:100%;
                    height:100%;
                    background:rgba(255,255,255,0.8);
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    z-index:9999;
                    font-weight:bold;
                    font-size:16px;
                    color:#333;
                ">
                    در حال بارگذاری اطلاعات...
                </div>
            `);
        
            // اطمینان از اینکه modal-body position:relative داشته باشه
            if ($modalBody.css('position') === 'static') {
                $modalBody.css('position', 'relative');
            }
        
            $modalBody.append($overlay);
        
            $.ajax({
                url: admin_payout_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_get_unpaid_commissions',
                    nonce: admin_payout_ajax.nonce,
                    consultant_id: consultantId
                },
                success: function(response) {
                    if (response.success) {
                        self.renderUnpaidCommissions(response.data);
                    }
                },
                complete: function() {
                    // حذف overlay بعد از اتمام Ajax
                    $overlay.remove();
                }
            });
        },


        renderUnpaidCommissions: function(data) {
            const self = this; // ✅ اضافه کن
            const $container = $('#unpaid-commissions');
            let html = '';
            let totalAmount = 0;

            if (data.commissions.length === 0) {
                html = '<p>هیچ کمیسیون پرداخت‌نشده‌ای یافت نشد</p>';
            } else {
                html = `
                    <table class="unpaid-commissions-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-commissions"></th>
                                <th>ID</th>
                                <th>درخواست</th>
                                <th>مبلغ پایه</th>
                                <th>کمیسیون نهایی</th>
                                <th>تاریخ تولید</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.commissions.forEach(commission => {
                    totalAmount += parseFloat(commission.final_commission);
                    html += `
                        <tr>
                            <td><input type="checkbox" name="commission_ids[]" value="${commission.id}" data-amount="${commission.final_commission}" checked></td>
                            <td>${commission.id}</td>
                            <td>#${commission.request_id}</td>
                            <td>${this.formatNumber(commission.base_amount)} تومان</td>
                            <td>${this.formatNumber(commission.final_commission)} تومان</td>
                            <td>${commission.generated_at}</td>
                        </tr>
                    `;
                });

                html += `
                        </tbody>
                    </table>
                    <div class="total-amount">
                        <strong>مجموع مبلغ انتخاب‌شده: ${this.formatNumber(totalAmount)} تومان</strong>
                    </div>
                `;
            }

            $container.html(html);
            $('#payout-amount').val(totalAmount);
            
            // select all functionality
            $('#select-all-commissions').on('change', function() {
                $('input[name="commission_ids[]"]').prop('checked', this.checked);
                self.calculateTotalAmount();
            });
            
            $('input[name="commission_ids[]"]').on('change', function() {
                self.calculateTotalAmount();
            });
        },

    calculateTotalAmount: function() {
        let total = 0;
        $('input[name="commission_ids[]"]:checked').each(function() {
            total += parseFloat($(this).data('amount')) || 0;
        });
        $('#payout-amount').val(total);
        $('.total-amount strong').text(`مجموع مبلغ انتخاب‌شده: ${this.formatNumber(total)} تومان`);
    },


        createPayout: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const commissionIds = $('input[name="commission_ids[]"]:checked').map(function() {
                return this.value;
            }).get();

            if (commissionIds.length === 0) {
                this.showMessage('لطفاً حداقل یک کمیسیون انتخاب کنید', 'warning');
                return;
            }

            formData.append('commission_ids', JSON.stringify(commissionIds));

            const self = this;
            
            $.ajax({
                url: admin_payout_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_create_payout',
                    nonce: admin_payout_ajax.nonce,
                    consultant_id: formData.get('consultant_id'),
                    amount: formData.get('amount'),
                    period_start: formData.get('period_start'),
                    period_end: formData.get('period_end'),
                    payment_method: formData.get('payment_method'),
                    reference_code: formData.get('reference_code'),
                    commission_ids: commissionIds
                },
                beforeSend: function() {
                    $('#create-payout-submit').prop('disabled', true).html(`
                        <div class="spinner"></div> در حال ایجاد تسویه...
                    `);
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage('تسویه با موفقیت ایجاد شد', 'success');
                        self.closeModals();
                        self.loadPayouts();
                     //   this.loadConsultantsWithPendingCommissions();
                    } else {
                        self.showMessage('خطا در ایجاد تسویه: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('خطا در ارتباط با سرور', 'error');
                },
                complete: function() {
                    $('#create-payout-submit').prop('disabled', false).html(`
                        <i class="fas fa-check"></i> ایجاد تسویه
                    `);
                }
            });
        },

        exportPayout: function(e) {
            const payoutId = $(e.currentTarget).data('payout-id');
            window.open(`${admin_payout_ajax.ajax_url}?action=admin_export_payout&payout_id=${payoutId}&nonce=${admin_payout_ajax.nonce}`);
        },

        deletePayout: function(e) {
            const payoutId = $(e.currentTarget).data('payout-id');
            
            if (!confirm('آیا از حذف این تسویه اطمینان دارید؟ این عمل قابل بازگشت نیست.')) {
                return;
            }

            const self = this;
            
            $.ajax({
                url: admin_payout_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'admin_delete_payout',
                    nonce: admin_payout_ajax.nonce,
                    payout_id: payoutId
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage('تسویه با موفقیت حذف شد', 'success');
                        self.loadPayouts();
                    } else {
                        self.showMessage('خطا در حذف تسویه: ' + response.data, 'error');
                    }
                },
                error: function() {
                    self.showMessage('خطا در ارتباط با سرور', 'error');
                }
            });
        },

        exportCSV: function() {
            const filterParams = $.param(this.currentFilters);
            window.open(`${admin_payout_ajax.ajax_url}?action=admin_export_payouts&${filterParams}&nonce=${admin_payout_ajax.nonce}`);
        },

        refreshData: function() {
            this.loadPayouts();
            this.showMessage('داده‌ها به روز شد', 'success');
        },

        closeModals: function() {
            $('.modal').fadeOut(300);
            $('#create-payout-form')[0].reset();
            $('#confirm-payment-form')[0].reset();
            $('#unpaid-commissions').html('<p>لطفاً ابتدا یک مشاور انتخاب کنید</p>');
        },

        showMessage: function(message, type = 'success') {
            const messageEl = $(`
                <div class="admin-payout-message ${type}">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    ${message}
                </div>
            `);
            
            $('body').append(messageEl);
            
            setTimeout(() => {
                messageEl.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        formatNumber: function(number) {
            return new Intl.NumberFormat('fa-IR').format(number);
        },

        getStatusText: function(status) {
            const statusMap = {
                'pending': 'در انتظار',
                'done': 'پرداخت شده',
                'failed': 'ناموفق'
            };
            return statusMap[status] || status;
        },

        getPaymentMethodText: function(method) {
            const methodMap = {
                'manual': 'دستی',
                'api': 'API',
                'bank_transfer': 'کارت به کارت'
            };
            return methodMap[method] || method;
        }
    };

    // Initialize
    PayoutManager.init();
});