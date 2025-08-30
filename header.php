<!--/home/aidastya/public_html/wp-content/themes/ai-assistant/header.php-->
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('ai-assistant-body'); ?>>
        <header class="ai-header glass-div">
            <div class="ai-header-container">
                <div class="ai-logo-nav">
                    <h1 class="ai-logo">
                        <a href="<?php echo home_url('/'); ?>">
                            <?php _e('دستیار هوش مصنوعی', 'ai-assistant'); ?>
                        </a>
                    </h1>
                    
                    <!-- دکمه منوی موبایل -->
                    <button class="ai-mobile-menu-toggle">
                        <span class="dashicons dashicons-menu"></span>
                    </button>
                </div>
                
                
                <div class="ai-nav-wrapper">
                    
                     
                    <nav class="ai-nav">
                        <?php if (is_user_logged_in()): ?>
                        
                             <a href="<?php echo home_url('/ai-dashboard'); ?>">
                                <div >  
                                    <?php echo get_avatar(get_current_user_id(), 32); ?>
                                    <span><?php echo wp_get_current_user()->display_name; ?></span>                                
                                </div> 
                            </a>
                            
                            <a href="<?php echo home_url('/ai-dashboard'); ?>">
                            <span class="dashicons dashicons-dashboard"></span>
                            داشبورد
                            </a>
                            
                            <a href="<?php echo home_url('/ai-services'); ?>">
                            <span class="dashicons dashicons-admin-tools"></span>
                            سرویس ها
                            </a>
                            
                            <a href="<?php echo home_url('/service-history'); ?>">
                            <span class="dashicons dashicons-backup"></span>
                            تاریخچه
                            </a>
                        <?php else: ?>
                            <a href="<?php echo home_url('/ai-services'); ?>"><?php _e('سرویس‌ها', 'ai-assistant'); ?></a>
                        <?php endif; ?>
                    </nav>
                    
                    <div class="ai-user-section">
                        <?php if (is_user_logged_in()): ?>
                            <div class="ai-wallet-info">
                                <span class="ai-wallet-amount">
                                    <?php echo number_format(AI_Assistant_Payment_Handler::get_instance()->get_user_credit(get_current_user_id())); ?>
                                    <?php _e('تومان', 'ai-assistant'); ?>
                                </span>
                                <a href="<?php echo home_url('/wallet-charge'); ?>" class="ai-wallet-button">
                                    <?php _e('شارژ', 'ai-assistant'); ?>
                                </a>
                            </div>
                            
                            <div class="ai-user-profile">

                                <a href="<?php echo wp_logout_url(); ?>" class="ai-logout-button left-profile ai-button">
                                    <?php _e('خروج', 'ai-assistant'); ?>
                                </a>                                
                            </div>
                            

                        <?php else: ?>
                            <a href="<?php echo wp_login_url(); ?>" class="ai-login-button">
                                <?php _e('ورود', 'ai-assistant'); ?>
                            </a>
                            <a href="<?php echo wp_registration_url(); ?>" class="ai-register-button">
                                <?php _e('ثبت نام', 'ai-assistant'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                

                
            </div>
        </header>
    
    <main class="ai-main">