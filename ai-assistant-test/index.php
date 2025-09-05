<?php
/**
 * /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/index.php
 * The main template file
 */

get_header();
?>

<div class="ai-container">
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();
            the_content();
        endwhile;
    endif;
    ?>
</div>

<?php
get_footer();