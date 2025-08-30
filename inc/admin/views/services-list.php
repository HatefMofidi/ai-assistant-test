
<div class="wrap">
    <h1>مدیریت سرویس‌های هوش مصنوعی</h1>
    
    <a href="<?php echo add_query_arg(['edit_service' => 'new']); ?>" class="page-title-action">سرویس جدید</a>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>آی دی سرویس</th>
                <th>نام سرویس</th>
                <th>قیمت (تومان)</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($service_manager->get_all_services() as $id => $service): ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($service['service_id']); ?></strong>
                </td>                
                <td>
                    <strong><?php echo esc_html($service['name']); ?></strong>
                </td>
                <td><?php echo number_format($service['price']); ?></td>
                <td>
                    <span class="service-status <?php echo $service['active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $service['active'] ? 'فعال' : 'غیرفعال'; ?>
                    </span>
                </td>
                <td>
                    <a href="<?php echo add_query_arg(['edit_service' => $id]); ?>" class="button">ویرایش</a>
                </td>
               
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    .service-status.active { color: green; }
    .service-status.inactive { color: red; }
</style>