<?php
/**
 * Template Name: نمایش خروجی سرویس
 */

get_header('service');


// دریافت ID از URL
$history_id = get_query_var('history_id');
$history_manager = AI_Assistant_History_Manager::get_instance();
$user_id = get_current_user_id();


// دریافت اطلاعات کاربر با متا داده‌ها
function get_complete_user_data($user_id) {
    $user_data = get_userdata($user_id);
    
    if ($user_data) {
        return array(
            'ID' => $user_data->ID,
            'username' => $user_data->user_login,
            'email' => $user_data->user_email,
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name' => get_user_meta($user_id, 'last_name', true),
            'phone' => get_user_meta($user_id, 'phone_number', true), // فیلد سفارشی
            'address' => get_user_meta($user_id, 'user_address', true) // فیلد سفارشی
        );
    }
    
    return false;
} 




// بررسی وجود آیتم و مالکیت
if (!$history_id || !$history_manager->is_user_owner($history_id, $user_id)) {
    status_header(404);
    get_template_part(404);
    exit;
}

// دریافت اطلاعات از جدول
global $wpdb;
$table_name = $wpdb->prefix . 'service_history';
$item = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE id = %d",
    $history_id
));

if (!$item) {
    status_header(404);
    get_template_part(404);
    exit;
}

// تنظیم title صفحه
add_filter('document_title_parts', function($title) use ($item) {
    $title['title'] = $item->service_name ?: 'خروجی سرویس';
    return $title;
});

