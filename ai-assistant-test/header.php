<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
    
    <script>
        var home_url = '<?php echo home_url(); ?>';
    </script>   
    
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
                    
                    
                    <div class=" ai-nav ai-nav-desktop ai-nav-desktop-menu">
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo home_url('/ai-dashboard'); ?>" class="">
                                
                                داشبورد
                            </a>
                            
                            <a href="<?php echo home_url('/ai-services'); ?>" class="">
                               
                                سرویس ها
                            </a>
                            
                            <a href="<?php echo home_url('/page-user-history'); ?>" class="">
                                
                                تاریخچه
                            </a>
                            
                            <!-- اضافه کردن لینک پروفایل در منوی اصلی -->
                            <a href="<?php echo home_url('/profile'); ?>" class=" user-profile-menu-item">
                                
                                پروفایل کاربری
                            </a>
                            
                            <?php if ( current_user_can('administrator') ): ?>
                                <a href="<?php echo home_url('/management-comments'); ?>" class="">
                                    مدیریت کامنت ها
                                </a>
                                
                                
                                <a href="<?php echo home_url('/management-discounts/'); ?>" class="">
                                    مدیریت تخفیف ها
                                </a>                                 
                                
                                
                            <?php endif; ?>                            
                            
                        <?php else: ?>
                            <a href="<?php echo home_url('/ai-services'); ?>" class="">
                               
                                سرویس‌ها
                            </a>
                            
                            <a href="<?php echo home_url('/blog'); ?>" class="">
                                
                                وبلاگ
                            </a>
                            
                            <a href="<?php echo home_url('/about-us'); ?>" class="">
                                
                                درباره ما
                            </a>
                        <?php endif; ?>                            
                            
                    </div>        
                    
                      <!-- منوی دسکتاپ -->
                    <nav class="ai-nav ai-nav-desktop">



                        
                        
                        
                        <div class="ai-user-section">
                            <?php if (is_user_logged_in()): ?>
                                    <div class="bottom-actions">

                                        
                                        <div class="user-profile-summary">
                                            <?php echo get_avatar(get_current_user_id(), 40); ?>
                                            <div class="user-info">
                                                <span class="user-name"><?php echo wp_get_current_user()->display_name; ?></span>
                                                <span class="user-credit"><?php echo number_format(AI_Assistant_Payment_Handler::get_instance()->get_user_credit(get_current_user_id())); ?> تومان</span>
                                            </div>
                                            
                                            <a href="<?php echo home_url('/wallet-charge'); ?>" class="charge-wallet-btn" title="شارژ کیف پول">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                                    <circle cx="12" cy="14" r="2"></circle>
                                                </svg>
                                            </a>
  
                                            
                                            <div class="user-actions">
                                                <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-btn" title="خروج">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M15 21h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4"></path>
                                                        <polyline points="8,7 3,12 8,17"></polyline>
                                                        <line x1="3" y1="12" x2="15" y2="12"></line>
                                                    </svg>
                                                </a>
                                            </div>

                                        </div>
                                    </div>
                                

                                
    
                            <?php else: ?>
                                <a href="<?php echo wp_login_url(); ?>" class="ai-login-button">
                                    <?php _e('ورود/ثبت نام', 'ai-assistant'); ?>
                                </a>

                            <?php endif; ?>
                        </div> 
                        

                    </nav>
                    
                    
                   
                    
                    
                    <!-- منوی موبایل -->
                    <nav class="menu-nav ai-nav-mobile">
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo home_url('/ai-dashboard'); ?>" class="menu-item-card <?php if (is_page('ai-dashboard')) echo 'menu-active'; ?>"> 
                                <span class="dashicons dashicons-dashboard"></span>
                                داشبورد
                            </a>
                            
                            <a href="<?php echo home_url('/ai-services'); ?>" class="menu-item-card <?php if (is_page('ai-services')) echo 'menu-active'; ?>">
                                <span class="dashicons dashicons-admin-tools"></span>
                                سرویس ها
                            </a>
                            
                            <a href="<?php echo home_url('/page-user-history'); ?>" class="menu-item-card <?php if (is_page('page-user-history')) echo 'menu-active'; ?>">
                                <span class="dashicons dashicons-backup"></span>
                                تاریخچه
                            </a>
                            
                            
                            <a href="<?php echo home_url('/profile'); ?>" class="menu-item-card <?php if ( untrailingslashit($_SERVER['REQUEST_URI']) == '/profile' ) echo 'menu-active'; ?>">
                                <span class="dashicons dashicons-admin-users"></span>
                                پروفایل کاربری
                            </a>
                            
                            <!-- فقط برای ادمین‌ها -->
                            <?php if ( current_user_can('administrator') ) : ?>
                                <a href="<?php echo home_url('/management-comments/'); ?>" class="menu-item-card <?php if (is_page('management-comments')) echo 'menu-active'; ?>">
                                    <span class="dashicons dashicons-admin-comments"></span>
                                    مدیریت کامنت ها
                                </a>
                                
                                <a href="<?php echo home_url('/management-discounts/'); ?>" class="menu-item-card <?php if (is_page('management-discounts')) echo 'menu-active'; ?>">
                                    <span class="dashicons dashicons-tickets"></span>
                                    مدیریت تخفیف ها
                                </a>                                
                                
                            <?php endif; ?>                           
                            
                        <?php else: ?>
                            <a href="<?php echo home_url('/ai-services'); ?>" class="menu-item-card">
                                <span class="dashicons dashicons-admin-tools"></span>
                                سرویس‌ها
                            </a>
                            
                            <a href="<?php echo home_url('/blog'); ?>" class="menu-item-card">
                                <span class="dashicons dashicons-welcome-write-blog"></span>
                                وبلاگ
                            </a>
                            
                            <a href="<?php echo home_url('/about-us'); ?>" class="menu-item-card">
                                <span class="dashicons dashicons-info"></span>
                                درباره ما
                            </a>
                        <?php endif; ?>
                                       
                    
                        <div>
                            <?php if (is_user_logged_in()): ?>

                                
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
                                <div class="guest-actions">
                                    <div class="auth-buttons-container">
                                        <a href="<?php echo wp_login_url(); ?>" class="btn auth-btn login-btn">
                                            <span class="dashicons dashicons-admin-users"></span>
                                            ورود / ثبت نام
                                        </a>
                                    </div>
 
                                </div>
                            <?php endif; ?>
                        </div>
                    
                    </nav>  
                    
                    
                    
                </div>
                

                
            </div>
            

            
        </header>
    
    <main class="ai-main">