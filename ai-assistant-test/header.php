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
                    
                      <!-- منوی دسکتاپ -->
                    <nav class="ai-nav ai-nav-desktop">
                        <?php if (is_user_logged_in()): ?>
                        
                            <a href="<?php echo home_url('/ai-dashboard'); ?>">
                                <div >  
                                    <?php echo get_avatar(get_current_user_id(), 32); ?>
                                    <span><?php echo wp_get_current_user()->display_name; ?></span>                                
                                </div> 
                            </a>
                            
                            <a href="<?php echo home_url('/ai-dashboard'); ?>">
                            داشبورد
                            </a>
                            
                            <a href="<?php echo home_url('/ai-services'); ?>">
                            سرویس ها
                            </a>
                            
                            <a href="<?php echo home_url('/service-history'); ?>">
                            تاریخچه
                            </a>
                        <?php else: ?>
                            <a href="<?php echo home_url('/ai-services'); ?>"><?php _e('سرویس‌ها', 'ai-assistant'); ?></a>
                        <?php endif; ?>
                        
                        
                        
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
                    </nav>
                    
                    
                    <!-- منوی موبایل -->
                    <nav class="menu-nav ai-nav-mobile">
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo home_url('/ai-dashboard'); ?>" class="menu-item-card">
                                <span class="dashicons dashicons-dashboard"></span>
                                داشبورد
                            </a>
                            
                            <a href="<?php echo home_url('/ai-services'); ?>" class="menu-item-card">
                                <span class="dashicons dashicons-admin-tools"></span>
                                سرویس ها
                            </a>
                            
                            <a href="<?php echo home_url('/page-user-history'); ?>" class="menu-item-card">
                                <span class="dashicons dashicons-backup"></span>
                                تاریخچه
                            </a>
                            
                            <!-- اضافه کردن لینک پروفایل در منوی اصلی -->
                            <a href="<?php echo home_url('/ai-dashboard'); ?>" class="menu-item-card user-profile-menu-item">
                                <span class="dashicons dashicons-admin-users"></span>
                                پروفایل کاربری
                            </a>
                            
                        <?php else: ?>
                            <a href="<?php echo home_url('/ai-services'); ?>" class="menu-item-card"><?php _e('سرویس‌ها', 'ai-assistant'); ?></a>
                        <?php endif; ?>
                                       
                    
                        <div>
                            <?php if (is_user_logged_in()): ?>
                                <div class="wallet-section">
                                    <span class="wallet-title">موجودی کیف پول شما</span>
                                    <div class="wallet-balance">
                                        <span class="amount"><?php echo number_format(AI_Assistant_Payment_Handler::get_instance()->get_user_credit(get_current_user_id())); ?></span>
                                        <span class="decimal">تومان</span>
                                    </div>
                                </div>
                                
                                <div class="menu-actions">
                                    <a href="<?php echo home_url('/wallet-charge'); ?>" class="btn btn-primary">
                                        <?php _e('شارژ کیف پول', 'ai-assistant'); ?>
                                    </a>
                                    
                                    <div class="bottom-actions">
                                        <a href="<?php echo wp_logout_url(); ?>" class="btn btn-secondary">
                                            <?php _e('خروج', 'ai-assistant'); ?>
                                        </a>
                                        
                                        <!-- نمایش خلاصه پروفایل کاربر به جای دکمه -->
                                        <div class="user-profile-summary">
                                            <?php echo get_avatar(get_current_user_id(), 40); ?>
                                            <div class="user-info">
                                                <span class="user-name"><?php echo wp_get_current_user()->display_name; ?></span>
                                                <span class="user-credit"><?php echo number_format(AI_Assistant_Payment_Handler::get_instance()->get_user_credit(get_current_user_id())); ?> تومان</span>
                                            </div>
                                        </div>
                                    </div>
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
                        
                    </nav>     
                </div>
                

                
            </div>
        </header>
    
    <main class="ai-main">