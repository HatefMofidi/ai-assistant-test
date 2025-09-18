<?php
// /inc/admin/views/discounts-edit.php

$discount_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$discount = $discount_id ? AI_Assistant_Discount_DB::get_instance()->get_discount($discount_id) : null;
$service_manager = AI_Assistant_Service_Manager::get_instance();
$services = $service_manager->get_all_services();
$users = get_users();
?>

<h1><?php echo $discount_id ? 'ویرایش تخفیف' : 'افزودن تخفیف جدید'; ?></h1>

<form method="post" action="" class="discount-form">
    <?php wp_nonce_field('ai_assistant_discount_nonce', 'ai_discount_nonce'); ?>
    
    <input type="hidden" name="action" value="<?php echo $discount_id ? 'edit_discount' : 'add_discount'; ?>">
    <?php if ($discount_id): ?>
        <input type="hidden" name="discount_id" value="<?php echo $discount_id; ?>">
    <?php endif; ?>
    
    <div class="discount-form-section">
        <h2>اطلاعات اصلی</h2>
        
        <div class="discount-form-row">
            <label for="discount_name">نام تخفیف</label>
            <input type="text" id="discount_name" name="discount_name" value="<?php echo $discount ? esc_attr($discount->name) : ''; ?>" required>
        </div>
        
        <div class="discount-form-row">
            <label for="discount_type">نوع تخفیف</label>
            <select id="discount_type" name="discount_type" required>
                <option value="percentage" <?php echo $discount && $discount->amount_type == 'percentage' ? 'selected' : ''; ?>>درصدی</option>
                <option value="fixed" <?php echo $discount && $discount->amount_type == 'fixed' ? 'selected' : ''; ?>>مبلغ ثابت</option>
            </select>
        </div>
        
        <div class="discount-form-row">
            <label for="discount_value">مقدار تخفیف</label>
            <input type="number" id="discount_value" name="discount_value" value="<?php echo $discount ? esc_attr($discount->amount) : ''; ?>" step="1" min="0" required>
            <span class="discount-value-suffix"><?php echo $discount && $discount->amount_type == 'percentage' ? '%' : 'تومان'; ?></span>
        </div>
        
        <div class="discount-form-row">
            <label for="scope">دامنه اعمال</label>
            <select id="scope" name="scope" required>
                <option value="global" <?php echo $discount && $discount->scope == 'global' ? 'selected' : ''; ?>>تخفیف عمومی (روی همه سرویس‌ها)</option>
                <option value="service" <?php echo $discount && $discount->scope == 'service' ? 'selected' : ''; ?>>تخفیف روی سرویس‌های خاص</option>
                <option value="coupon" <?php echo $discount && $discount->scope == 'coupon' ? 'selected' : ''; ?>>کد تخفیف</option>
                <option value="user_based" <?php echo $discount && $discount->scope == 'user_based' ? 'selected' : ''; ?>>تخفیف کاربرمحور</option>
            </select>
        </div>
        
        <div class="discount-form-row" id="coupon_code_row" style="display: none;">
            <label for="coupon_code">کد تخفیف</label>
            <input type="text" id="coupon_code" name="coupon_code" value="<?php echo $discount ? esc_attr($discount->code) : ''; ?>">
            <p class="description">کد منحصربه‌فردی که کاربران باید وارد کنند</p>
        </div>
        
        <div class="discount-form-row" id="user_restriction_row" style="display: none;">
            <label for="user_restriction">محدودیت کاربری</label>
            <select id="user_restriction" name="user_restriction">
                <option value="first_time" <?php echo $discount && $discount->user_restriction == 'first_time' ? 'selected' : ''; ?>>فقط اولین استفاده کاربر</option>
                <option value="specific_users" <?php echo $discount && $discount->user_restriction == 'specific_users' ? 'selected' : ''; ?>>کاربران خاص</option>
            </select>
        </div>
    </div>
    
    <div class="discount-form-section" id="service_section" style="display: none;">
        <h2>سرویس‌های موردنظر</h2>
        <div class="discount-form-row">
            <select id="service_ids" name="service_ids[]" multiple>
                <?php foreach ($services as $service_id => $service): ?>
                    <?php $selected = $discount && in_array($service_id, $discount->services) ? 'selected' : ''; ?>
                    <option value="<?php echo esc_attr($service_id); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html($service['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">برای انتخاب چند سرویس، کلید Ctrl را نگه دارید</p>
        </div>
    </div>
    
    <div class="discount-form-section" id="user_section" style="display: none;">
        <h2>کاربران موردنظر</h2>
        <div class="discount-form-row">
            <div class="user-search-container">
                <input type="text" id="user_search" placeholder="جستجوی کاربران..." class="regular-text">
                <button type="button" id="user_search_btn" class="button">جستجو</button>
                <button type="button" id="select_all_users" class="button">انتخاب همه</button>
                <button type="button" id="deselect_all_users" class="button">عدم انتخاب همه</button>
            </div>
            
            <div class="users-checkbox-container" id="users_checkbox_container">
                <div class="users-loading" style="display: none;">در حال بارگذاری...</div>
                <div class="users-list">
                    <?php 
                    $limited_users = array_slice($users, 0, 1000);
                    foreach ($limited_users as $user): 
                        $checked = $discount && in_array($user->ID, $discount->users) ? 'checked' : '';
                    ?>
                        <label class="user-checkbox-label">
                            <input type="checkbox" name="user_ids[]" value="<?php echo esc_attr($user->ID); ?>" <?php echo $checked; ?>>
                            <?php echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')'; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <p class="description">می‌توانید با تایپ در کادر جستجو، کاربران را فیلتر کنید</p>
        </div>
    </div>
    
    <div class="discount-form-section">
        <h2>تنظیمات زمانی و محدودیت‌ها</h2>
        
        <div class="discount-form-row">
            <label for="start_date">تاریخ شروع</label>
            <input type="datetime-local" id="start_date" name="start_date" value="<?php echo $discount && $discount->start_date ? date('Y-m-d\TH:i', strtotime($discount->start_date)) : ''; ?>">
            <p class="description">خالی = بلافاصله فعال شود</p>
        </div>
        
        <div class="discount-form-row">
            <label for="end_date">تاریخ پایان</label>
            <input type="datetime-local" id="end_date" name="end_date" value="<?php echo $discount && $discount->end_date ? date('Y-m-d\TH:i', strtotime($discount->end_date)) : ''; ?>">
            <p class="description">خالی = بدون تاریخ انقضا</p>
        </div>
        
        <div class="discount-form-row">
            <label for="usage_limit">محدودیت تعداد استفاده</label>
            <input type="number" id="usage_limit" name="usage_limit" value="<?php echo $discount ? esc_attr($discount->usage_limit) : '0'; ?>" min="0">
            <p class="description">0 = بدون محدودیت</p>
        </div>
        
        <div class="discount-form-row">
            <label for="min_order_amount">حداقل مبلغ سفارش</label>
            <input type="number" id="min_order_amount" name="min_order_amount" value="<?php echo $discount ? esc_attr($discount->min_order_amount) : '0'; ?>" step="10000" min="0"> تومان
        </div>
        
        <div class="discount-form-row">
            <label for="active">وضعیت</label>
            <input type="checkbox" id="active" name="active" value="1" <?php echo $discount ? checked($discount->active, 1, false) : 'checked'; ?>> فعال
        </div>
    </div>
    
    <div class="discount-form-actions">
        <button type="submit" class="button button-primary">ذخیره تغییرات</button>
        <a href="<?php echo admin_url('admin.php?page=ai-assistant-discounts'); ?>" class="button">انصراف</a>
    </div>
</form>