// اگر response شامل JSON است، از قالب مشابه template-result.php استفاده می‌کنیم
if ($item->response && is_string($item->response)) {
    $json_test = json_decode($item->response);
    if (json_last_error() === JSON_ERROR_NONE) {
        // مستقیماً محتوای template-result.php را با داده‌های خود بارگذاری می‌کنیم
        $result_data = array(
            'response' => $item->response,
            'remaining_credit' => 0
        );
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>برنامه تغذیه‌ای بالینی</title>
            <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/fonts/webfonts/all.min.css">
            <style>
                :root {
                    --primary-color: #4CAF50;
                    --primary-dark: #2E7D32;
                    --primary-light: #E8F5E8;
                    --text-color: #333;
                    --light-gray: #f5f5f5;
                    --border-radius: 8px;
                    --box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    --transition-time: 0.4s;
                }
                
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                
                body {
                    font-family: Vazir, Tahoma, sans-serif;
                    background-color: #f8f9fa;
                    margin: 0;
                    line-height: 1.6;
                    direction: rtl;
                    color: var(--text-color);
                    padding: 10px;
                    text-align: justify;
                    text-justify: inter-word;
                }
                
                h1, h2, h3, h4, h5, h6 {
                    text-align: right !important;
                }
                
                ul, ol {
                    text-align: justify;
                }
                
                li {
                    text-align: justify;
                }
                
                .result-container {
                    max-width: 900px;
                    margin: 0 auto;
                    padding: 0 10px;
                }
                
                .header {
                    text-align: center;
                    margin: 20px 0;
                    padding: 15px;
                    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
                    border-radius: var(--border-radius);
                    color: white;
                    box-shadow: var(--box-shadow);
                }
                
                .header h1 {
                    font-size: 1.5rem;
                    margin-bottom: 10px;
                }
                
                .header p {
                    opacity: 0.9;
                    font-size: 0.9rem;
                }
                
                .accordion-section {
                    background: #fff;
                    border-radius: var(--border-radius);
                    box-shadow: var(--box-shadow);
                    margin-bottom: 15px;
                    overflow: hidden;
                    border-top: 5px solid var(--primary-color);
                }
                
                .accordion-header {
                    padding: 15px 20px;
                    cursor: pointer;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    background-color: #fff;
                    transition: all var(--transition-time) ease;
                }
                
                .accordion-header:hover {
                    background-color: var(--primary-light);
                }
                
                .accordion-header h2 {
                    margin: 0;
                    font-size: 1.1rem;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .accordion-icon {
                    transition: transform var(--transition-time) cubic-bezier(0.4, 0, 0.2, 1);
                }
                
                .accordion-header.active .accordion-icon {
                    transform: rotate(180deg);
                }
                
                .accordion-content {
                    max-height: 0;
                    overflow: hidden;
                    transition: all var(--transition-time) cubic-bezier(0.4, 0, 0.2, 1);
                    padding: 0 20px;
                    opacity: 0;
                    transform: translateY(-10px);
                }
                
                .accordion-content-inner {
                    padding: 15px 0;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                }
                
                .accordion-content.active {
                    max-height: 50000px;
                    opacity: 1;
                    transform: translateY(0);
                }
                
                .accordion-content.active .accordion-content-inner {
                    opacity: 1;
                    transition: opacity 0.3s ease 0.2s;
                }
                
                .action-controls {
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    margin: 25px 0;
                    flex-wrap: wrap;
                }
                
                .action-btn {
                    padding: 14px 20px;
                    background: var(--primary-dark);
                    color: white;
                    border: none;
                    border-radius: var(--border-radius);
                    cursor: pointer;
                    font-family: Tahoma, sans-serif;
                    font-weight: bold;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    transition: all 0.3s ease;
                    box-shadow: var(--box-shadow);
                    width: 100%;
                    max-width: 100%;
                    text-align: center;
                    font-size: 1rem;
                }
                
                .action-btn:hover {
                    background: var(--primary-color);
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
                
                .action-btn.print {
                    background: var(--primary-dark);
                }
                
                .action-btn.print:hover {
                    background: var(--primary-color);
                }
                
                h3 {
                    color: var(--primary-color);
                    margin: 15px 0 10px;
                    font-size: 1rem;
                    padding-right: 10px;
                    border-right: 3px solid var(--primary-color);
                }
                
                ul {
                    list-style-type: none;
                    padding: 0;
                }
                
                ul li {
                    padding: 10px;
                    border-bottom: 1px dashed #ddd;
                    background-color: var(--light-gray);
                    margin-bottom: 8px;
                    border-radius: var(--border-radius);
                    display: flex;
                    justify-content: space-between;
                }
                
                ul li:last-child {
                    border-bottom: none;
                    margin-bottom: 0;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                    display: none;
                }
                
                table th, table td {
                    border: 1px solid #ddd;
                    padding: 10px;
                    text-align: center;
                }
                
                table th {
                    background: var(--primary-color);
                    color: white;
                }
                
                .mobile-cards {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                    margin-top: 15px;
                }
                
                .day-card {
                    background: var(--primary-light);
                    border-radius: var(--border-radius);
                    padding: 15px;
                    box-shadow: var(--box-shadow);
                    transition: transform 0.3s ease;
                }
                
                .day-card:hover {
                    transform: translateY(-5px);
                }
                
                .day-header {
                    background: var(--primary-color);
                    color: white;
                    padding: 10px;
                    border-radius: 8px;
                    margin-bottom: 10px;
                    text-align: center;
                    font-weight: bold;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }
                
                .meal-section {
                    margin-bottom: 12px;
                    padding: 10px;
                    background: white;
                    border-radius: 8px;
                }
                
                .meal-title {
                    font-weight: bold;
                    color: var(--primary-dark);
                    margin-bottom: 5px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .footer {
                    text-align: center;
                    margin-top: 40px;
                    color: #777;
                    padding: 15px;
                    font-size: 0.9rem;
                }        
                
                .back-button {
                    display: inline-block;
                    margin: 20px auto;
                    padding: 14px 20px;
                    background: var(--primary-dark);
                    color: white;
                    text-decoration: none;
                    border-radius: var(--border-radius);
                    cursor: pointer;
                    text-align: center;
                    font-weight: bold;
                    border: none;
                    box-shadow: var(--box-shadow);
                    width: 100%;
                    max-width: 100%;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    font-size: 1rem;
                }
                
                .back-button:hover {
                    background: var(--primary-color);
                    transform: translateY(-2px);
                }
                
                .error {
                    color: #d32f2f;
                    background: #ffebee;
                    padding: 15px;
                    border-radius: var(--border-radius);
                    margin: 15px 0;
                    border-right: 4px solid #d32f2f;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .credit-info {
                    background: var(--primary-light);
                    padding: 15px;
                    border-radius: var(--border-radius);
                    margin: 15px 0;
                    text-align: center;
                    font-weight: bold;
                    color: var(--primary-dark);
                    border: 1px solid var(--primary-color);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                }
                
                
.user-date-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #1ABC9C 0%, #16A085 100%); 
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    margin: 15px 0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    font-family: 'Vazir', 'Tanha', 'Segoe UI', Tahoma, sans-serif;
}

.report-user-info, .date-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 500;
}

.report-user-info i, .date-info i {
    font-size: 16px;
    background: rgba(255,255,255,0.2);
    padding: 6px;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
}                
                
                @media (min-width: 768px) {
                    body {
                        padding: 20px;
                    }
                    
                    .header h1 {
                        font-size: 1.8rem;
                    }
                    
                    .mobile-cards {
                        display: none;
                    }
                    
                    table {
                        display: table;
                    }
                    
                    .back-button {
                        width: auto;
                    }
                    
    .user-date-row {
     /*   flex-direction: column; */
        gap: 10px;
        text-align: center;
    }
    
    .report-user-info, .date-info {
        justify-content: center;
    }                    
                }
                
                @media (max-width: 480px) {
                    .accordion-header {
                        padding: 12px 15px;
                    }
                    
                    .accordion-header h2 {
                        font-size: 1rem;
                    }
                    
                    ul li {
                        padding: 8px;
                        font-size: 0.9rem;
                        flex-direction: column;
                        gap: 5px;
                    }
                    
                    .day-card {
                        padding: 7px;
                    }
                    
                    .day-header {
                        font-size: 0.9rem;
                    }
                }
                
                .badge {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    margin-left: 5px;
                }
                
                .badge-primary {
                    background-color: var(--primary-color);
                    color: white;
                }
                
                .badge-light {
                    background-color: var(--light-gray);
                    color: var(--text-color);
                }
                
                .nutrition-facts {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    margin: 15px 0;
                }
                
                .nutrition-item {
                    flex: 1;
                    min-width: 120px;
                    text-align: center;
                    padding: 10px;
                    background: var(--primary-light);
                    border-radius: 8px;
                }
                
                .nutrition-value {
                    font-size: 1.2rem;
                    font-weight: bold;
                    color: var(--primary-dark);
                }
                
                .nutrition-label {
                    font-size: 0.8rem;
                    margin-top: 5px;
                }
                
                .progress-bar {
                    height: 8px;
                    background-color: #e0e0e0;
                    border-radius: 4px;
                    overflow: hidden;
                    margin: 10px 0;
                }
                
                .progress-fill {
                    height: 100%;
                    background-color: var(--primary-color);
                    border-radius: 4px;
                }
                
                .action-controls {
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    margin: 25px 0;
                    flex-wrap: wrap;
                }
                
                .action-btn {
                    padding: 12px 25px;
                    background: var(--primary-color);
                    color: white;
                    border: none;
                    border-radius: 30px;
                    cursor: pointer;
                    font-family: Vazir, Tahoma, sans-serif;
                    font-weight: bold;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    transition: all 0.3s ease;
                    box-shadow: var(--box-shadow);
                    min-width: 160px;
                    justify-content: center;
                }
                
                .action-btn:hover {
                    background: var(--primary-dark);
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
                
                .action-btn.print {
                    background: var(--primary-dark);
                }
                
                .action-btn.print:hover {
                    background: var(--primary-color);
                }        
                
                @media (max-width: 480px) {
                    .action-controls {
                        flex-direction: column;
                        align-items: center;
                    }
                    
                    .action-btn {
                        width: 100%;
                    }
                }
                
                @media print {
                    .action-controls,
                    .back-button {
                        display: none !important;
                    }
                    
                    .accordion-content {
                        display: block !important;
                        max-height: none !important;
                        opacity: 1 !important;
                        height: auto !important;
                    }
                    
                    .accordion-header {
                        border-bottom: 2px solid var(--primary-color);
                        pointer-events: none;
                    }
                    
                    .accordion-header .accordion-icon {
                        display: none !important;
                    }
                    
                    body {
                        padding: 0;
                        background: white;
                        color: black;
                        font-size: 12pt;
                    }
                    
                    .result-container {
                        max-width: 100%;
                        padding: 0;
                        margin: 0;
                    }
                    
                    .header {
                        background: white !important;
                        color: black !important;
                        border: 2px solid black;
                    }
                    
                    .accordion-icon,
                    .badge {
                        display: none !important;
                    }
                    
                    ul li {
                        background: white !important;
                        border: 1px solid #ddd !important;
                    }
                    
                    .day-card {
                        break-inside: avoid;
                    }            
                }
            </style>
        </head>
        <body>
            <div class="result-container">
                <div class="header">
                    <h1><i class="fas fa-utensils"></i> برنامه تغذیه‌ای بالینی</h1>
                    <p>برنامه غذایی شخصی شده بر اساس مشخصات شما</p>
                    <div class="user-date-row">
                        <span class="report-user-info">
                            <i class="fas fa-user"></i>
                            <?php 
                                $user_info = get_complete_user_data(get_current_user_id());
                                if ($user_info && (!empty($user_info['first_name']) || !empty($user_info['last_name']))) {
                                    echo esc_html(trim($user_info['first_name'] . ' ' . $user_info['last_name']));
                                } 
                            ?>
                        </span>
                        
                        <span class="date-info">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo esc_html(date_i18n('j F Y - H:i', strtotime($item->created_at))); ?>
                        </span>
                    </div>
                </div>
                
                <div class="action-controls">
                    <button class="action-btn print" onclick="printDocument()">
                        <i class="fas fa-print"></i> چاپ برنامه
                    </button>
                </div>
                
                <div id="debug-info"></div>
                <div id="content"></div>
                
                <div style="text-align: center;">
                    <a href="<?php echo home_url(); ?>" class="back-button">
                        <i class="fas fa-home"></i>
                        بازگشت به صفحه اصلی
                    </a>
                </div>
            </div>

            <script>
            // داده‌های ما
            const resultData = <?php echo json_encode($result_data); ?>;
            
            // تابع برای باز کردن همه آکاردئون‌ها قبل از چاپ
            function expandAllForPrint() {
                const accordionHeaders = document.querySelectorAll('.accordion-header');
                const accordionContents = document.querySelectorAll('.accordion-content');
                const accordionContentInners = document.querySelectorAll('.accordion-content-inner');
                
                accordionHeaders.forEach(header => {
                    header.classList.add('active');
                });
                
                accordionContents.forEach(content => {
                    content.classList.add('active');
                    content.style.maxHeight = 'none';
                    content.style.opacity = '1';
                });
                
                accordionContentInners.forEach(inner => {
                    inner.style.opacity = '1';
                });
            }
            
            // تابع چاپ با اطمینان از نمایش تمام محتوا
            function printDocument() {
                expandAllForPrint();
                
                setTimeout(() => {
                    window.print();
                }, 100);
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                const debugContainer = document.getElementById('debug-info');
                const contentContainer = document.getElementById('content');
                
                if (resultData) {
                    try {
                        // نمایش اطلاعات اعتبار باقیمانده
                        // if (resultData.remaining_credit !== undefined) {
                        //     debugContainer.innerHTML += '<div class="credit-info"><i class="fas fa-coins"></i> اعتبار باقیمانده: ' + resultData.remaining_credit.toLocaleString() + ' تومان</div>';
                        // }
                        
                        // پردازش response
                        if (resultData.response) {
                            try {
                                const responseData = JSON.parse(resultData.response);
                                renderContent(responseData);
                            } catch (e) {
                                debugContainer.innerHTML += '<div class="error"><i class="fas fa-exclamation-circle"></i> خطا در پردازش response: ' + e.message + '</div>';
                                contentContainer.innerHTML = '<div class="accordion-section"><div class="accordion-header"><h2><i class="fas fa-exclamation-triangle"></i> نتایج برنامه غذایی</h2><i class="fas fa-chevron-down accordion-icon"></i></div><div class="accordion-content"><div class="accordion-content-inner"><div class="error">' + resultData.response + '</div></div></div></div>';
                            }
                        } else {
                            renderContent(resultData);
                        }
                    } catch (e) {
                        debugContainer.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> خطا در پردازش داده‌ها: ' + e.message + '</div>';
                    }
                }
                
                setTimeout(initAccordions, 100);
            });
            
            // تابع مقداردهی اولیه آکاردئون‌ها
            function initAccordions() {
                const accordionHeaders = document.querySelectorAll('.accordion-header');
                
                accordionHeaders.forEach(header => {
                    header.addEventListener('click', function() {
                        const isActive = this.classList.contains('active');
                        const content = this.nextElementSibling;
                        
                        if (isActive) {
                            this.classList.remove('active');
                            content.classList.remove('active');
                            content.style.maxHeight = '0';
                        } else {
                            this.classList.add('active');
                            content.classList.add('active');
                            content.style.maxHeight = content.scrollHeight + 'px';
                        }
                    });
                });
                
                if (accordionHeaders[0]) {
                    accordionHeaders[0].classList.add('active');
                    const firstContent = accordionHeaders[0].nextElementSibling;
                    firstContent.classList.add('active');
                    firstContent.style.maxHeight = firstContent.scrollHeight + 'px';
                }
            }

            function renderContent(data) {
                const container = document.getElementById("content");
                container.innerHTML = '';
                
                if (!data.sections || !Array.isArray(data.sections)) {
                    container.innerHTML = '<div class="accordion-section"><div class="accordion-header"><h2><i class="fas fa-utensils"></i> نتایج برنامه غذایی</h2><i class="fas fa-chevron-down accordion-icon"></i></div><div class="accordion-content"><div class="accordion-content-inner"><div class="debug">' + JSON.stringify(data, null, 2) + '</div></div></div></div>';
                    return;
                }

                data.sections.forEach((section, index) => {
                    const sectionDiv = document.createElement("div");
                    sectionDiv.className = "accordion-section";

                    const headerDiv = document.createElement("div");
                    headerDiv.className = "accordion-header";
                    headerDiv.innerHTML = '<h2><i class="fas fa-' + getSectionIcon(section.title) + '"></i> ' + (section.title || "بخش بدون عنوان") + '</h2><i class="fas fa-chevron-down accordion-icon"></i>';
                    
                    const contentDiv = document.createElement("div");
                    contentDiv.className = "accordion-content";
                    
                    const contentInnerDiv = document.createElement("div");
                    contentInnerDiv.className = "accordion-content-inner";

                    if (Array.isArray(section.content)) {
                        section.content.forEach(sub => {
                            if (sub && sub.subtitle) {
                                const h3 = document.createElement("h3");
                                h3.textContent = sub.subtitle;
                                contentInnerDiv.appendChild(h3);
                            }

                            if (sub && sub.type === "list" && sub.items) {
                                const ul = document.createElement("ul");
                                sub.items.forEach(item => {
                                    const li = document.createElement("li");
                                    if (item && item.label && item.value) {
                                        li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                                    } else if (typeof item === "string") {
                                        li.textContent = item;
                                    }
                                    ul.appendChild(li);
                                });
                                contentInnerDiv.appendChild(ul);
                            }
                            
                            if (sub && sub.type === "table" && sub.headers && sub.rows) {
                                const table = document.createElement("table");
                                const thead = document.createElement("thead");
                                const headRow = document.createElement("tr");
                                
                                sub.headers.forEach(h => {
                                    const th = document.createElement("th");
                                    th.textContent = h;
                                    headRow.appendChild(th);
                                });
                                
                                thead.appendChild(headRow);
                                table.appendChild(thead);

                                const tbody = document.createElement("tbody");
                                sub.rows.forEach(row => {
                                    const tr = document.createElement("tr");
                                    row.forEach(cell => {
                                        const td = document.createElement("td");
                                        td.textContent = cell;
                                        tr.appendChild(td);
                                    });
                                    tbody.appendChild(tr);
                                });
                                
                                table.appendChild(tbody);
                                contentInnerDiv.appendChild(table);
                                
                                const mobileCards = document.createElement("div");
                                mobileCards.className = "mobile-cards";
                                
                                sub.rows.forEach((row, index) => {
                                    const dayCard = document.createElement("div");
                                    dayCard.className = "day-card";
                                    
                                    const dayHeader = document.createElement("div");
                                    dayHeader.className = "day-header";
                                    dayHeader.innerHTML = '<i class="fas fa-calendar-day"></i> <span>' + row[0] + '</span>';
                                    dayCard.appendChild(dayHeader);
                                    
                                    for (let i = 1; i < row.length; i++) {
                                        if (row[i]) {
                                            const mealSection = document.createElement("div");
                                            mealSection.className = "meal-section";
                                            
                                            const mealTitle = document.createElement("div");
                                            mealTitle.className = "meal-title";
                                            mealTitle.innerHTML = '<i class="fas fa-' + getMealIcon(i) + '"></i> <span>' + sub.headers[i] + '</span>';
                                            
                                            const mealContent = document.createElement("div");
                                            mealContent.textContent = row[i];
                                            
                                            mealSection.appendChild(mealTitle);
                                            mealSection.appendChild(mealContent);
                                            dayCard.appendChild(mealSection);
                                        }
                                    }
                                    
                                    mobileCards.appendChild(dayCard);
                                });
                                
                                contentInnerDiv.appendChild(mobileCards);
                            }
                        });
                    } else if (section.content && typeof section.content === "object") {
                        if (section.content.type === "list" && section.content.items) {
                            const ul = document.createElement("ul");
                            section.content.items.forEach(item => {
                                const li = document.createElement("li");
                                if (typeof item === "string") {
                                    li.textContent = item;
                                } else if (item && item.label && item.value) {
                                    li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                                }
                                ul.appendChild(li);
                            });
                            contentInnerDiv.appendChild(ul);
                        }
                        
                        if (section.content.type === "paragraph" && section.content.text) {
                            const p = document.createElement("p");
                            p.textContent = section.content.text;
                            contentInnerDiv.appendChild(p);
                        }
                        
                        if (section.content.type === "nested_list" && section.content.items) {
                            const ul = document.createElement("ul");
                            section.content.items.forEach(item => {
                                const li = document.createElement("li");
                                if (item && item.label && item.value) {
                                    li.innerHTML = `<span>${item.label}:</span> <span>${item.value}</span>`;
                                }
                                ul.appendChild(li);
                            });
                            contentInnerDiv.appendChild(ul);
                        }
                    }

                    contentDiv.appendChild(contentInnerDiv);
                    sectionDiv.appendChild(headerDiv);
                    sectionDiv.appendChild(contentDiv);
                    container.appendChild(sectionDiv);
                });

                if (data.footer) {
                    const footer = document.createElement("div");
                    footer.className = "footer";
                    footer.textContent = data.footer;
                    container.appendChild(footer);
                }
                
            }

            function getSectionIcon(sectionTitle) {
                const icons = {
                    'اطلاعات کاربر': 'user',
                    'اطلاعات تغذیه‌ای': 'chart-pie',
                    'برنامه هفتگی': 'calendar-alt',
                    'توصیه‌های تکمیلی': 'lightbulb',
                    'نتایج برنامه غذایی': 'utensils'
                };
                
                return icons[sectionTitle] || 'file-alt';
            }

            function getMealIcon(mealIndex) {
                const icons = {
                    1: 'sun',
                    2: 'sun',
                    3: 'moon',
                    4: 'apple-alt'
                };
                
                return icons[mealIndex] || 'utensils';
            }
            
            


         
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// اگر JSON نبود، نمایش معمولی
echo '<div class="ai-service-output-container" style="max-width: 800px; margin: 2rem auto; padding: 1rem;">';
echo '<h1 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">' . esc_html($item->service_name) . '</h1>';
echo '<div class="service-content" style="background: #f9f9f9; padding: 1.5rem; border-radius: 5px; margin-top: 1rem;">';
echo apply_filters('the_content', $item->response);
echo '</div>';
echo '</div>';

get_footer();