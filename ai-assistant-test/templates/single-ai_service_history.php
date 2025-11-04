<?php
/**
 * Template Name: نمایش خروجی سرویس
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/templates/single-ai_service_history.php
 */

// غیرفعال کردن کش قبل از هر خروجی
if (!defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}

// غیرفعال کردن کش برای این صفحه
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");


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

// بررسی وجود درخواست مشاوره برای این تاریخچه
$consultation_request = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}diet_consultation_requests WHERE service_history_id = %d AND user_id = %d",
    $history_id,
    $user_id
));

$has_consultation_request = !is_null($consultation_request);
$is_approved = false;
$final_diet_data = null;

if ($has_consultation_request) {
    // بررسی وضعیت درخواست
    $status_badges = [
        'pending' => 'ai-status-pending',
        'under_review' => 'ai-status-review', 
        'approved' => 'ai-status-approved',
        'rejected' => 'ai-status-rejected'
    ];
    
    $status_texts = [
        'pending' => 'در انتظار بازبینی',
        'under_review' => 'در حال بازبینی',
        'approved' => 'تایید شده',
        'rejected' => 'نیاز به اصلاح'
    ];
    
    $current_status = $consultation_request->status;
    $is_approved = ($current_status === 'approved');
    

    
    if ($is_approved && !empty($consultation_request->final_diet_data)) {
        $final_diet_data = $consultation_request->final_diet_data;
    }
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

// اگر کاربر درخواست مشاوره دارد و وضعیت تایید شده است
if ($has_consultation_request && $is_approved && $final_diet_data) {
    // استفاده از رژیم نهایی تایید شده توسط مشاور
    $response_data = $final_diet_data;
    $source = 'final_diet_data';
} else if (!$has_consultation_request) {
    // استفاده از رژیم معمولی از جدول service_history
    $response_data = $item->response;
    $source = 'response';
}

// اگر response شامل JSON است
if ($response_data && is_string($response_data)) {
    $json_test = json_decode($response_data);
    if (json_last_error() === JSON_ERROR_NONE) {
        // آماده‌سازی داده‌ها
        $result_data = array(
            'response' => $response_data,
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
        
        <?php if ($has_consultation_request && !$is_approved): ?>
        <!-- نمایش وضعیت درخواست مشاوره -->
        <div class="consultation-status-notice" style="max-width: 800px; margin: 1rem auto; padding: 1rem; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; text-align: center;">
            <h3 style="color: #856404; margin-bottom: 0.5rem;">
                <i class="fas fa-clock"></i>
                درخواست مشاوره در حال بررسی
            </h3>
            <p style="color: #856404; margin: 0;">
                <?php
                $status_text = $status_texts[$current_status] ?? 'در انتظار بازبینی';
                echo "وضعیت درخواست شما: <strong>{$status_text}</strong>";
                ?>
            </p>
            <p style="color: #856404; margin: 0.5rem 0 0 0; font-size: 0.9rem;">
            </p>
        </div>
        <?php endif; ?>
        
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
                
                
   <?php if ($is_approved): ?>
   
        const container = document.getElementById('diet-plan-container');
        if (container) {
            const watermark = document.createElement('div');
            watermark.className = 'diet-approval-watermark';
            watermark.innerHTML = 'تایید شده توسط مشاور تغذیه';
            container.appendChild(watermark);
            
            // اضافه کردن استایل به واترمارک
            const style = document.createElement('style');
            style.textContent = `
                .diet-approval-watermark {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-30deg);
                    font-size: 4rem;
                    font-weight: bold;
                    color: rgba(76, 175, 80, 0.15);
                    z-index: 100;
                    pointer-events: none;
                    white-space: nowrap;
                    font-family: Arial, sans-serif;
                }
                #diet-plan-container {
                    position: relative;
                    overflow: hidden;
                }
                @media (max-width: 768px) {
                    .diet-approval-watermark {
                        font-size: 2.5rem;
                    }
                }
            `;
            document.head.appendChild(style);
        }
   
    <?php endif; ?>                
                
                
                
                
                
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

// نمایش وضعیت درخواست مشاوره اگر وجود دارد
// if ($has_consultation_request && !$is_approved) {
//     $status_text = $status_texts[$current_status] ?? 'در انتظار بازبینی';


$current_status = $item->status; // وضعیت فعلی
render_consultation_timeline_inline($current_status ,$has_consultation_request );



// }
function render_consultation_timeline_inline($current_status, $has_consultation_request = true) {

    // مراحل برای درخواست‌های با مشاوره
    $steps_with_consultation = [
        [
            'key' => 'queued',
            'title' => 'در صف انتظار',
            'desc'  => 'درخواست شما در صف انتظار پردازش قرار دارد',
            'icon'  => 'fas fa-clock',
        ],
        [
            'key' => 'processing',
            'title' => 'در حال پردازش',
            'desc'  => 'درخواست شما در حال پردازش است',
            'icon'  => 'fas fa-cogs',
        ],
        [
            'key' => 'completed',
            'title' => 'پردازش موفق',
            'desc'  => 'پردازش با موفقیت انجام شد',
            'icon'  => 'fas fa-check-circle',
        ],
        [
            'key' => 'consultant_queue',
            'title' => 'در صف مشاور',
            'desc'  => 'درخواست شما در کارتابل مشاور قرار گرفته است',
            'icon'  => 'fas fa-user-clock',
        ],
        [
            'key' => 'under_review',
            'title' => 'در حال بازبینی',
            'desc'  => 'مشاور تغذیه در حال بررسی درخواست شماست',
            'icon'  => 'fas fa-search',
        ],
        [
            'key' => 'draft',
            'title' => 'پیش‌نویس مشاور',
            'desc'  => 'مشاور در حال تهیه پیش‌نویس رژیم غذایی است',
            'icon'  => 'fas fa-edit',
        ],
        [
            'key' => 'approved',
            'title' => 'تایید نهایی',
            'desc'  => 'رژیم غذایی با موفقیت تایید و آماده ارائه است',
            'icon'  => 'fas fa-check-double',
        ],
        [
            'key' => 'error',
            'title' => 'خطا در پردازش',
            'desc'  => 'خطایی در پردازش رخ داده است',
            'icon'  => 'fas fa-exclamation-triangle',
        ],
    ];

    // مراحل برای درخواست‌های بدون مشاوره
    $steps_without_consultation = [
        [
            'key' => 'queued',
            'title' => 'در صف انتظار',
            'desc'  => 'درخواست شما در صف انتظار پردازش قرار دارد',
            'icon'  => 'fas fa-clock',
        ],
        [
            'key' => 'processing',
            'title' => 'در حال پردازش',
            'desc'  => 'درخواست شما در حال پردازش است',
            'icon'  => 'fas fa-cogs',
        ],
        [
            'key' => 'completed',
            'title' => 'پردازش موفق',
            'desc'  => 'پردازش با موفقیت انجام شد',
            'icon'  => 'fas fa-check-circle',
        ],
        [
            'key' => 'error',
            'title' => 'خطا در پردازش',
            'desc'  => 'خطایی در پردازش رخ داده است',
            'icon'  => 'fas fa-exclamation-triangle',
        ],
    ];

    // انتخاب مراحل بر اساس نوع درخواست
    $steps = $has_consultation_request ? $steps_with_consultation : $steps_without_consultation;

    // ترتیب مراحل برای نمایش در تایم‌لاین
    if ($has_consultation_request) {
        $status_order = [
            'queued', 
            'processing', 
            'completed', 
            'consultant_queue', 
            'under_review', 
            'draft', 
            'approved'
        ];
    } else {
        $status_order = [
            'queued', 
            'processing', 
            'completed'
        ];
    }
    
    // پیدا کردن ایندکس وضعیت جاری
    $current_index = array_search($current_status, $status_order);
    if ($current_index === false) $current_index = 0;

    echo '<div style="background:white; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:20px; max-width:500px; margin:2rem auto; font-family:\'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; direction:rtl;">';

    echo '<div style="text-align:center; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #eee;">';
    echo '<h3 style="color:#2c3e50; font-size:18px; margin-bottom:5px;"><i class="fas fa-clipboard-check"></i> وضعیت درخواست مشاوره</h3>';
    echo '<p style="color:#7f8c8d; font-size:13px;">پیشرفت درخواست شما در مراحل زیر نمایش داده شده است</p>';
    echo '</div>';

    echo '<div style="position:relative; padding:10px 0;">';

    foreach ($steps as $i => $step) {
        // تعیین وضعیت هر مرحله
        $is_done = $i < $current_index;
        $is_current = $i === $current_index;
        $is_next = $i > $current_index;
        
        // برای وضعیت error، فقط زمانی که وضعیت جاری error باشد، قرمز نمایش داده می‌شود
        $is_error_state = ($step['key'] === 'error') && ($current_status === 'error');

        // رنگ‌ها
        if ($is_error_state) {
            $color = '#dc3545';
            $badge_bg = '#f8d7da';
            $badge_color = '#721c24';
            $badge_text = 'خطا';
        } else {
            $color = $is_done ? '#28a745' : ($is_current ? '#17a2b8' : '#bdc3c7');
            $badge_bg = $is_done ? '#d4edda' : ($is_current ? '#d1ecf1' : '#f8f9fa');
            $badge_color = $is_done ? '#155724' : ($is_current ? '#0c5460' : '#6c757d');
            $badge_text = $is_done ? 'تکمیل شده' : ($is_current ? 'در حال انجام' : 'در انتظار');
        }

        echo '<div style="display:flex; align-items:center; margin-bottom:20px; position:relative;">';
        if ($i < count($steps) - 1) {
            echo '<div style="content:\'\'; position:absolute; right:15px; top:30px; bottom:-20px; width:2px; background-color:#e0e0e0; z-index:1;"></div>';
        }

        echo '<div style="width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-left:15px; font-size:14px; z-index:2; position:relative; flex-shrink:0; background-color:'.$color.'; color:white;">';
        echo '<i class="'.$step['icon'].'"></i>';
        echo '</div>';

        $border_color = $color;
        $bg_content = $is_error_state ? '#f8d7da' : (($is_done || $is_current) ? '#e8f5e9' : '#f8f9fa');
        $opacity = $is_next ? '0.6' : '1';
        echo '<div style="flex:1; padding:10px 12px; border-radius:8px; background-color:'.$bg_content.'; border-right:3px solid '.$border_color.'; box-shadow:0 2px 6px rgba(0,0,0,0.05); opacity:'.$opacity.';">';
        echo '<div style="font-weight:600; margin-bottom:3px; font-size:14px; display:flex; justify-content:space-between; align-items:center;">';
        echo '<span>'.$step['title'].'</span>';
        echo '<span style="display:inline-block; padding:3px 8px; border-radius:12px; font-size:10px; font-weight:600; background-color:'.$badge_bg.'; color:'.$badge_color.';">'.$badge_text.'</span>';
        echo '</div>';
        echo '<div style="color:#7f8c8d; font-size:12px; line-height:1.4;">'.$step['desc'].'</div>';
        echo '</div>';

        echo '</div>';
    }

    echo '</div>';

    echo '<div style="margin-top:20px; padding:10px; background-color:#f8f9fa; border-radius:6px; text-align:center; font-size:12px; color:#6c757d;">';
    echo '<i class="fas fa-info-circle"></i> برای اطلاعات بیشتر با پشتیبانی تماس بگیرید';
    echo '</div>';

    echo '</div>';
}



echo '<div class="service-content" style="background: #f9f9f9; padding: 1.5rem; border-radius: 5px; margin-top: 1rem;">';
echo apply_filters('the_content', $response_data);
echo '</div>';
echo '</div>';

get_footer();