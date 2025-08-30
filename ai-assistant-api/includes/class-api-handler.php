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
            $price = $service_manager->get_service_price($service_id);
            
            $service_info = $service_manager->get_service($service_id);
            if ($service_info && isset($service_info['system_prompt'])) {
                $system_prompt = $service_info['system_prompt'];
                // استفاده از system_prompt
                error_log('System Prompt: ' . $system_prompt);
            } else {
                error_log('Service not found or system_prompt not set');
            }
            
            $prompt = $system_prompt . "\n\n" . $userData;
            
            if ($price === 0) {
            //    $all_services = get_option('ai_assistant_services', []);
                $price = $all_services[$service_id]['price'] ?? 0;
            }
            
            
            
            $payment_handler = AI_Assistant_Payment_Handler::get_instance();
            
        
            $this->validate_request($prompt, $service_id, $user_id, $price, $payment_handler);

            // کسر هزینه
            $payment_handler->deduct_credit($user_id, $price, $service_name);

            // ثبت لاگ
            $this->logger->log('Processing API request', [
                'user_id:' => $user_id,
                'service_id:' => $service_id,
                'price:' => $price,
                'prompt:' => $prompt
            ]);

            // فراخوانی API------------------------------------------------------
        //   $response = $this->call_deepseek_api($prompt);
            //-----------------------------------------------------------------


   

