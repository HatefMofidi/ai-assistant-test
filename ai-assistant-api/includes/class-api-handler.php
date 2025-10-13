<?php
class AI_Assistant_Api_Handler {
    private static $instance;
    private $api_key;
    private $logger;

    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // تغییر نام option به ai_assistant_deepseek_key
        $this->api_key = get_option('ai_assistant_deepseek_key');
        $this->logger = AI_Assistant_Logger::get_instance();
        

        $this->register_hooks();

        // اطمینان از وجود پوشه لاگ
        if (!file_exists(WP_CONTENT_DIR.'/ai-assistant-logs')) {
            wp_mkdir_p(WP_CONTENT_DIR.'/ai-assistant-logs');
        }
    }   

    private function register_hooks() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_ai_assistant_process', [$this, 'process_request']);
        add_action('wp_ajax_nopriv_ai_assistant_process', [$this, 'handle_unauthorized']);
    }

    public function add_admin_page() {
        add_options_page(
            'تنظیمات DeepSeek',
            'DeepSeek_API',
            'manage_options',
            'ai_assistant-settings', // تغییر نام منو
            [$this, 'render_admin_page']
        );
    }

    public function register_settings() {
        register_setting('ai_assistant_settings', 'ai_assistant_deepseek_key'); // تغییر نام تنظیمات

        add_settings_section(
            'ai_assistant_api_section', // تغییر نام سکشن
            'تنظیمات API',
            null,
            'ai_assistant-settings'
        );

        add_settings_field(
            'ai_assistant_deepseek_key', // تغییر نام فیلد
            'API Key',
            [$this, 'render_api_key_field'],
            'ai_assistant-settings',
            'ai_assistant_api_section'
        );
    }

    public function render_api_key_field() {
        $value = esc_attr($this->api_key);
        echo '<input type="password" name="ai_assistant_deepseek_key" value="'.$value.'" class="regular-text">'; // تغییر نام فیلد
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>تنظیمات DeepSeek API</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_assistant_settings');
                do_settings_sections('ai_assistant-settings');
                submit_button();
                ?>
            </form>

            <h2>لاگ خطاها</h2>
            <pre><?php 
                if (!current_user_can('administrator')) {
                    echo 'دسترسی مجاز نیست.';
                    return;
                }

                $log_file = WP_CONTENT_DIR . '/ai-assistant-logs/ai-assistant.log';
                echo file_exists($log_file) ? 
                     esc_html(file_get_contents($log_file)) : 
                     'لاگی وجود ندارد';
            ?></pre>
        </div>
        <?php
    }

    public function process_request() {
        check_ajax_referer('ai_assistant_nonce', 'security');         

        if (ob_get_length()) ob_clean();

        try {
            $user_id = get_current_user_id();
            if (!$user_id) {
                wp_send_json_error(__('برای استفاده از این سرویس باید وارد شوید.', 'ai-assistant'));
            }
            
        //    $service_name = sanitize_text_field($_POST['name']);
            $service_id = sanitize_text_field($_POST['service_id']);
            $userData = stripslashes($_POST['userData']);
            
            $all_services = get_option('ai_assistant_services', []);
            // $system_prompt = $all_services[$service_id]['system_prompt'] ?? '';
            // $prompt = $system_prompt . "\n\n" . $userData;

            $service_name = $all_services[$service_id]['name'];
            $service_manager = AI_Assistant_Service_Manager::get_instance();
            $original_price = $service_manager->get_service_price($service_id);
            
            $service_info = $service_manager->get_service($service_id);
            if ($service_info && isset($service_info['system_prompt'])) {
                $system_prompt = $service_info['system_prompt'];
                // استفاده از system_prompt
               // error_log('System Prompt: ' . $system_prompt);
            } else {
                error_log('Service not found or system_prompt not set');
            }
            
            $prompt = $system_prompt . "\n\n" . $userData;
            
            $payment_handler = AI_Assistant_Payment_Handler::get_instance();
            
/*
            // اعمال تخفیف
            $discount_db = AI_Assistant_Discount_DB::get_instance();
            
            // در صورتی که کاربر کد تخفیف وارد کرده باشد
            $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';
            
            $discount = $discount_db->calculate_discount($service_id, $original_price, $user_id, $coupon_code);
            $final_price = $original_price - $discount['amount'];
            
            // افزایش تعداد استفاده از تخفیف پس از خرید موفق
            if ($discount['id'] > 0) {
                $discount_db->increment_usage($discount['id']);
            }        

*/
            $final_price = $original_price;



            
        
            $this->validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler);

            // کسر هزینه
            $payment_handler->deduct_credit($user_id, $final_price, $service_name);

            // ثبت لاگ
            $this->logger->log('Processing API request', [
                'user_id:' => $user_id,
                'service_id:' => $service_id,
                'final_price:' => $final_price,
                'original_price:' => $original_price,
                'discount:' => $discount
            ]);
            //-----------------------------------------------------------------

            $json_string = ' 
            {
              "title": "برنامه تغذیه‌ای بالینی",
              "sections": [
                {
                  "title": "اخطار",
                  "content": {
                    "type": "paragraph",
                    "text": "با توجه به سابقه جراحی پاراتیروئید و کلیه، هرگونه تغییر در رژیم غذایی باید با پزشک معالج و تیم پیوند شما هماهنگ شود. این برنامه صرفاً یک پیشنهاد کلی است و ممکن است نیاز به تنظیمات خاص بر اساس شرایط بالینی شما داشته باشد."
                  }
                },
                {
                  "title": "۱. اطلاعات ضروری کاربر",
                  "content": {
                    "type": "list",
                    "className": "horizontal-list",
                    "items": [
                      {"label": "سن", "value": "38 سال"},
                      {"label": "جنسیت", "value": "مرد"},
                      {"label": "وزن و قد", "value": "98 کیلوگرم - 176 سانتی‌متر (BMI: 31.6)"},
                      {"label": "بیماری‌ها/داروها", "value": "سابقه جراحی پاراتیروئید و کلیه"},
                      {"label": "حساسیت‌ها", "value": "ندارد"},
                      {"label": "سطح فعالیت", "value": "پایین"},
                      {"label": "هدف", "value": "تناسب اندام و کاهش وزن از 98 به 80 کیلوگرم"}
                    ]
                  }
                },
                {
                  "title": "۲. تحلیل علمی",
                  "content": {
                    "type": "paragraph",
                    "text": "شاخص توده بدنی (BMI) شما 31.6 است که در محدوده چاقی درجه 1 قرار دارد. با توجه به سابقه جراحی کلیه، کنترل پروتئین، سدیم، پتاسیم و فسفر در رژیم غذایی اهمیت ویژه‌ای دارد. مطالعات نشان می‌دهند کاهش وزن تدریجی 0.5-1 کیلوگرم در هفته برای افراد با سابقه مشکلات کلیوی ایمن‌تر است (Kovesdy et al., 2017). رژیم کم‌چرب با کالری کنترل‌شده می‌تواند به بهبود پروفایل لیپیدی و کاهش وزن کمک کند."
                  }
                },
                {
                  "title": "۳. نتیجه مورد انتظار",
                  "content": {
                    "type": "list",
                    "className": "vertical-list",
                    "items": [
                      {"label": "وضعیت وزن فعلی شما نسبت به محدوده‌ی ایده‌آل", "value": "وزن فعلی 98 کیلوگرم، محدوده ایده‌آل 60-78 کیلوگرم (اضافه وزن حدود 20 کیلوگرم)"},
                      {"label": "مدت زمان انجام این برنامه غذایی", "value": "8 هفته (با قابلیت تمدید پس از بررسی پزشکی)"},
                      {"label": "نتیجه مورد انتظار بعد از یک دوره رعایت رژیم", "value": "کاهش وزن 4-8 کیلوگرم در 8 هفته با فرض پیروی 80% از برنامه و فعالیت فیزیک منظم"}
                    ]
                  }
                },
                {
                  "title": "۴. برنامه‌ی عملی",
                  "content": [
                    {
                      "subtitle": "الف) کلیات برنامه:",
                      "type": "list",
                      "className": "vertical-list",
                      "items": [
                        {"label": "کالری روزانه", "value": "2200 کیلوکالری (با deficit 500 کالری از نیاز روزانه)"},
                        {"label": "درشت‌مغذی‌ها", "value": "پروتئین: 110 گرم (20%)، چربی: 60 گرم (25%)، کربوهیدرات: 275 گرم (55%)"},
                        {"label": "مکمل‌ها (در صورت نیاز)", "value": "ویتامین D3 (1000 IU روزانه) پس از تایید پزشک با توجه به سابقه پاراتیروئید"}
                      ]
                    },
                    {
                      "subtitle": "ب) برنامه‌ی غذایی هفتگی:",
                      "type": "table",
                      "headers": ["روز", "صبحانه", "میان‌وعده صبح", "ناهار", "میان‌وعده عصر", "شام"],
                      "rows": [
                        ["روز اول", "اوتمیل با شیر کم‌چرب (50 گرم جو دوسر + 200 میلی‌لیتر شیر)", "سیب متوسط (1 عدد)", "مرغ Grill شده (150 گرم) با برنج قهوه‌ای (100 گرم پخته) و سالاد", "ماست کم‌چرب (200 گرم) با گردو (2 عدد)", "ماهی قزل‌آلا بخارپز (120 گرم) با سبزیجات بخارپز"],
                        ["جایگزین روز اول", "نان سبوس‌دار (2 برش) با پنیر کم‌نمک (30 گرم) و گردو (2 عدد)", "نارنگی (2 عدد کوچک)", "عدس پلو (1 بشقاب متوسط) با سالاد شیرازی", "کefir (200 میلی‌لیتر)", "املت قارچ (2 تخم‌مرغ + 50 گرم قارچ) با نان سبوس‌دار"],
                        ["روز دوم", "شیر کم‌چرب (1 لیوان) با Corn Flakes سبوس‌دار (40 گرم)", "موز کوچک (1 عدد)", "کوفته تره‌فرنگی با برنج (3 عدد کوفته + 100 گرم برنج)", "خیار (1 عدد متوسط) با کمی نمک", "سینه بوقلمون Grill (120 گرم) با پوره کدو حلوایی"],
                        ["جایگزین روز دوم", "ماست چکیده کم‌چرب (150 گرم) با عسل (1 قاشق چایخوری)", "انجیر خشک (2 عدد)", "لوبیا چیتی خورشت (1 بشقاب کوچک) با نان سنگک", "سینه مرغ آبپز (50 گرم)", "ماهی تن با سالاد (100 گرم ماهی تن در آب)"],
                        ["روز سوم", "تخم‌مرغ آبپز (2 عدد) با نان جو (1 برش)", "پرتقال (1 عدد متوسط)", "باقالی پلو با ماهیچه (1 بشقاب متوسط)", "شیر کم‌چرب (1 لیوان)", "مرغ و سبزیجات Stir-fry (150 گرم مرغ + سبزیجات متنوع)"],
                        ["جایگزین روز سوم", "حلیم جو (1 کاسه کوچک) با دارچین", "کیوی (1 عدد)", "آبگوشت کم‌چرب (1 کاسه) با نان سنگک", "پسته (10 عدد)", "املت سبزیجات (2 تخم‌مرغ + سبزیجات معطر)"],
                        ["روز چهارم", "پنیر کم‌نمک (30 گرم) با نان سبوس‌دار و گردو", "انار (1/2 عدد)", "قیمه نثار با برنج (1 بشقاب متوسط)", "ماست کم‌چرب (150 گرم)", "سوپ مرغ و جو (1 کاسه بزرگ)"],
                        ["جایگزین روز چهارم", "شیر برنج کم‌شکر (1 کاسه کوچک)", "سیب زمینی آبپز (1 عدد کوچک)", "دلمه برگ مو (4 عدد) با ماست", "بادام (7 عدد)", "مرغ پیچیده در foil با سبزیجات"],
                        ["روز پنجم", "املت گوجه‌فرنگی (2 تخم‌مرغ) با نان سنگک", "انگور (1 خوشه کوچک)", "زرشک پلو با مرغ (1 بشقاب متوسط)", "دوغ کم‌نمک (1 لیوان)", "ماهی سفید بخارپز (120 گرم) با سبزیجات"],
                        ["جایگزین روز پنجم", "عدسی (1 کاسه کوچک) با نان", "خرما (2 عدد)", "خوراک مرغ و قارچ (1 بشقاب)", "کمپوت هلو بدون شکر (1 کاسه کوچک)", "پاستا با سس گوجه‌فرنگی و مرغ"],
                        ["روز ششم", "پنکیک جو دوسر (2 عدد کوچک) با عسل", "انبه (1/2 عدد کوچک)", "کلم پلو با ماهی (1 بشقاب متوسط)", "شیر سویا (1 لیوان)", "استیک گوشت کم‌چرب (120 گرم) با سالاد"],
                        ["جایگزین روز ششم", "نان تست آووکادو (1 برش) با تخم مرغ", "هلو (1 عدد)", "خورشت کرفس (1 بشقاب کوچک) با نان", "آب هویج (1 لیوان)", "مرغ کبابی با سبزیجات فرنگی"],
                        ["وعده آزاد (روز هفتم)", "نان لواش با پنیر و سبزی (2 عدد)", "میوه فصل", "چلوکباب کوبیده (1 سیخ) با نان و سبزی", "آب میوه طبیعی", "پیتزای سبزیجات خانگی (2 قطعه)"]
                      ]
                    }
                  ]
                },
                {
                  "title": "۵. دستور پخت غذاها",
                  "content": {
                    "type": "list",
                    "className": "vertical-list",
                    "items": [
                      {"label": "اوتمیل جو دوسر", "value": "1. 50 گرم جو دوسر را با 200 میلی‌لیتر شیر کم‌چرب مخلوط کنید\n2. روی حرارت ملایم به مدت 5 دقیقه هم بزنید\n3. شعله را خاموش کرده و بگذارید 2 دقیقه بماند\n4. با دارچین و کمی عسل سرو کنید"},
                      {"label": "مرغ Grill شده", "value": "1. سینه مرغ را با ادویه‌های مجاز مزه‌دار کنید\n2. در تابه نچسب Grill کنید\n3. هر طرف 6-7 دقیقه با حرارت متوسط\n4. قبل از سرو از پخت کامل اطمینان حاصل کنید"},
                      {"label": "ماهی قزل‌آلا بخارپز", "value": "1. فیله ماهی را با لیمو و سبزیجات معطر طعم‌دار کنید\n2. در دستگاه بخارپز یا روی آب جوش قرار دهید\n3. به مدت 15-20 دقیقه بپزید\n4. با سبزیجات تازه سرو کنید"}
                    ]
                  }
                },
                {
                  "title": "۶. هشدارها",
                  "content": {
                    "type": "list",
                    "items": [
                      "با توجه به سابقه کلیوی، از مصرف خودسرانه مکمل‌های پروتئینی پرهیز کنید",
                      "در صورت مشاهده تورم، کاهش ناگهانی ادرار یا افزایش وزن سریع، فوراً به پزشک مراجعه کنید",
                      "علائم هیپوکالمی (ضعف عضلانی، خستگی) یا هایپرکالمی (بی‌حسی، ضربان نامنظم) را جدی بگیرید"
                    ]
                  }
                },
                {
                  "title": "۷. توصیه‌ها",
                  "content": {
                    "type": "list",
                    "items": [
                      "مصرف آب را به تدریج و تحت نظر پزشک افزایش دهید",
                      "فعالیت بدنی منظم مانند پیاده‌روی 30 دقیقه‌ای روزانه را شروع کنید",
                      "پایش منظم فشار خون و آزمایشات کلیوی را فراموش نکنید"
                    ]
                  }
                },
                {
                  "title": "۸. سوالات احتمالی شما",
                  "content": {
                    "type": "list",
                    "className": "vertical-list",
                    "items": [
                      {
                        "label": "آیا می‌توانم قهوه بنوشم؟",
                        "value": "مصرف متعادل قهوه (1-2 فنجان روزانه) معمولاً مجاز است اما با توجه به سابقه کلیوی، بهتر است با پزشک خود مشورت کنید. توصیه: قهوه بدون شکر و خامه مصرف کنید"
                      },
                      {
                        "label": "در صورت تحمل نکردن گرسنگی چه کار کنم؟",
                        "value": "میان‌وعده‌های کم‌کالری مانند سبزیجات خام، ماست کم‌چرب یا میوه‌های کم‌قند مصرف کنید. توصیه: آب زیاد بنوشید و وعده‌ها را به 5-6 وعده کوچک‌تر تقسیم کنید"
                      },
                      {
                        "label": "برای کنترل وزن چه زمان‌هایی باید آزمایش دهم؟",
                        "value": "پایش ماهانه وزن و هر 3 ماه آزمایشات کلیوی توصیه می‌شود. منبع: National Kidney Foundation Guidelines, 2020"
                      },
                      {
                        "label": "نحوه ذخیره و گرم کردن غذاها چگونه است؟",
                        "value": "غذاها را در ظروف دربسته در یخچال حداکثر 3 روز نگهداری کنید. برای گرم کردن از حرارت غیرمستقیم استفاده کنید. توصیه: از گرم کردن مکرر غذاها خودداری کنید"
                      },
                      {
                        "label": "اگر مواد اولیه را نداشتم چه کار کنم؟",
                        "value": "از جایگزین‌های ارائه شده استفاده کنید یا با مواد مشابه از همان گروه غذایی جایگزین نمایید. توصیه: همیشه برنامه غذایی هفتگی را از قبل تهیه کنید"
                      },
                      {
                        "label": "آیا می‌توانم فست فود بخورم؟",
                        "value": "مصرف فست فود به دلیل سدیم و چربی بالا برای شرایط شما توصیه نمی‌شود. منبع: (Shim et al., 2023) - PMID: 36744032"
                      },
                      {
                        "label": "برای بهبود نتایج چه تغییراتی بدهم؟",
                        "value": "افزایش تدریجی فعالیت بدنی و پایبندی به برنامه غذایی مهم‌ترین عوامل هستند. توصیه: خواب کافی و مدیریت استرس را فراموش نکنید"
                      },
                      {
                        "label": "آیا نیاز به مصرف مکمل دارم؟",
                        "value": "تنها با توصیه پزشک معالج مکمل مصرف کنید. توصیه: ویتامین D3 ممکن است با توجه به سابقه پاراتیروئید نیاز باشد"
                      },
                      {
                        "label": "چگونه پروتئین کافی دریافت کنم؟",
                        "value": "از منابع پروتئین کم‌چرب مانند مرغ، ماهی و حبوبات استفاده کنید. توصیه: توزیع پروتئین در طول روز را رعایت کنید"
                      },
                      {
                        "label": "آیا می‌توانم شیرینی مصرف کنم؟",
                        "value": "مصرف محدود شیرینی‌های طبیعی مانند عسل یا میوه‌های خشک مجاز است. توصیه: از شیرینی‌های صنعتی و قندهای تصفیه شده پرهیز کنید"
                      }
                    ]
                  }
                },
                {
                  "title": "۹. منابع",
                  "content": {
                    "type": "list",
                    "items": [
                      "Kovesdy CP, et al. Obesity and kidney disease. J Am Soc Nephrol. 2017;28(2):407-408. doi:10.1681/ASN.2016101081",
                      "National Kidney Foundation. KDOQI Clinical Practice Guideline for Nutrition in CKD: 2020 Update. Am J Kidney Dis. 2020;76(3 Suppl 1):S1-S107",
                      "Shim JS, et al. Association of fast food consumption with obesity and cardiometabolic risk factors. Nutrients. 2023;15(2):362. PMID: 36744032",
                      "World Health Organization. Healthy diet. WHO.int. 2020",
                      "Academy of Nutrition and Dietetics. Nutrition Care Manual. 2023 Edition"
                    ]
                  }
                }
              ],
              "footer": "aidastyar.com"
            }
            ';     
    
            if (OTP_ENV === 'production') {
                $response = $this->call_deepseek_api($prompt);
            } else {
                $response  = $json_string ;
            }
            
            
            $cleaned_response = $this->clean_api_response($response);
            
            $history_manager = AI_Assistant_History_Manager::get_instance();
            // ذخیره در تاریخچه
            $saved = $history_manager->save_history($user_id, $service_id , $service_name , $userData , $cleaned_response);
            
            if ($service_id === 'diet'){
                
                
                
                $Nutrition_Consultant_Manager = AI_Assistant_Nutrition_Consultant_Manager::get_instance();
                $Consultant_Rec = $Nutrition_Consultant_Manager->submit_consultation_request($saved, 6000);
            }




            if (is_wp_error($saved)) {
                // خطای وردپرس
                $this->logger->log('History save WP_Error', [
                    'user_id' => $user_id,
                    'service_id' => $service_id,
                    'error_code' => $saved->get_error_code(),
                    'error_message' => $saved->get_error_message(),
                    'error_data' => $saved->get_error_data()
                ]);
              //  echo 'خطای سیستمی در ذخیره‌سازی: ' . $saved->get_error_message();
            } elseif ($saved) {
                // موفقیت‌آمیز
                $this->logger->log('History saved successfully', [
                    'user_id' => $user_id,
                    'service_id' => $service_id,
                    'service-name' => $service_name ,
                    'history_id' => $saved,
                    'output_sample' => substr(strip_tags($response), 0, 100)
                ]);
              //  echo 'نتایج با موفقیت ذخیره شد.';
            } else {
                // خطای عمومی
                $this->logger->log('History save failed', [
                    'user_id' => $user_id,
                    'service_id' => $service_id,
                    'html_size' => strlen($response),
                    'last_error' => error_get_last()
                ]);
              //  echo 'خطا در ذخیره‌سازی تاریخچه';
            }
            
            header('Content-Type: application/json; charset=utf-8');

            wp_send_json_success([
                'response' => $response,
                'remaining_credit' => $payment_handler->get_user_credit($user_id)
            ]);

        } catch (Exception $e) {

/*
            if (!empty($user_id) && !empty($price) && isset($payment_handler)) {
                $payment_handler->add_credit($user_id, $price, $description);
            }
            
*/            

            $this->logger->log_error($e->getMessage(), $_POST);
            wp_send_json_error($e->getMessage());
        }

        wp_die();
    }

    private function validate_request($prompt, $service_id, $user_id, $final_price, $payment_handler) {
        if (!is_user_logged_in()) {
            throw new Exception('برای استفاده از این سرویس باید وارد حساب کاربری خود شوید');
        }
        
        
        if (empty($prompt) || empty($service_id)) {
            throw new Exception('پارامترهای ورودی نامعتبر هستند');
        }

        if (!$payment_handler->has_enough_credit($user_id, $final_price)) {
            throw new Exception('.موجودی حساب شما کافی نیست');
        } 
    }

    private function call_deepseek_api($prompt) {
        $api_url = 'https://api.deepseek.com/v1/chat/completions';

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2,
                'max_tokens' => 8000
            ]),
            'timeout' => 180 ,
            'httpversion' => '1.1' // 📡 اطمینان از نسخه HTTP سازگار
        ];

        // ارسال درخواست به api دیپ سیک
        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            $this->logger->log_error('DeepSeek API connection error', [
                'error' => $response->get_error_message(),
                'prompt' => $prompt
            ]);
            throw new Exception('خطا در ارتباط با سرور DeepSeek: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $this->logger->log('DeepSeek API response', [
            'status_code' => $response_code,
            'response' => $body
        ]);

        if ($response_code !== 200) {
            $add_credit_description='برگشت وجه بدلیل خطا ';
            $payment_handler->add_credit($user_id, $price, $add_credit_description);
            $this->logger->log_error('DeepSeek API returned error status', [
                'status_code' => $response_code,
                'response' => $body
            ]);
            throw new Exception('خطا از سمت DeepSeek API. کد وضعیت: ' . $response_code);
        }

        $decoded_body = json_decode($body, true);

        if (empty($decoded_body['choices'][0]['message']['content'])) {
            $this->logger->log_error('Invalid API response structure', [
                'response_body' => $decoded_body
            ]);
            throw new Exception('پاسخ نامعتبر از API دریافت شد. ساختار پاسخ: ' . json_encode($decoded_body));
        }

        // تولیدی با فیلتر کدهای html
        // return sanitize_textarea_field($decoded_body['choices'][0]['message']['content']);
      
        // مستقیماً HTML تولیدی را بدون فیلتر بازگردان
        return $decoded_body['choices'][0]['message']['content'];
       
     
    }

    public function handle_unauthorized() {
        wp_send_json_error([
            'message' => 'برای استفاده از این سرویس باید وارد حساب کاربری خود شوید',
            'login_url' => wp_login_url()
        ], 401);
    }
    
    
    private function clean_api_response($response_content) {
        // حذف markdown code blocks
        $patterns = [
            '/^```json\s*/', // ابتدای json block
            '/\s*```$/', // انتهای json block  
            '/^```\s*/', // سایر code blocks
            '/\s*```$/',
        ];
        
        $cleaned_response = preg_replace($patterns, '', $response_content);
        
        // حذف فضاهای خالی اضافی
        $cleaned_response = trim($cleaned_response);
        
        // حذف کاراکترهای غیر قابل چاپ
        $cleaned_response = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned_response);
        
        return $cleaned_response;
    }     
}
