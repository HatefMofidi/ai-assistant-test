// /assets/js/admin/discount-admin.js

jQuery(document).ready(function($) {
    // تابع برای نمایش/پنهان کردن فیلدهای مرتبط
    function toggleDiscountFields() {
        var scope = $('#scope').val();
        var discountType = $('#discount_type').val();
        
        // پنهان کردن همه بخش‌ها
        $('#coupon_code_row, #user_restriction_row, #service_section, #user_section').hide();
        
        // نمایش فیلدهای بر اساس scope
        if (scope === 'coupon') {
            $('#coupon_code_row').show();
        } else if (scope === 'user_based') {
            $('#user_restriction_row').show();
            var userRestriction = $('#user_restriction').val();
            $('#user_section').toggle(userRestriction === 'specific_users');
        }
        
        // نمایش بخش سرویس‌ها برای scopeهای مربوطه
        if (scope === 'service' || scope === 'coupon' || scope === 'user_based') {
            $('#service_section').show();
        }
        
        // تغییر واحد مقدار تخفیف
        $('.discount-value-suffix').text(discountType === 'percentage' ? '%' : 'تومان');
    }
    
    // تغییرات در selectها
    $('#scope, #discount_type, #user_restriction').change(toggleDiscountFields);
    
    // اجرای اولیه
    toggleDiscountFields();
    
    // اعتبارسنجی فرم
    $('.discount-form').on('submit', function(e) {
        var scope = $('#scope').val();
        var discountValue = $('#discount_value').val();
        
        // اعتبارسنجی مقدار تخفیف
        if (discountValue <= 0) {
            alert('مقدار تخفیف باید بیشتر از صفر باشد.');
            e.preventDefault();
            return false;
        }
        
        // اعتبارسنجی کد تخفیف
        if (scope === 'coupon') {
            var couponCode = $('#coupon_code').val();
            if (!couponCode || couponCode.trim() === '') {
                alert('برای تخفیف نوع "کد تخفیف" باید یک کد وارد کنید.');
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    });
    
    
    // مدیریت جستجوی کاربران
    $('#user_search').on('keyup', function() {
        searchUsers($(this).val());
    });
    
    $('#user_search_btn').on('click', function() {
        searchUsers($('#user_search').val());
    });
    
    // highlight کاربران انتخاب شده
    function updateSelectedUsers() {
        $('.user-checkbox-label').each(function() {
            var $label = $(this);
            var $checkbox = $label.find('input[type="checkbox"]');
            if ($checkbox.is(':checked')) {
                $label.addClass('selected');
            } else {
                $label.removeClass('selected');
            }
        });
    }
    
    // ابتدا وضعیت انتخاب‌ها را به روز کنیم
    updateSelectedUsers();
    
    // هنگام تغییر چک‌بکس‌ها
    $(document).on('change', '.user-checkbox-label input[type="checkbox"]', function() {
        updateSelectedUsers();
    });
    
    // تابع جستجوی کاربران
    function searchUsers(searchTerm) {
        if (!searchTerm) {
            // اگر جستجو خالی است، همه کاربران را نشان بده
            $('.user-checkbox-label').show();
            return;
        }
        
        var searchLower = searchTerm.toLowerCase();
        
        $('.user-checkbox-label').each(function() {
            var $label = $(this);
            var text = $label.text().toLowerCase();
            
            if (text.includes(searchLower)) {
                $label.show();
                
                // highlight متن پیدا شده
                var html = $label.html();
                var regex = new RegExp('(' + searchTerm + ')', 'gi');
                html = html.replace(regex, '<span class="search-highlight">$1</span>');
                $label.html(html);
            } else {
                $label.hide();
            }
        });
    }
    
    // بارگذاری بیشتر کاربران با اسکرول
    var usersPage = 1;
    var isLoading = false;
    
    $('#users_checkbox_container').on('scroll', function() {
        var $container = $(this);
        if (isLoading) return;
        
        if ($container.scrollTop() + $container.innerHeight() >= $container[0].scrollHeight - 100) {
            loadMoreUsers();
        }
    });
    
    function loadMoreUsers() {
        if (isLoading) return;
        
        isLoading = true;
        $('.users-loading').show();
        
        var searchTerm = $('#user_search').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'load_more_discount_users',
                page: usersPage + 1,
                search: searchTerm,
                security: discountAdmin.nonce // از localized script استفاده می‌کنیم
            },
            success: function(response) {
                if (response.success) {
                    usersPage++;
                    $('#users_checkbox_container .users-list').append(response.data);
                    updateSelectedUsers();
                }
            },
            complete: function() {
                isLoading = false;
                $('.users-loading').hide();
            }
        });
    }
});