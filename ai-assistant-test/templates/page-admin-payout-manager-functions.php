<?php
// AJAX Handlers برای مدیریت تسویه ادمین

// فقط برای ادمین‌ها
function admin_payout_ajax_auth() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'admin_payout_nonce')) {
        wp_die('Nonce نامعتبر');
    }
}

// دریافت لیست تسویه‌ها
add_action('wp_ajax_admin_get_payouts', 'admin_get_payouts_handler');
function admin_get_payouts_handler() {
    admin_payout_ajax_auth();
    
    $page = intval($_POST['page'] ?? 1);
    $per_page = intval($_POST['per_page'] ?? 20);
    $filters = $_POST['filters'] ?? [];
    
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $data = $consultation_db->get_all_payouts($filters, $page, $per_page);
    
    wp_send_json_success($data);
}

// دریافت جزئیات تسویه
add_action('wp_ajax_admin_get_payout_details', 'admin_get_payout_details_handler');
function admin_get_payout_details_handler() {
    admin_payout_ajax_auth();
    
    $payout_id = intval($_POST['payout_id']);
    
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $data = $consultation_db->get_payout_details($payout_id);
    
    if ($data) {
        wp_send_json_success($data);
    } else {
        wp_send_json_error('تسویه یافت نشد');
    }
}

// تأیید پرداخت
add_action('wp_ajax_admin_mark_payout_done', 'admin_mark_payout_done_handler');
function admin_mark_payout_done_handler() {
    admin_payout_ajax_auth();
    
    $payout_id = intval($_POST['payout_id']);
    $reference_code = sanitize_text_field($_POST['reference_code']);
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $result = $consultation_db->mark_payout_as_done($payout_id, $reference_code);
    
     error_log("Email sent for payout ID: " . $payout_id);      
            
    if ($result) {
        
        error_log("Email sent for payout ID: " . $payout_id);

        // ارسال نوتیفیکیشن به مشاور
        admin_send_payout_notification($payout_id);
        wp_send_json_success('پرداخت با موفقیت تأیید شد');
    } else {
        wp_send_json_error('خطا در تأیید پرداخت');
    }
}

// ایجاد تسویه جدید
add_action('wp_ajax_admin_create_payout', 'admin_create_payout_handler');
function admin_create_payout_handler() {
    admin_payout_ajax_auth();
    
    $data = [
        'consultant_id' => intval($_POST['consultant_id']),
        'amount' => floatval($_POST['amount']),
        'period_start' => sanitize_text_field($_POST['period_start']),
        'period_end' => sanitize_text_field($_POST['period_end']),
        'payment_method' => sanitize_text_field($_POST['payment_method']),
        'reference_code' => sanitize_text_field($_POST['reference_code']),
        'commission_ids' => $_POST['commission_ids'] ?? []
    ];
    
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $payout_id = $consultation_db->create_payout($data);
    
    if ($payout_id) {
        // علامت‌گذاری به عنوان پرداخت شده
        $consultation_db->mark_payout_as_done($payout_id, $data['reference_code']);
        admin_send_payout_notification($payout_id);
        wp_send_json_success(['payout_id' => $payout_id]);
    } else {
        wp_send_json_error('خطا در ایجاد تسویه');
    }
}

// دریافت کمیسیون‌های پرداخت نشده
add_action('wp_ajax_admin_get_unpaid_commissions', 'admin_get_unpaid_commissions_handler');
function admin_get_unpaid_commissions_handler() {
    admin_payout_ajax_auth();
    
    $consultant_id = intval($_POST['consultant_id']);
    
//$consultant_id =13;

    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $commissions = $consultation_db->get_unpaid_commissions($consultant_id);
    
    wp_send_json_success(['commissions' => $commissions]);
}

// دریافت لیست مشاوران
add_action('wp_ajax_admin_get_consultants', 'admin_get_consultants_handler');
function admin_get_consultants_handler() {
    admin_payout_ajax_auth();
    
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $consultants = $consultation_db->get_consultants_list();
    
    wp_send_json_success($consultants);
}

// حذف تسویه
add_action('wp_ajax_admin_delete_payout', 'admin_delete_payout_handler');
function admin_delete_payout_handler() {
    admin_payout_ajax_auth();
    
    $payout_id = intval($_POST['payout_id']);
    
    global $wpdb;
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
 $result= $consultation_db -> delete_payout($payout_id)  ; 
    // // فقط تسویه‌های در انتظار قابل حذف هستند
    // $result = $wpdb->delete(
    //     $consultation_db->payouts_table,
    //     ['id' => $payout_id, 'status' => 'pending'],
    //     ['%d', '%s']
    // );
    
    if ($result) {
        wp_send_json_success('تسویه با موفقیت حذف شد');
    } else {
        wp_send_json_error('خطا در حذف تسویه');
    }
}

