<?php
/*
Template Name: صفحه برنامه غذایی
*/

get_header();
?>

<!-- بارگذاری فونت آیکون -->
<!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">-->
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/fonts/webfonts/all.min.css">
<!-- بارگذاری استایل‌ها -->
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/services/diet-plan.css">

<div id="diet-plan-container">
    <!-- محتوا توسط JavaScript پر خواهد شد -->
</div>

<!-- بارگذاری اسکریپت -->
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/services/diet/diet-plan.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // بارگذاری برنامه غذایی از sessionStorage
    loadDietPlanFromSession('diet-plan-container', {
        backButtonUrl: '<?php echo home_url(); ?>',
        backButtonCallback: function() {
            window.location.href = '<?php echo home_url(); ?>';
        }
    });
});
</script>

<?php
get_footer();