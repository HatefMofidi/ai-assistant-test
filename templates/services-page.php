<?php
/**
 * Template Name: صفحه سرویس‌ها
 */

get_header();

$theme_assets = get_stylesheet_directory_uri();


?>

<div class="ai-container">
    <?php if (is_user_logged_in()): ?>
        <div class="ai-card ai-user-info">
            <h2><?php _e('داشبورد کاربری', 'ai-assistant'); ?></h2>



            
            
            <p><?php _e('موجودی شما:', 'ai-assistant'); ?> 
               <span id="user-wallet-credit">در حال بارگذاری...</span>
            </p>
            

            
            <a href="https://aidastyar.com/wallet-charge" class="ai-button">
                <?php _e('شارژ حساب', 'ai-assistant'); ?>
            </a>
            
            




        </div>
    <?php else: ?>
        <div class="ai-card ai-notice">
            <p><?php _e('برای استفاده از سرویس‌ها باید وارد حساب کاربری خود شوید.', 'ai-assistant'); ?></p>
            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ai-button">
                <?php _e('ورود به حساب کاربری', 'ai-assistant'); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="ai-services-grid">
        <?php 
        $services = AI_Assistant_Service_Manager::get_instance()->get_active_services();
        foreach ($services as $service_id => $service): 
            $service_url = add_query_arg('service', $service_id, home_url('/service/'));
            
            
        // گرفتن تعداد اجرا از پست‌های history
        $run_count = new WP_Query([
            'post_type'      => 'ai_service_history',
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => 'service_id',
                    'value'   => $service_id,
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'nopaging' => true,
        ]);
        $total_runs = $run_count->found_posts;
        
        ?>
        

        <a href="<?php echo esc_url(home_url('/service/' . $service_id . '/')); ?>" class="main-ai-service-card" target="_blank">
            <div class="main-ai-service-info">
                <h3><?php echo esc_html($service['name']); ?></h3>
                <p><?php echo esc_html($service['description']); ?></p>
            </div>
            <div class="main-ai-service-image" style="background-image: url('<?= $theme_assets ?>/assets/images/<?= $service_id ?>.jpg')"></div>
        </a>
        
       
            
          

        <?php endforeach; ?>
    </div>
</div>

<?php if (is_user_logged_in()): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_user_wallet_credit', {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        console.log(data);  // نمایش داده‌های دریافتی برای دیباگ
        if (data.success && !isNaN(data.data.credit)) {  // دسترسی به credit داخل data
            document.getElementById('user-wallet-credit').innerText = new Intl.NumberFormat('fa-IR').format(data.data.credit) + ' تومان';
        } else {
            document.getElementById('user-wallet-credit').innerText = 'خطا در دریافت موجودی';
        }
    })
    .catch(error => {
        console.error('Error:', error);  // چاپ خطا در کنسول در صورت بروز مشکل در ارتباط
        document.getElementById('user-wallet-credit').innerText = 'خطا در ارتباط';
    });
});

</script>
<?php endif; ?>


<?php


get_footer();