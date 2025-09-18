<?php
// /inc/admin/views/discounts-list.php

$discounts = AI_Assistant_Discount_DB::get_instance()->get_all_discounts();
?>

<h1 class="wp-heading-inline">مدیریت تخفیف‌ها</h1>
<a href="<?php echo admin_url('admin.php?page=ai-assistant-discounts-add'); ?>" class="page-title-action">افزودن جدید</a>
<hr class="wp-header-end">

<?php if (isset($_GET['message'])): ?>
    <div class="notice notice-<?php echo $_GET['message'] === 'deleted' ? 'success' : 'error'; ?> is-dismissible">
        <p><?php echo $_GET['message'] === 'deleted' ? 'تخفیف با موفقیت حذف شد.' : 'خطا در حذف تخفیف.'; ?></p>
    </div>
<?php endif; ?>

<?php if (empty($discounts)): ?>
    <div class="discount-empty-state">
        <p>هیچ تخفیفی تعریف نشده است.</p>
        <a href="<?php echo admin_url('admin.php?page=ai-assistant-discounts-add'); ?>" class="button button-primary">افزودن اولین تخفیف</a>
    </div>
<?php else: ?>
    <table class="wp-list-table widefat fixed striped discount-table">
        <thead>
            <tr>
                <th>نام</th>
                <th>نوع</th>
                <th>مقدار</th>
                <th>دامنه</th>
                <th>تاریخ شروع</th>
                <th>تاریخ پایان</th>
                <th>استفاده شده</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($discounts as $discount): ?>
                <?php
                $scope_labels = [
                    'global' => 'عمومی',
                    'service' => 'سرویس خاص',
                    'coupon' => 'کد: ' . ($discount->code ?: 'ندارد'),
                    'user_based' => 'کاربرمحور'
                ];
                ?>
                <tr>
                    <td><?php echo esc_html($discount->name); ?></td>
                    <td><?php echo $discount->amount_type == 'percentage' ? 'درصدی' : 'مبلغ ثابت'; ?></td>
                    <td><?php echo number_format($discount->amount); ?><?php echo $discount->amount_type == 'percentage' ? '%' : ' تومان'; ?></td>
                    <td><?php echo isset($scope_labels[$discount->scope]) ? $scope_labels[$discount->scope] : $discount->scope; ?></td>
                    <td><?php echo $discount->start_date ? date('Y-m-d H:i', strtotime($discount->start_date)) : '-'; ?></td>
                    <td><?php echo $discount->end_date ? date('Y-m-d H:i', strtotime($discount->end_date)) : '-'; ?></td>
                    <td><?php echo $discount->usage_count . ($discount->usage_limit > 0 ? ' / ' . $discount->usage_limit : ''); ?></td>
                    <td>
                        <span class="discount-status discount-status-<?php echo $discount->active ? 'active' : 'inactive'; ?>">
                            <?php echo $discount->active ? 'فعال' : 'غیرفعال'; ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=ai-assistant-discounts&action=edit&id=' . $discount->id); ?>" class="button button-small">ویرایش</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ai-assistant-discounts&action=delete&id=' . $discount->id), 'delete_discount_' . $discount->id); ?>" 
                           class="button button-small button-link-delete" 
                           onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>