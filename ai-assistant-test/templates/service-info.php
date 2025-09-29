<?php
/**
 * Template Name: صفحه اطلاعات سرویس
 * Template Post Type: page
 */

// بررسی اینکه آیا مستقیماً از وردپرس فراخوانی شده
if (!defined('ABSPATH')) {
    // اگر مستقیم فراخوانی شده، وردپرس را بارگذاری کن
    $wp_path = preg_replace('/wp-content.*$/', '', __DIR__);
    require_once $wp_path . 'wp-load.php';
}

get_header();
$theme_assets = get_stylesheet_directory_uri();

// دریافت اطلاعات سرویس از پارامتر URL
$service_id = get_query_var('service_id');
if (empty($service_id)) {
    // اگر service_id از طریق rewrite rule نیامد، از پارامتر GET بگیر
    $service_id = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
}

$services = AI_Assistant_Service_Manager::get_instance()->get_active_services();
$service = isset($services[$service_id]) ? $services[$service_id] : null;

if (!$service) {
    // اگر سرویس یافت نشد، به صفحه 404 هدایت شود
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit();
}

$full_description = $service['full_description'] ?? '
    <h3>درباره این سرویس</h3>
    <p>این سرویس با استفاده از هوش مصنوعی پیشرفته، بهترین و متناسب‌ترین راهکار را برای شما ارائه می‌دهد. با تحلیل اطلاعات ورودی و تطبیق آن با استانداردهای روز، خروجی دقیق و کاربردی تولید می‌کند.</p>
    <p>مزایای استفاده از این سرویس شامل صرفه‌جویی در زمان، دقت بالا، قابلیت شخصی‌سازی، پشتیبانی از به‌روزرسانی و امکان استفاده نامحدود می‌باشد. شما می‌توانید بارها از این سرویس استفاده کنید و هر بار نتیجه‌ای متناسب با نیاز خود دریافت نمایید.</p>
';

// گرفتن اطلاعات قیمت و تخفیف (فرضی)
$service_price = isset($service['price']) ? $service['price'] : 50000;
// $service_discount = isset($service['discount']) ? $service['discount'] : 0;
$service_discount = 20;
$final_price = $service_discount > 0 ? $service_price * (1 - $service_discount/100) : $service_price;

// گرفتن نظرات سرویس
$comments_db = AI_Assistant_Comments_DB::get_instance();
$service_comments = $comments_db->get_comments($service_id, 'approved', 5);
$average_rating = $comments_db->get_average_rating($service_id);
$average_rating = $average_rating ? round($average_rating, 1) : 0;
$total_comments = 20;
?>

<div class="ai-container service_info-container">
    <!-- مسیر ناوبری -->
    <div class="service_info-breadcrumb">
        <a href="<?php echo home_url(); ?>">خانه</a> / 
        <a href="<?php echo home_url('/ai-services/'); ?>">سرویس‌ها</a> / 
        <span><?php echo esc_html($service['name']); ?></span>
    </div>

    <!-- بخش اصلی اطلاعات سرویس -->
    <div class="service_info-main">
        <div class="service_info-image-section">
            <div class="service_info-image" style="background-image: url('<?= $theme_assets ?>/assets/images/<?= $service_id ?>.jpg')">
                <?php if ($service_discount > 0): ?>
                <div class="service_info-discount-badge">
                    <?php echo esc_html($service_discount); ?>% تخفیف
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="service_info-details">
            <h1 class="service_info-title"><?php echo esc_html($service['name']); ?></h1>
            
            <div class="service_info-rating">
                <div class="service_info-stars">
                    <?php
                    $full_stars = floor($average_rating);
                    $has_half_star = ($average_rating - $full_stars) >= 0.5;
                    
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars) {
                            echo '<span class="service_info-star full">★</span>';
                        } elseif ($has_half_star && $i == $full_stars + 1) {
                            echo '<span class="service_info-star half">★</span>';
                        } else {
                            echo '<span class="service_info-star">☆</span>';
                        }
                    }
                    ?>
                </div>
                <span class="service_info-rating-value">(<?php echo esc_html($average_rating); ?> از ۵)</span>
                <span class="service_info-review-count"><?php echo esc_html($total_comments); ?> نظر</span>
            </div>
            
            <div class="service_info-price-section">
                <?php if ($service_discount > 0): ?>
                <div class="service_info-original-price"><?php echo number_format($service_price); ?> تومان</div>
                <?php endif; ?>
                <div class="service_info-final-price"><?php echo number_format($final_price); ?> تومان</div>
            </div>
            
            <div class="service_info-action">
                <a href="<?php echo esc_url(home_url('/service/' . $service_id . '/')); ?>" class="ai-button service_info-use-btn">استفاده از سرویس</a>
            </div>
                        
            <div class="service_info-description">
                <?php echo wp_kses_post($full_description); ?>
            </div>
        </div>
    </div>

    <!-- بخش نظرات کاربران -->
    <div class="service_info-comments-section">
        <h2 class="service_info-comments-title">نظرات کاربران</h2>
        
        <?php if ($service_comments): ?>
        <div class="service_info-comments-list">
            <?php foreach ($service_comments as $comment): ?>
            <div class="service_info-comment-item">
                <div class="service_info-comment-header">
                    <div class="service_info-comment-author">کاربر <?php echo esc_html(substr($comment->user_id, 0, 4)); ?></div>
                    <div class="service_info-comment-rating">
                        <?php 
                        $rating = intval($comment->rating);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<span class="service_info-comment-star">★</span>';
                            } else {
                                echo '<span class="service_info-comment-star">☆</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="service_info-comment-content">
                    <p><?php echo esc_html($comment->comment_text); ?></p>
                </div>
                <div class="service_info-comment-date">
                    <?php echo date_i18n('j F Y', strtotime($comment->created_at)); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="service_info-no-comments">
            <p>هنوز نظری برای این سرویس ثبت نشده است.</p>
        </div>
        <?php endif; ?>
        
        <!-- دکمه مشاهده نظرات بیشتر -->
        <?php if ($total_comments > 5): ?>
        <div class="service_info-more-comments">
            <a href="#" class="ai-button service_info-more-btn">مشاهده همه نظرات</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* استایل‌های صفحه اطلاعات سرویس */
.service_info-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.service_info-breadcrumb {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

.service_info-breadcrumb a {
    color: var(--primary-color);
    text-decoration: none;
}

.service_info-breadcrumb a:hover {
    text-decoration: underline;
}

.service_info-breadcrumb span {
    color: #333;
    font-weight: 500;
}

.service_info-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
    background: var(--white);
    border-radius: 8px;
    padding: 20px;
    box-shadow: var(--box-shadow);
}

.service_info-image-section {
    position: relative;
}

.service_info-image {
    width: 100%;
    height: 400px;
    background-size: cover;
    background-position: center;
    border-radius: 8px;
    position: relative;
}

.service_info-discount-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: var(--error-color);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 14px;
}

