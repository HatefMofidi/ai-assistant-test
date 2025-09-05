<?php
/**
 * Template Name: تاریخچه تراکنش‌های کیف پول
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// غیرفعال کردن کش برای این صفحه
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

get_header();

$current_user_id = get_current_user_id();
$wallet = AI_Assistant_Payment_Handler::get_instance();
$history = $wallet->get_transaction_history($current_user_id, 10, get_query_var('paged') ?: 1);
?>

<div class="ai-history-page">
    <div class="ai-history-header">
        <h1><span class="dashicons dashicons-money-alt"></span> تاریخچه تراکنش‌های کیف پول</h1>
        <div class="ai-history-actions">
            <a href="<?php echo esc_url(home_url('/ai-dashboard')); ?>" class="ai-back-button">
                <span class="dashicons dashicons-arrow-right-alt"></span> بازگشت به داشبورد
            </a>
        </div>
    </div>

    <div class="ai-history-container">
        <?php if (!empty($history['items'])) : ?>
            <div class="ai-history-table-responsive">
                <table class="ai-history-table">
                    <thead>
                        <tr>
                            <th>نوع تراکنش</th>
                            <th>تاریخ</th>
                            <th>مبلغ (تومان)</th>
                            <th>موجودی</th>
                            <th>توضیحات</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history['items'] as $item) : ?>
                            <tr data-history-id="<?php echo esc_attr($item->id); ?>">
                                <td>
                                    <?php if ($item->type == 'credit') : ?>
                                        <span class="ai-status-badge ai-status-success">واریز</span>
                                    <?php else : ?>
                                        <span class="ai-status-badge ai-status-warning">برداشت</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date_i18n('j F Y - H:i', strtotime($item->created_at)); ?></td>
                                <td>
                                    <?php if ($item->type == 'credit') : ?>
                                        <span style="color:green;">+<?php echo number_format($item->amount); ?></span>
                                    <?php else : ?>
                                        <span style="color:red;">-<?php echo number_format($item->amount); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($item->new_balance); ?></td>
                                <td><?php echo esc_html($item->description); ?></td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ai-history-pagination">
                <?php echo paginate_links([
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $history['pages'],
                    'prev_text' => __('&laquo; قبلی'),
                    'next_text' => __('بعدی &raquo;'),
                ]); ?>
            </div>
        <?php else : ?>
            <div class="ai-history-empty">
                <div class="ai-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <h3>تاریخچه کیف پول شما خالی است</h3>
                    <p>هنوز هیچ تراکنشی در کیف پول شما ثبت نشده است.</p>
                    <a href="<?php echo esc_url(home_url('/wallet-charge')); ?>" class="ai-button">
                        شارژ کیف پول
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// اسکریپت AJAX برای حذف
wp_enqueue_script('wallet-history-ajax', get_template_directory_uri() . '/assets/js/wallet-history-ajax.js', ['jquery'], null, true);
wp_localize_script('wallet-history-ajax', 'wallet_history_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('wallet_history_ajax_nonce'),
    'confirm_delete' => __('آیا از حذف این تراکنش مطمئن هستید؟', 'ai-assistant'),
]);

get_footer();