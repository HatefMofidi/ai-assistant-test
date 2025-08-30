
<div class="wrap">
    <h1>ویرایش سرویس</h1>
    
    <a href="<?php echo remove_query_arg('edit_service'); ?>" class="page-title-action">بازگشت به لیست</a>
    
    <form method="post" class="ai-service-form">
        <?php wp_nonce_field('ai_service_nonce'); ?>
        

        <div class="form-group">
            <label>آی دی سرویس</label>
            <input type="text" name="service_id" value="<?php echo esc_attr($service_id); ?> " 
                <?php if($service_id !== 'new' && !empty($service_id)): ?>
                    readonly style="background-color: #f5f5f5; cursor: not-allowed;"
                <?php endif; ?> required>
        </div>

        
        <div class="form-group">
            <label>نام سرویس</label>
            <input type="text" name="service_data[name]" value="<?php echo esc_attr($service['name']); ?>" required>
        </div>

        <div class="form-group">
            <label>قیمت (تومان)</label>
            <input type="number" name="service_data[price]" value="<?php echo esc_attr($service['price']); ?>" required>
        </div>

        <div class="form-group">
            <label>آیکون (کلاس Dashicons)</label>
            <input type="text" name="service_data[icon]" value="<?php echo esc_attr($service['icon']); ?>">
        </div>

        <div class="form-group">
            <label>توضیحات</label>
            <textarea name="service_data[description]"><?php echo esc_textarea($service['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>ساختار ثابت</label>
            <?php
            /* <textarea name="service_data[system_prompt]"><?php echo esc_textarea($service['system_prompt'] ?? ''); ?></textarea> */
            ?>
            <textarea name="service_data[system_prompt]"><?php echo $service['system_prompt'] ?? ''; ?></textarea>

        </div>        

        <div class="form-group">
            <label>
                <input type="checkbox" name="service_data[active]" value="1" <?php checked($service['active'] ?? true); ?>>
                سرویس فعال
            </label>
        </div>

        <input type="submit" name="submit_service" value="ذخیره تغییرات" class="button button-primary">
    </form>
</div>