.service_info-details {
    display: flex;
    flex-direction: column;
}

.service_info-title {
    font-size: 24px;
    margin: 0 0 15px 0;
    color: var(--primary-color);
}

.service_info-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.service_info-stars {
    display: flex;
    direction: ltr;
}

.service_info-star {
    color: #ddd;
    font-size: 20px;
}

.service_info-star.full {
    color: #ffc107;
}

.service_info-star.half {
    background: linear-gradient(90deg, #ffc107 50%, #ddd 50%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.service_info-rating-value {
    font-size: 14px;
    color: #666;
}

.service_info-review-count {
    font-size: 14px;
    color: var(--primary-color);
    cursor: pointer;
}

.service_info-price-section {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.service_info-original-price {
    font-size: 16px;
    color: #999;
    text-decoration: line-through;
    margin-bottom: 5px;
}

.service_info-final-price {
    font-size: 24px;
    font-weight: bold;
    color: var(--success-color);
}

.service_info-action {
    margin-bottom: 25px;
}

.service_info-use-btn {
    display: block;
    text-align: center;
    padding: 15px;
    font-size: 16px;
    font-weight: bold;
}

.service_info-description h3 {
    font-size: 18px;
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.service_info-description p {
    line-height: 1.8;
    color: #555;
    margin-bottom: 15px;
    text-align: justify;
}

.service_info-comments-section {
    background: var(--white);
    border-radius: 8px;
    padding: 20px;
    box-shadow: var(--box-shadow);
    margin-bottom: 30px;
}

.service_info-comments-title {
    font-size: 20px;
    margin: 0 0 20px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.service_info-comments-list {
    margin-bottom: 20px;
}

.service_info-comment-item {
    border-bottom: 1px solid #f0f0f0;
    padding: 15px 0;
}

.service_info-comment-item:last-child {
    border-bottom: none;
}

.service_info-comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.service_info-comment-author {
    font-weight: bold;
    color: #333;
}

.service_info-comment-rating {
    direction: ltr;
}

.service_info-comment-star {
    color: #ffc107;
}

.service_info-comment-content p {
    margin: 0;
    line-height: 1.6;
    color: #555;
}

.service_info-comment-date {
    font-size: 12px;
    color: #999;
    margin-top: 10px;
}

.service_info-no-comments {
    text-align: center;
    padding: 30px;
    color: #999;
}

.service_info-more-comments {
    text-align: center;
    margin-top: 20px;
}

.service_info-more-btn {
    padding: 10px 25px;
}

/* رسپانسیو برای موبایل */
@media (max-width: 768px) {
    .service_info-main {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 15px;
    }
    
    .service_info-image {
        height: 250px;
    }
    
    .service_info-title {
        font-size: 20px;
    }
    
    .service_info-rating {
        flex-wrap: wrap;
    }
    
    .service_info-final-price {
        font-size: 20px;
    }
    
    .service_info-comment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<?php
get_footer();