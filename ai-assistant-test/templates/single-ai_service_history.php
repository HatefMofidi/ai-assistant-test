<?php
/**
 * Template Name: نمایش خروجی سرویس
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/single-ai_service_history.php
 */

get_header('service');

// دریافت ID از URL
$history_id = get_query_var('history_id');
$history_manager = AI_Assistant_History_Manager::get_instance();
$user_id = get_current_user_id();

// دریافت اطلاعات کاربر با متا داده‌ها
function get_complete_user_data($user_id) {
    $user_data = get_userdata($user_id);
    
    if ($user_data) {
        return array(
            'ID' => $user_data->ID,
            'username' => $user_data->user_login,
            'email' => $user_data->user_email,
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name' => get_user_meta($user_id, 'last_name', true),
            'phone' => get_user_meta($user_id, 'phone_number', true),
            'address' => get_user_meta($user_id, 'user_address', true)
        );
    }
    
    return false;
} 

// بررسی وجود آیتم و مالکیت
if (!$history_id || !$history_manager->is_user_owner($history_id, $user_id)) {
    status_header(404);
    get_template_part(404);
    exit;
}

// دریافت اطلاعات از جدول
global $wpdb;
$table_name = $wpdb->prefix . 'service_history';
$item = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE id = %d",
    $history_id
));

if (!$item) {
    status_header(404);
    get_template_part(404);
    exit;
}

// تنظیم title صفحه
add_filter('document_title_parts', function($title) use ($item) {
    $title['title'] = $item->service_name ?: 'خروجی سرویس';
    return $title;
});

// بارگذاری فونت آیکون و استایل‌ها
wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
wp_enqueue_style('diet-plan-css', get_template_directory_uri() . '/assets/css/services/diet-plan.css');

// بارگذاری اسکریپت کامپوننت
wp_enqueue_script('diet-plan-js', get_template_directory_uri() . '/assets/js/services/diet/diet-plan.js', array(), null, true);

// اگر response شامل JSON است
if ($item->response && is_string($item->response)) {
    $json_test = json_decode($item->response);
    if (json_last_error() === JSON_ERROR_NONE) {
        // آماده‌سازی داده‌ها
        $result_data = array(
            'response' => $item->response,
            'remaining_credit' => 0
        );
        
        // دریافت اطلاعات کاربر
        $user_info = get_complete_user_data($user_id);
        $user_name = $user_info && (!empty($user_info['first_name']) || !empty($user_info['last_name'])) 
            ? trim($user_info['first_name'] . ' ' . $user_info['last_name']) 
            : '';
        
        // تاریخ ایجاد به فرمت فارسی
        $created_date = date_i18n('j F Y - H:i', strtotime($item->created_at));
        ?>
        
        <div id="diet-plan-container"></div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // داده‌های ما
            const resultData = <?php echo json_encode($result_data); ?>;
            let finalData = resultData;
            
            // اگر response وجود دارد، آن را پردازش کن
            if (resultData.response) {
                try {
                    finalData = JSON.parse(resultData.response);
                } catch (e) {
                    console.error('Error parsing response data:', e);
                }
            }
            
            // ایجاد کامپوننت
            const renderer = new DietPlanRenderer({
                containerId: 'diet-plan-container',
                showHeader: true,
                showActions: true,
                showBackButton: true,
                backButtonUrl: '<?php echo home_url(); ?>',
                backButtonCallback: function() {
                    window.location.href = '<?php echo home_url(); ?>';
                }
            });
            
            // افزودن اطلاعات کاربر و تاریخ به داده‌ها
            if (!finalData.userInfo) {
                finalData.userInfo = {
                    name: '<?php echo esc_js($user_name); ?>',
                    date: '<?php echo esc_js($created_date); ?>'
                };
            }
            
            // رندر برنامه غذایی
            renderer.render(finalData);
            
            // افزودن ردیف اطلاعات کاربر پس از رندر
            setTimeout(() => {
                const header = document.querySelector('.diet-plan-container .header');
                if (header && finalData.userInfo) {
                    const userDateRow = document.createElement('div');
                    userDateRow.className = 'user-date-row';
                    userDateRow.innerHTML = `
                        <span class="report-user-info">
                            <i class="fas fa-user"></i>
                            ${finalData.userInfo.name || 'کاربر'}
                        </span>
                        
                        <span class="date-info">
                            <i class="fas fa-calendar-alt"></i>
                            ${finalData.userInfo.date || ''}
                        </span>
                    `;
                    
                    // اضافه کردن بعد از عنوان
                    const title = header.querySelector('h1');
                    if (title) {
                        title.insertAdjacentElement('afterend', userDateRow);
                    }
                }
            }, 100);
        });
        </script>
        
        <?php
        get_footer();
        exit;
    }
}

// اگر JSON نبود، نمایش معمولی
echo '<div class="ai-service-output-container" style="max-width: 800px; margin: 2rem auto; padding: 1rem;">';
echo '<h1 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">' . esc_html($item->service_name) . '</h1>';
echo '<div class="service-content" style="background: #f9f9f9; padding: 1.5rem; border-radius: 5px; margin-top: 1rem;">';
echo apply_filters('the_content', $item->response);
echo '</div>';
echo '</div>';

get_footer();