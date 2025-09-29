// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/discount-frontend-admin.js
jQuery(document).ready(function($) {
    let currentFilters = {
        status: 'all',
        type: 'all',
        search: ''
    };

    function toggleUserRestrictionSection() {
        const userRestriction = $('#discount-user-restriction').val();
        if (userRestriction === 'specific_users') {
            $('#specific-users-section').show();
            
            // فقط اگر لیست کاربران خالی است، بارگذاری کن
            if ($('#discount-specific-users option').length <= 1) {
                loadUsersList();
            }
        } else {
            $('#specific-users-section').hide();
        }
    }
    
    // توابع تبدیل اعداد
    function toPersianNumbers(text) {
        if (!text) return '';
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return text.toString().replace(/\d/g, function(match) {
            return persianNumbers[parseInt(match)];
        });
    }
    
    function toEnglishNumbers(text) {
        if (!text) return '';
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        
        let result = text.toString();
        
        // تبدیل اعداد فارسی
        persianNumbers.forEach((persianNum, index) => {
            const regex = new RegExp(persianNum, 'g');
            result = result.replace(regex, index.toString());
        });
        
        // تبدیل اعداد عربی
        arabicNumbers.forEach((arabicNum, index) => {
            const regex = new RegExp(arabicNum, 'g');
            result = result.replace(regex, index.toString());
        });
        
        return result;
    }
    
    // تابع برای نرمالایز کردن متن جستجو (تبدیل به انگلیسی و حذف فاصله)
    function normalizeSearchText(text) {
        if (!text) return '';
        
        // تبدیل به اعداد انگلیسی
        let normalized = toEnglishNumbers(text);
        
        // حذف فاصله و کاراکترهای خاص
        normalized = normalized.replace(/\s+/g, '');
        normalized = normalized.replace(/[-\/\\^$*+?.()|[\]{}]/g, '');
        
        return normalized.toLowerCase();
    }    
    
    function loadUsersList() {
        return new Promise(function(resolve, reject) {
            const usersListContainer = $('#users-checkbox-list');
            const loadingElement = $('#users-loading');
            
            // نشان دادن حالت بارگذاری
            loadingElement.show();
            usersListContainer.html('<div class="no-users-message">در حال بارگذاری کاربران...</div>');
    
            $.ajax({
                url: discountFrontendAdminVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_users_list',
                    nonce: discountFrontendAdminVars.nonce
                },
                success: function(response) {
                    console.log('پاسخ لیست کاربران:', response);
                    if (response.success) {
                        renderUsersCheckboxList(response.data.users);
                        bindUsersCheckboxEvents();
                        updateSelectedUsersCount();
                        resolve(response.data.users);
                    } else {
                        usersListContainer.html('<div class="no-users-message">خطا در بارگذاری کاربران</div>');
                        reject('خطا در بارگذاری لیست کاربران: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('خطای AJAX در بارگذاری کاربران:', error);
                    usersListContainer.html('<div class="no-users-message">خطا در ارتباط با سرور</div>');
                    reject('خطا در ارتباط با سرور');
                },
                complete: function() {
                    loadingElement.hide();
                }
            });
        });
    }
    
    // تابع برای رندر لیست چک‌باکس کاربران
    function renderUsersCheckboxList(users) {
        const usersListContainer = $('#users-checkbox-list');
        
        if (users.length === 0) {
            usersListContainer.html('<div class="no-users-message">هیچ کاربری یافت نشد</div>');
            return;
        }
        
        let html = '';
        users.forEach(function(user) {
            // تبدیل اعداد به فارسی برای نمایش
            const displayPhone = user.phone ? toPersianNumbers(user.phone) : 'بدون شماره';
            const displayName = user.full_name || user.name;
            
            html += `
                <div class="user-checkbox-item" data-user-id="${user.id}">
                    <div class="user-info">
                        <div class="user-name">${displayName}</div>
                        <div class="user-details">
                            <span class="user-phone">${displayPhone}</span>
                            <span class="user-email">${user.email}</span>
                        </div>
                    </div>
                    <input type="checkbox" name="specific_users[]" value="${user.id}" style="margin-left: 10px;">
                </div>
            `;
        });
        
        usersListContainer.html(html);
        
        // به‌روزرسانی شمارنده پس از رندر
        setTimeout(() => {
            updateUsersCounters(users.length);
        }, 100);
    }
    
    // تابع برای بایند کردن ایونت‌های چک‌باکس‌ها - نسخه اصلاح شده
    function bindUsersCheckboxEvents() {
        // کلیک روی آیتم کاربر
        $('.user-checkbox-item').on('click', function(e) {
            if (!$(e.target).is('input[type="checkbox"]')) {
                const checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked'));
                $(this).toggleClass('selected', checkbox.prop('checked'));
                updateUsersCounters();
            }
        });
        
        // تغییر وضعیت چک‌باکس
        $('input[name="specific_users[]"]').on('change', function() {
            $(this).closest('.user-checkbox-item').toggleClass('selected', $(this).prop('checked'));
            updateUsersCounters();
        });
        
        // جستجو در کاربران - نسخه نهایی
        $('#users-search').on('keyup', function() {
            const searchTerm = normalizeSearchText($(this).val());
            let visibleCount = 0;
            
            $('.user-checkbox-item').each(function() {
                const $item = $(this);
                const userName = normalizeSearchText($item.find('.user-name').text());
                const userPhone = normalizeSearchText($item.find('.user-phone').text());
                const userEmail = normalizeSearchText($item.find('.user-email').text());
                
                // جستجو در تمام فیلدها
                const isVisible = searchTerm === '' || 
                                userName.includes(searchTerm) || 
                                userPhone.includes(searchTerm) || 
                                userEmail.includes(searchTerm);
                
                $item.toggle(isVisible);
                
                if (isVisible) {
                    visibleCount++;
                }
            });
            
            // به‌روزرسانی شمارنده‌ها
            updateUsersCounters(visibleCount);
        });
        
        // انتخاب همه کاربران visible
        $('#select-all-users').on('click', function() {
            const searchTerm = $('#users-search').val();
            let targetUsers;
            
            if (searchTerm && searchTerm.length > 0) {
                // فقط کاربران visible را انتخاب کن
                targetUsers = $('.user-checkbox-item:visible');
            } else {
                // همه کاربران را انتخاب کن
                targetUsers = $('.user-checkbox-item');
            }
            
            targetUsers.find('input[type="checkbox"]').prop('checked', true);
            targetUsers.addClass('selected');
            updateUsersCounters();
        });
        
        // لغو انتخاب همه کاربران
        $('#deselect-all-users').on('click', function() {
            $('input[name="specific_users[]"]').prop('checked', false);
            $('.user-checkbox-item').removeClass('selected');
            updateUsersCounters();
        });
        
        // پاک کردن جستجو
        $('#users-search').on('search', function() {
            if ($(this).val() === '') {
                // نشان دادن همه کاربران وقتی جستجو پاک شد
                $('.user-checkbox-item').show();
                updateUsersCounters($('.user-checkbox-item').length);
            }
        });
    }
        
    // تابع برای به‌روزرسانی تمام شمارنده‌ها - نسخه نهایی
    function updateUsersCounters(visibleCount = null) {
        const totalCount = $('.user-checkbox-item').length;
        
        // اگر visibleCount مشخص نشده، محاسبه کن
        if (visibleCount === null) {
            visibleCount = $('.user-checkbox-item:visible').length;
        }
        
        const selectedCount = $('input[name="specific_users[]"]:checked').length;
        
        // مدیریت شمارنده visible
        const $visibleCounter = $('#visible-users-count');
        const searchTerm = $('#users-search').val();
        
        if (searchTerm && searchTerm.length > 0) {
            if (visibleCount === 0) {
                if ($visibleCounter.length === 0) {
                    $('#selected-users-count').before(`<div id="visible-users-count" class="no-results">هیچ کاربری مطابقت ندارد</div>`);
                } else {
                    $visibleCounter.text('هیچ کاربری مطابقت ندارد').addClass('no-results');
                }
            } else {
                if ($visibleCounter.length === 0) {
                    $('#selected-users-count').before(`<div id="visible-users-count">${toPersianNumbers(visibleCount)} کاربر از ${toPersianNumbers(totalCount)} کاربر نمایش داده می‌شود</div>`);
                } else {
                    $visibleCounter.text(`${toPersianNumbers(visibleCount)} کاربر از ${toPersianNumbers(totalCount)} کاربر نمایش داده می‌شود`).removeClass('no-results');
                }
            }
        } else {
            // اگر جستجو خالی است، شمارنده visible را پاک کن
            $visibleCounter.remove();
        }
        
        // به‌روزرسانی شمارنده انتخاب‌ها
        let countText = '';
        if (visibleCount !== totalCount && searchTerm && searchTerm.length > 0) {
            countText = `${toPersianNumbers(selectedCount)} کاربر انتخاب شده است (از ${toPersianNumbers(visibleCount)} کاربر نمایش داده شده)`;
        } else {
            countText = `${toPersianNumbers(selectedCount)} کاربر از ${toPersianNumbers(totalCount)} کاربر انتخاب شده است`;
        }
        
        $('#selected-users-count').text(countText);
    }

    // تابع برای به‌روزرسانی تعداد کاربران visible
    function updateVisibleUsersCount() {
        const visibleCount = $('.user-checkbox-item:visible').length;
        const totalCount = $('.user-checkbox-item').length;
        
        if (visibleCount !== totalCount) {
            $('#selected-users-count').before(`<div id="visible-users-count" style="font-size: 11px; color: #718096; margin-bottom: 5px;">${visibleCount} کاربر از ${totalCount} کاربر نمایش داده می‌شود</div>`);
        } else {
            $('#visible-users-count').remove();
        }
    }
    
    // تابع برای به‌روزرسانی تعداد کاربران انتخاب شده
    function updateSelectedUsersCount() {
        const selectedCount = $('input[name="specific_users[]"]:checked').length;
        const totalCount = $('input[name="specific_users[]"]').length;
        const visibleCount = $('.user-checkbox-item:visible').length;
        
        let countText = `${toPersianNumbers(selectedCount)} کاربر از ${toPersianNumbers(totalCount)} کاربر انتخاب شده است`;
        
        // اگر جستجو فعال است، تعداد visible را هم نشان بده
        if (visibleCount !== totalCount) {
            countText = `${toPersianNumbers(selectedCount)} کاربر از ${toPersianNumbers(visibleCount)} کاربر نمایش داده شده انتخاب شده است (از ${toPersianNumbers(totalCount)} کاربر کل)`;
        }
        
        $('#selected-users-count').text(countText);
    }
    
    // اضافه کردن event listener برای تغییر محدودیت کاربر
    $('#discount-user-restriction').on('change', function() {
        toggleUserRestrictionSection();
    });
    
    // در تابع toggleScopeSections، این خط را اضافه کنید:
    function toggleScopeSections() {
        const scope = $('#discount-scope').val();
        
        // مخفی کردن همه بخش‌ها
        $('#services-section, #user-restriction-section, #occasional-section, #jalali-date-section').hide();
        
        // نمایش بخش‌های مربوطه
        if (scope === 'service') {
            $('#services-section').show();
        } else if (scope === 'user_based') {
            $('#user-restriction-section').show();
        } else if (scope === 'occasional') {
            $('#occasional-section').show();
            toggleJalaliDateSection();
        }
        
        // اضافه کردن این خط در انتها
        toggleUserRestrictionSection();
    }
    
    // بارگذاری اولیه لیست تخفیف‌ها
    loadDiscounts();

    // مدیریت ایجاد تخفیف جدید
    $('#create-discount-btn').on('click', function() {
        resetDiscountForm();
        $('#discount-modal-title').text('ایجاد کد تخفیف جدید');
        $('#discount-modal').show();
    });

    // بستن modal
    $('.discount-close-modal').on('click', function() {
        $('#discount-modal, #discount-details-modal').hide();
    });

    // تولید کد تخفیف تصادفی
    $('#generate-code').on('click', function() {
        const code = generateRandomCode();
        $('#discount-code').val(code);
    });

    // تغییر حوزه اعتبار
    $('#discount-scope').on('change', function() {
        toggleScopeSections();
    });

    // جستجو
    $('#search-discounts').on('click', function() {
        currentFilters.search = $('#discount-search').val();
        loadDiscounts();
    });

    $('#discount-search').on('keypress', function(e) {
        if (e.which === 13) {
            currentFilters.search = $(this).val();
            loadDiscounts();
        }
    });

    // فیلتر وضعیت
    $('#discount-status-filter').on('change', function() {
        currentFilters.status = $(this).val();
        loadDiscounts();
    });

    // فیلتر نوع
    $('#discount-type-filter').on('change', function() {
        currentFilters.type = $(this).val();
        loadDiscounts();
    });

    // ثبت فرم تخفیف
    $('#discount-form').on('submit', function(e) {
        e.preventDefault();
        saveDiscount();
    });

    // انصراف از ایجاد/ویرایش
    $('#cancel-discount').on('click', function() {
        $('#discount-modal').hide();
    });

    function loadDiscounts() {
        const listElement = $('#discounts-list');
        listElement.html('<div class="discounts-loading">' + discountFrontendAdminVars.i18n.loading + '</div>');

        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_discounts_list',
                ...currentFilters,
                nonce: discountFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    listElement.html(response.data.html);
                    updateStats(response.data.stats);
                    bindDiscountActions();
                } else {
                    listElement.html('<div class="discount-error">خطا در بارگذاری کدهای تخفیف</div>');
                }
            },
            error: function() {
                listElement.html('<div class="discount-error">خطا در ارتباط با سرور</div>');
            }
        });
    }

    function updateStats(stats) {
        $('#active-count').text(stats.active);
        $('#inactive-count').text(stats.inactive);
        $('#total-count').text(stats.total);
    }

    function bindDiscountActions() {
        // مشاهده جزئیات
        $('.discount-view-details').on('click', function() {
            const discountId = $(this).data('discount-id');
            showDiscountDetails(discountId);
        });

        // ویرایش تخفیف
        $('.discount-edit').on('click', function() {
            const discountId = $(this).data('discount-id');
            editDiscount(discountId);
        });

        // تغییر وضعیت
        $('.discount-toggle-status').on('click', function() {
            const discountId = $(this).data('discount-id');
            const action = $(this).find('.fa-play').length ? 'فعال' : 'غیرفعال';
            
            if (!confirm(discountFrontendAdminVars.i18n.confirm_deactivate)) {
                return;
            }
            
            toggleDiscountStatus(discountId);
        });

        // حذف تخفیف
        $('.discount-delete').on('click', function() {
            if (!confirm(discountFrontendAdminVars.i18n.confirm_delete)) {
                return;
            }
            
            const discountId = $(this).data('discount-id');
            deleteDiscount(discountId);
        });

        // کپی کردن کد
        $('.discount-copy-code').on('click', function() {
            const code = $(this).data('code');
            copyToClipboard(code);
        });
    }

    function generateRandomCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return code;
    }

    // اضافه کردن مدیریت بخش تاریخ شمسی
    function toggleScopeSections() {
        const scope = $('#discount-scope').val();
        
        // مخفی کردن همه بخش‌ها
        $('#services-section, #user-restriction-section, #occasional-section, #jalali-date-section').hide();
        
        // نمایش بخش‌های مربوطه
        if (scope === 'service') {
            $('#services-section').show();
        } else if (scope === 'user_based') {
            $('#user-restriction-section').show();
        } else if (scope === 'occasional') {
            $('#occasional-section').show();
            toggleJalaliDateSection();
        }
    }
    
    // نمایش/مخفی کردن بخش تاریخ شمسی
    function toggleJalaliDateSection() {
        const isAnnual = $('#discount-is-annual').val() === '1';
        if (isAnnual) {
            $('#jalali-date-section').show();
        } else {
            $('#jalali-date-section').hide();
        }
    }
    
    // اضافه کردن event listener برای تغییر وضعیت سالانه
    $('#discount-is-annual').on('change', function() {
        toggleJalaliDateSection();
    });
    
    // در تابع resetDiscountForm بخش تاریخ شمسی را نیز ریست کنید
    function resetDiscountForm() {
        $('#discount-form')[0].reset();
        $('#discount-id').val('');
        $('input[name="services[]"]').prop('checked', false);
        $('#discount-occasion-name').val('');
        $('#discount-is-annual').val('0');
        $('#discount-jalali-month').val('');
        $('#discount-jalali-day').val('');
        toggleScopeSections();
        
        $('input[name="specific_users[]"]').prop('checked', false);
        $('.user-checkbox-item').removeClass('selected');
        $('#users-search').val('');
        updateSelectedUsersCount();    
        
        // ریست کردن بخش کاربران
        $('input[name="specific_users[]"]').prop('checked', false);
        $('.user-checkbox-item').removeClass('selected');
        $('#users-search').val('');
        
        // نشان دادن همه کاربران
        $('.user-checkbox-item').show();
        
        // به‌روزرسانی شمارنده
        const totalCount = $('.user-checkbox-item').length;
        updateUsersCounters(totalCount);        
    }

    function saveDiscount() {
        const formData = new FormData($('#discount-form')[0]);
        formData.append('action', $('#discount-id').val() ? 'update_discount_code' : 'create_discount_code');
        formData.append('nonce', discountFrontendAdminVars.nonce);
    
        const submitBtn = $('#discount-form button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...');
    
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // اصلاح این خط - دسترسی به response.data.message
                    showMessage(response.data.message || 'عملیات با موفقیت انجام شد', 'success');
                    $('#discount-modal').hide();
                    loadDiscounts();
                } else {
                    // اصلاح این خط - دسترسی به response.data
                    showMessage(response.data || discountFrontendAdminVars.i18n.error, 'error');
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    function showDiscountDetails(discountId) {
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_discount_details',
                discount_id: discountId,
                nonce: discountFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#discount-details-content').html(response.data.html);
                    $('#discount-details-modal').show();
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
            }
        });
    }
    
    function editDiscount(discountId) {
        console.log('شروع ویرایش تخفیف با ID:', discountId);
        
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_discount_details',
                discount_id: discountId,
                nonce: discountFrontendAdminVars.nonce,
                for_edit: true
            },
            success: function(response) {
                console.log('پاسخ سرور برای ویرایش:', response);
                if (response.success) {
                    fillEditForm(response.data.discount);
                    $('#discount-modal-title').text('ویرایش کد تخفیف');
                    $('#discount-modal').show();
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('خطا در دریافت اطلاعات تخفیف:', error);
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
            }
        });
    }
    
    function fillEditForm(discount) {
        console.log('پر کردن فرم با داده‌های تخفیف:', discount);
        
        // پر کردن فرم با داده‌های تخفیف
        $('#discount-id').val(discount.id);
        $('#discount-name').val(discount.name);
        $('#discount-code').val(discount.code);
        $('#discount-type').val(discount.type);
        $('#discount-amount').val(discount.amount);
        $('#discount-scope').val(discount.scope);
        $('#discount-usage-limit').val(discount.usage_limit);
        $('#discount-user-restriction').val(discount.user_restriction || '');
        
        // تاریخ‌ها
        if (discount.start_date) {
            $('#discount-start-date').val(discount.start_date.replace(' ', 'T').substr(0, 16));
        }
        if (discount.end_date) {
            $('#discount-end-date').val(discount.end_date.replace(' ', 'T').substr(0, 16));
        }
        
        // سرویس‌های مرتبط
        $('input[name="services[]"]').prop('checked', false);
        
        if (discount.scope === 'service' && discount.services && discount.services.length > 0) {
            discount.services.forEach(function(serviceId) {
                $('input[name="services[]"][value="' + serviceId + '"]').prop('checked', true);
            });
        }
        
        // نمایش بخش‌های مربوطه
        toggleScopeSections();
        
        if (discount.scope === 'user_based' && discount.user_restriction === 'specific_users') {
            console.log('کاربران تخفیف:', discount.users);
            
            // ابتدا مطمئن شویم بخش کاربران نمایش داده شده است
            $('#user-restriction-section').show();
            $('#specific-users-section').show();
            
            // بارگذاری لیست کاربران و سپس انتخاب کاربران مرتبط
            loadUsersList().then(function(users) {
                if (discount.users && discount.users.length > 0) {
                    // پاک کردن انتخاب‌های قبلی
                    $('input[name="specific_users[]"]').prop('checked', false);
                    $('.user-checkbox-item').removeClass('selected');
                    
                    // انتخاب کاربران
                    discount.users.forEach(function(user) {
                        // اگر user یک آبجکت است، از user.id استفاده کن، در غیر این صورت از خود user
                        var userId = (typeof user === 'object' && user.id) ? user.id : user;
                        $(`input[name="specific_users[]"][value="${userId}"]`).prop('checked', true);
                        $(`input[name="specific_users[]"][value="${userId}"]`).closest('.user-checkbox-item').addClass('selected');
                    });
                    
                    updateSelectedUsersCount();
                    console.log('کاربران انتخاب شده:', discount.users.length);
                }
            }).catch(function(error) {
                console.error('خطا در بارگذاری لیست کاربران:', error);
            });
        }
    }

    function toggleDiscountStatus(discountId) {
        const button = $(`.discount-toggle-status[data-discount-id="${discountId}"]`);
        const originalText = button.html();
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ...');
    
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_discount_status',
                discount_id: discountId,
                nonce: discountFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    // اصلاح این خط
                    showMessage(response.data.message || 'وضعیت با موفقیت تغییر کرد', 'success');
                    loadDiscounts();
                } else {
                    // اصلاح این خط
                    showMessage(response.data || discountFrontendAdminVars.i18n.error, 'error');
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
                button.prop('disabled', false).html(originalText);
            }
        });
    }

    function deleteDiscount(discountId) {
        const discountItem = $(`.discount-item[data-discount-id="${discountId}"]`);
        
        $.ajax({
            url: discountFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_discount_code',
                discount_id: discountId,
                nonce: discountFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    // اصلاح این خط
                    showMessage(response.data.message || 'کد تخفیف حذف شد', 'success');
                    discountItem.fadeOut(300, function() {
                        $(this).remove();
                        loadDiscounts();
                    });
                } else {
                    // اصلاح این خط
                    showMessage(response.data || discountFrontendAdminVars.i18n.error, 'error');
                }
            },
            error: function() {
                showMessage(discountFrontendAdminVars.i18n.error, 'error');
            }
        });
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            showMessage(discountFrontendAdminVars.i18n.copy_success, 'success');
        }, function() {
            // Fallback برای مرورگرهای قدیمی
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showMessage(discountFrontendAdminVars.i18n.copy_success, 'success');
        });
    }

    function showMessage(message, type) {
        // حذف پیام قبلی اگر وجود دارد
        $('.discount-admin-message').remove();
        
        const messageClass = `discount-admin-message ${type}`;
        const messageHtml = `<div class="${messageClass}">${message}</div>`;
        
        $('.discount-admin-panel-header').after(messageHtml);
        
        // حذف خودکار پیام بعد از 5 ثانیه
        setTimeout(function() {
            $('.discount-admin-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // بستن modal با کلیک خارج از آن
    $(window).on('click', function(event) {
        if ($(event.target).is('.discount-modal')) {
            $('.discount-modal').hide();
        }
    });
});