// دریافت مشاوران با کمیسیون پرداخت نشده
add_action('wp_ajax_admin_get_consultants_with_pending', 'admin_get_consultants_with_pending_handler');
function admin_get_consultants_with_pending_handler() {
    admin_payout_ajax_auth();
    
    $page = intval($_POST['page'] ?? 1);
    $per_page = intval($_POST['per_page'] ?? 20);
    
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $filters = $_POST['filters'] ?? [];
    $data = $consultation_db->get_consultants_with_pending_commissions($filters, $page, $per_page);
    wp_send_json_success($data);

    
    
    wp_send_json_success($data);
}

// دریافت تعداد برای badgeهای تب‌ها
add_action('wp_ajax_admin_get_tab_counts', 'admin_get_tab_counts_handler');
function admin_get_tab_counts_handler() {
    admin_payout_ajax_auth();
    
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $counts = $consultation_db->get_counts_for_tabs();
    
    wp_send_json_success($counts);
}


// ارسال نوتیفیکیشن به مشاور
function admin_send_payout_notification($payout_id) {
    $consultation_db = AI_Assistant_Diet_Consultation_DB::get_instance();
    $payout_details = $consultation_db->get_payout_details($payout_id);
    
    if (!$payout_details) return;
    
    $consultant_id = $payout_details['payout']->consultant_id;
    $consultant = get_user_by('id', $consultant_id);
    
    if (!$consultant) return;
    
    // ساخت جدول کمیسیون‌ها
    $commissionsHtml = '';
    foreach ($payout_details['commissions'] as $commission) {
        $approved_at = !empty($commission->approved_at) ? $commission->approved_at : '---';
        $delay_hours = !empty($commission->delay_hours) ? $commission->delay_hours : '---';
        $penalty_multiplier = !empty($commission->penalty_multiplier) ? $commission->penalty_multiplier : '---';
        $commissionsHtml .= '<tr style="border-bottom:1px solid #ddd;">
            <td style="padding:8px;text-align:center;">'.$commission->id.'</td>
            <td style="padding:8px;text-align:center;">#'.$commission->request_id.'</td>
            <td style="padding:8px;text-align:center;">'.$commission->generated_at.'</td>
            <td style="padding:8px;text-align:center;">'.$approved_at.'</td>
            <td style="padding:8px;text-align:right;">'.$commission->base_amount.' تومان</td>
            <td style="padding:8px;text-align:center;">'.($commission->commission_type === 'percent' ? 'درصدی' : 'ثابت').'</td>
            <td style="padding:8px;text-align:center;">'.$commission->commission_value.'</td>
            <td style="padding:8px;text-align:center;">'.$delay_hours.'</td>
            <td style="padding:8px;text-align:center;">'.$penalty_multiplier.'</td>
            <td style="padding:8px;text-align:right;">'.$commission->final_commission.' تومان</td>
        </tr>';
    }

    $subject = 'تسویه حساب شما انجام شد';
    $message = '<html><body style="font-family:Tahoma, sans-serif; direction:rtl; background:#f9f9f9; padding:20px;">
        <div style="max-width:800px; margin:auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h2 style="text-align:center; color:#333;">تسویه حساب انجام شد</h2>
            <p>سلام <strong>'.$consultant->display_name.'</strong>،</p>
            <p>تسویه حساب شما با جزئیات زیر انجام شد:</p>
            <table style="border-collapse:collapse;width:100%; margin-top:15px; font-size:14px;">
                <thead>
                    <tr style="background:#4CAF50; color:#fff;">
                        <th style="padding:8px;">ID</th>
                        <th style="padding:8px;">درخواست</th>
                        <th style="padding:8px;">تاریخ تولید</th>
                        <th style="padding:8px;">تأیید</th>
                        <th style="padding:8px;">مبلغ پایه</th>
                        <th style="padding:8px;">نوع کمیسیون</th>
                        <th style="padding:8px;">مقدار کمیسیون</th>
                        <th style="padding:8px;">ساعت تاخیر</th>
                        <th style="padding:8px;">ضریب جریمه</th>
                        <th style="padding:8px;">مبلغ نهایی</th>
                    </tr>
                </thead>
                <tbody>
                    '.$commissionsHtml.'
                </tbody>
            </table>
            <p style="margin-top:15px; text-align:right; font-size:16px; font-weight:bold; background:#FFD700; padding:10px; border-radius:4px;">
                مجموع مبلغ انتخاب‌شده: '.$payout_details['payout']->amount.' تومان
            </p>
            <p style="margin-top:10px;"><strong>بازه زمانی:</strong> '.$payout_details['payout']->period_start.' تا '.$payout_details['payout']->period_end.'</p>
            <p><strong>شماره پیگیری:</strong> '.$payout_details['payout']->reference_code.'</p>
            <p><strong>تاریخ پرداخت:</strong> '.$payout_details['payout']->paid_at.'</p>
            <p style="margin-top:20px;">با تشکر،<br>تیم مدیریت</p>
        </div>
    </body></html>';

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    
    wp_mail($consultant->user_email, $subject, $message, $headers);
}

