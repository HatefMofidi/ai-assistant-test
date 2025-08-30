<?php
/**
 * Template Name: صفحه سرویس‌ها
 */

get_header();

$theme_assets = get_stylesheet_directory_uri();


?>

<div class="ai-container">

    
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




<?php


get_footer();