$json_string = ' 
{
  "title": "برنامه تغذیه‌ای بالینی",
  "sections": [
    {
      "title": "اخطار",
      "content": {
        "type": "paragraph",
        "text": "با توجه به شرایط خاص شما (پاراتیروئید و پیوند کلیه)، لطفاً قبل از شروع این رژیم غذایی با پزشک معالج خود مشورت کنید. این برنامه صرفاً جنبه پیشنهادی دارد و ممکن است نیاز به تنظیمات خاص برای شرایط شما داشته باشد."
      }
    },
    {
      "title": "۱. اطلاعات ضروری کاربر",
      "content": {
        "type": "list",
        "items": [
          {"label": "سن", "value": "36 سال"},
          {"label": "جنسیت", "value": "مرد"},
          {"label": "وزن و قد", "value": "81 کیلوگرم، 175 سانتی‌متر"},
          {"label": "بیماری‌ها/داروها", "value": "پاراتیروئید، پیوند کلیه"},
          {"label": "سطح فعالیت", "value": "کم"},
          {"label": "هدف", "value": "حفظ سلامتی"},
          {"label": "مدت زمان برنامه", "value": "این برنامه برای هفت روز طراحی شده است. با توجه به شرایط خاص شما، بهتر است پس از یک هفته با پزشک معالج خود برای ادامه یا تعدیل برنامه مشورت کنید."}
        ]
      }
    },
    {
      "title": "۲. تحلیل علمی",
      "content": {
        "type": "paragraph",
        "text": "با توجه به شرایط شما (پیوند کلیه و پاراتیروئید)، رژیم غذایی باید کنترل شده از نظر پروتئین، فسفر، پتاسیم و سدیم باشد. مطالعات نشان می‌دهند که بیماران پیوند کلیه نیاز به تنظیم دقیق این مواد مغذی دارند (Journal of Renal Nutrition, 2020). همچنین، سطح کلسیم و ویتامین D باید به دقت کنترل شود (Clinical Journal of the American Society of Nephrology, 2019). رژیم پیشنهادی با در نظر گرفتن این موارد طراحی شده است."
      }
    },
    {
      "title": "۳. نتیجه مورد انتظار",
      "content": {
        "type": "nested_list",
        "items": [
          {
            "label": "وضعیت وزن فعلی شما نسبت به محدوده‌ی ایده‌آل",
            "value": "شاخص توده بدنی (BMI) شما 26.4 است که در محدوده اضافه وزن قرار دارد. وزن ایده‌آل برای قد شما حدود 70-75 کیلوگرم است."
          },
          {
            "label": "نتیجه مورد انتظار بعد از یک دوره رعایت رژیم",
            "value": "با رعایت این رژیم غذایی به مدت یک هفته، انتظار می‌رود تعادل مواد مغذی در بدن شما بهبود یابد و احتمالاً مقداری از وزن اضافی کاهش یابد. اما با توجه به شرایط خاص شما، کاهش وزن باید بسیار تدریجی و تحت نظر پزشک باشد."
          }
        ]
      }
    },
    {
      "title": "۴. برنامه‌ی عملی",
      "content": [
        {
          "subtitle": "الف) کلیات برنامه:",
          "type": "list",
          "items": [
            {"label": "کالری روزانه", "value": "2000-2200 کالری"},
            {"label": "درشت‌مغذی‌ها", "value": "50٪ کربوهیدرات، 25٪ پروتئین، 25٪ چربی"},
            {"label": "مکمل‌ها (در صورت نیاز)", "value": "مکمل کلسیم و ویتامین D فقط با تجویز پزشک"}
          ]
        },
        {
          "subtitle": "ب) برنامه‌ی غذایی هفتگی:",
          "type": "table",
          "headers": ["روز", "صبحانه", "میان‌وعده صبح", "ناهار", "میان‌وعده عصر", "شام"],
          "rows": [
            ["روز اول", "نان سنگک 2 برش (80 گرم) + پنیر کم نمک 30 گرم + گردو 2 عدد + چای کم رنگ", "میوه فصل (سیب کوچک 1 عدد) + بادام 5 عدد", "خوراک مرغ با سبزیجات (100 گرم سینه مرغ + 1 فنجان سبزیجات بخارپز) + برنج قهوه‌ای نصف پیمانه", "ماست کم چرب (1 پیاله کوچک) + نان خشک 1 عدد", "ماهی قزل‌آلا 100 گرم + سیب زمینی آبپز کوچک 1 عدد + سالاد فصل با روغن زیتون"],
            ["جایگزین روز اول", "بلغور جو دوسر (نصف پیمانه پخته) + شیر کم چرب (1 لیوان) + کشمش 1 قاشق غذاخوری", "هویج کوچک 2 عدد + حمص 2 قاشق غذاخوری", "عدس پلو با مرغ (نصف پیمانه) + سالاد شیرازی", "پنیر کم نمک 30 گرم + نان سنگک 1 برش", "املت سبزیجات (2 تخم مرغ + سبزیجات معطر) + نان سنگک 1 برش"],
            ["روز دوم", "اوتمیل (نصف پیمانه جو پرک + 1 لیوان شیر کم چرب + دارچین) + موز کوچک نصف", "ماست کم چرب (1 پیاله کوچک) + گردو 2 عدد", "کباب تابه‌ای گوشت کم چرب (100 گرم) + برنج سفید نصف پیمانه + سبزی خوردن", "نخودچی 1 مشت کوچک + کشمش 1 قاشق غذاخوری", "سوپ جو با سبزیجات (1 کاسه متوسط) + نان سنگک 1 برش"],
            ["جایگزین روز دوم", "تخم مرغ آبپز 2 عدد + نان سنگک 1 برش + گوجه فرنگی 2 برش", "انجیر خشک 2 عدد + بادام 5 عدد", "خوراک لوبیا چیتی با سبزیجات (نصف پیمانه) + نان سنگک 1 برش", "پوره سیب زمینی کوچک (1 سیب زمینی کوچک) + روغن زیتون 1 قاشق چایخوری", "مرغ آبپز 100 گرم + پوره هویج (1 هویج متوسط) + نان سنگک 1 برش"],
            ["روز سوم", "پنیر کم نمک 30 گرم + نان سنگک 2 برش + خیار 1 عدد کوچک + چای کم رنگ", "میوه فصل (نارنگی 1 عدد متوسط) + پسته 10 عدد", "قورمه سبزی با لوبیا قرمز (نصف پیمانه) + برنج سفید نصف پیمانه", "شیر کم چرب (1 لیوان) + بیسکویت سبوس دار 2 عدد", "کته گشنیز با ماهی (100 گرم ماهی + نصف پیمانه برنج مخلوط با گشنیز)"],
            ["جایگزین روز سوم", "حلیم جو (1 کاسه کوچک) + شیره انگور 1 قاشق چایخوری", "کدو تنبل پخته (1 فنجان) + روغن زیتون 1 قاشق چایخوری", "خوراک بادمجان با گوشت چرخ کرده کم چرب (نصف پیمانه) + نان سنگک 1 برش", "انار نصف یک انار کوچک", "سینه بوقلمون گریل شده 100 گرم + پوره کدو حلوایی (1 فنجان) + نان سنگک 1 برش"],
            ["روز چهارم", "تخم مرغ نیمرو (1 عدد) + نان سنگک 1 برش + گوجه فرنگی 2 برش", "ماست کم چرب (1 پیاله کوچک) + نان خشک 1 عدد", "آبگوشت کم چرب (1 کاسه کوچک) + نان سنگک 1 برش", "میوه فصل (کیوی 1 عدد) + بادام 5 عدد", "ماهی سفید بخارپز 100 گرم + برنج قهوه‌ای نصف پیمانه + سبزیجات بخارپز"],
            ["جایگزین روز چهارم", "پنیر کم نمک 30 گرم + گردو 2 عدد + نان سنگک 1 برش + چای کم رنگ", "سینه مرغ آبپز 50 گرم + خیار 1 عدد کوچک", "لوبیا سبز پلو با مرغ (نصف پیمانه) + ماست کم چرب (نصف پیاله)", "توت خشک 1 قاشق غذاخوری + بادام 5 عدد", "املت سفیده تخم مرغ (3 سفیده) + نان سنگک 1 برش + سالاد فصل"],
            ["روز پنجم", "بلغور گندم (نصف پیمانه پخته) + شیر کم چرب (نصف لیوان) + عسل 1 قاشق چایخوری", "میوه فصل (انگور 10 دانه کوچک) + پسته 10 عدد", "کوفته قلقلی با سبزیجات (3 عدد کوچک) + برنج سفید نصف پیمانه", "دوغ کم نمک (1 لیوان) + نان خشک 1 عدد", "مرغ گریل شده 100 گرم + پوره سیب زمینی (1 سیب زمینی کوچک) + سالاد فصل"],
            ["جایگزین روز پنجم", "نان تست سبوس دار 1 برش + کره بادام زمینی 1 قاشق غذاخوری + موز نصف", "ماست کم چرب (1 پیاله کوچک) + گردو 2 عدد", "خوراک مرغ با قارچ (100 گرم مرغ + 1 فنجان قارچ) + نان سنگک 1 برش", "هندوانه (1 فنجان خرد شده)", "ماهی کبابی 100 گرم + برنج سفید نصف پیمانه + سبزیجات کبابی"],
            ["روز ششم", "پنیر کم نمک 30 گرم + نان سنگک 2 برش + گردو 2 عدد + چای کم رنگ", "میوه فصل (سیب کوچک 1 عدد) + بادام 5 عدد", "خوراک گوشت با سبزیجات (100 گرم گوشت کم چرب + 1 فنجان سبزیجات) + برنج قهوه‌ای نصف پیمانه", "ماست کم چرب (1 پیاله کوچک) + نان خشک 1 عدد", "سوپ مرغ با جو (1 کاسه متوسط) + نان سنگک 1 برش"],
            ["جایگزین روز ششم", "تخم مرغ آبپز 2 عدد + نان سنگک 1 برش + خیار 1 عدد کوچک", "هویج کوچک 2 عدد + حمص 2 قاشق غذاخوری", "عدس پلو با مرغ (نصف پیمانه) + سالاد شیرازی", "پنیر کم نمک 30 گرم + نان سنگک 1 برش", "املت سبزیجات (2 تخم مرغ + سبزیجات معطر) + نان سنگک 1 برش"],
            ["وعده آزاد", "انتخاب آزاد (ترجیحاً سالم) - پیشنهاد ما: صبحانه ایرانی کامل (نان، پنیر، سبزی، گردو)", "میوه فصل به انتخاب شما", "غذای مورد علاقه شما (در حد متعادل) - پیشنهاد ما: چلوکباب کوبیده 1 سیخ + سالاد", "آجیل خام به مقدار کم", "سوپ سبزیجات خانگی + نان سنگک"]
          ]
        }
      ]
    },
    {
      "title": "۵. دستور پخت غذاها",
      "content": {
        "type": "list",
        "items": [
          {
            "label": "خوراک مرغ با سبزیجات",
            "value": "مواد لازم: سینه مرغ 100 گرم، هویج 1 عدد، لوبیا سبز نصف فنجان، قارچ 5 عدد، روغن زیتون 1 قاشق چایخوری. روش تهیه: سبزیجات را خرد کنید. در تابه روغن زیتون را گرم کنید، مرغ را تفت دهید، سپس سبزیجات را اضافه کنید و با کمی آب بگذارید تا بپزد."
          },
          {
            "label": "کته گشنیز با ماهی",
            "value": "مواد لازم: برنج نصف پیمانه، گشنیز تازه 1 فنجان، ماهی قزل‌آلا 100 گرم، روغن زیتون 1 قاشق چایخوری. روش تهیه: برنج را با گشنیز خرد شده بپزید. ماهی را با روغن زیتون و کمی لیمو تفت دهید یا بخارپز کنید."
          },
          {
            "label": "سوپ جو با سبزیجات",
            "value": "مواد لازم: جو پرک نصف پیمانه، هویج 1 عدد، کرفس 1 ساقه، جعفری کمی، آب مرغ کم نمک 2 لیوان. روش تهیه: جو را با آب مرغ بپزید، سبزیجات خرد شده را اضافه کنید تا بپزد. در آخر جعفری خرد شده اضافه کنید."
          }
        ]
      }
    },
    {
      "title": "۶. هشدارها",
      "content": {
        "type": "list",
        "items": [
          "با توجه به پیوند کلیه، مصرف پروتئین باید کنترل شده باشد. از مصرف بیش از حد پروتئین خودداری کنید.",
          "مصرف نمک را به حداقل برسانید. از غذاهای فرآوری شده و پرنمک اجتناب کنید.",
          "با توجه به پاراتیروئید، سطح کلسیم و فسفر خون باید به دقت کنترل شود.",
          "در صورت هرگونه تغییر در وضعیت سلامتی، فوراً با پزشک معالج خود مشورت کنید."
        ]
      }
    },
    {
      "title": "۷. توصیه ها",
      "content": {
        "type": "list",
        "items": [
          "روزانه حداقل 6-8 لیوان آب مصرف کنید، مگر اینکه پزشک محدودیت مایعات تجویز کرده باشد.",
          "فعالیت بدنی سبک مانند پیاده‌روی روزانه 20-30 دقیقه را در برنامه خود بگنجانید.",
          "وعده‌های غذایی را به صورت منظم و در ساعت‌های مشخص مصرف کنید.",
          "از مصرف الکل و سیگار به شدت پرهیز کنید.",
          "استرس خود را مدیریت کنید، زیرا بر سلامت کلیه‌ها تأثیر می‌گذارد."
        ]
      }
    },
    {
      "title": "۸. منابع",
      "content": {
        "type": "list",
        "items": [
          "Journal of Renal Nutrition (2020). Nutritional Management of Kidney Transplant Recipients.",
          "Clinical Journal of the American Society of Nephrology (2019). Calcium and Phosphate Management in Chronic Kidney Disease.",
          "WHO Guidelines on Sodium Intake (2021).",
          "ADA Nutrition Recommendations for Kidney Disease (2022)."
        ]
      }
    }
  ],
  "footer": "aidastyar.com"
}
 ';     
         
            
            
            
            
           $response  = $json_string ;







            $history_manager = AI_Assistant_History_Manager::get_instance();
            // ذخیره در تاریخچه
            $saved = $history_manager->save_history($user_id, $service_id , $service_name , $userData , $response);
            


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

    private function validate_request($prompt, $service_id, $user_id, $price, $payment_handler) {
        if (!is_user_logged_in()) {
            throw new Exception('برای استفاده از این سرویس باید وارد حساب کاربری خود شوید');
        }
        
        
        if (empty($prompt) || empty($service_id)) {
            throw new Exception('پارامترهای ورودی نامعتبر هستند');
        }

        if (!$payment_handler->has_enough_credit($user_id, $price)) {
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
                'temperature' => 0.9,
                'max_tokens' => 5000
            ]),
            'timeout' => 120 ,
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
}
