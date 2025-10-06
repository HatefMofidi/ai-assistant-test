<?php
// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/services/diet/template-parts/form.php
$theme_assets = get_stylesheet_directory_uri();
?>
<form id="multi-step-form" class="ai-multistep-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
    <input type="hidden" name="action" value="submit_diet_form">
    <?php wp_nonce_field('diet_form_nonce', 'diet_form_security'); ?>
    
    <div id="header-container">
        <div id="step-counter"><span id="current-step">1</span>/<span id="total-steps">19</span></div>
        <button type="button" id="back-button">›</button>
        
        <div id="header-logo" onclick="window.location.href='<?php echo home_url(); ?>'">
            AiDASTYAR
        </div>
    </div>
    
    <div id="progress-bar-container">
        <div id="progress-bar"></div>
    </div>

    <!-- Step 1: Gender Selection -->
    <div id="gender-selection-step" class="step active">
        <h1 id="form-title">سیستم هوشمند رژیم غذایی هوش مصنوعی</h1>
        <h2>جنسیت خود را انتخاب کنید</h2>
        <div id="gender-selection">
            <div class="gender-option" data-gender="male"><img src="<?php echo $theme_assets; ?>/assets/images/webp/male.webp" alt="مرد"></div>
            <div class="gender-option" data-gender="female"><img src="<?php echo $theme_assets; ?>/assets/images/webp/female.webp" alt="زن"></div>
        </div>
    
        <div id="terms-checkbox" class="checkbox-container terms-combined">
            <input type="checkbox" id="confirm-terms" class="real-checkbox">
            <label for="confirm-terms" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">شرایط و قوانین را می‌پذیرم</span>
            </label>
            <div class="terms-box">
                <ul class="terms-list">
                    <li>اطلاعات سلامت و شخصی من، با حداکثر امنیت و مطابق قوانین محرمانگی، نزد این سامانه محفوظ خواهد ماند.</li>
                    <li>توصیه‌های این سیستم توسط پیشرفته‌ترین الگوریتم‌های هوش مصنوعی ارائه می‌شود، اما جایگزین تشخیص پزشک نیست.</li>
                    <li>مسئولیت نهایی تصمیمات سلامت و استفاده از این توصیه‌ها بر عهده خودم است.</li>
                    <li>متعهد می‌شوم برای مسائل پزشکی مهم، حتماً با پزشک معتمدم مشورت کنم.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Step 2: Personal Information -->
    <div id="personal-info-step" class="step">
        <h2>لطفا نام و نام خانوادگی را وارد کنید</h2>
        
        <div class="input-container" style="margin-bottom: 15px;">
            <input type="text" id="first-name-input" dir="rtl" placeholder=" ">
            <span id="first-name-display"></span>
        </div>
        
        <div class="input-container">
            <input type="text" id="last-name-input" dir="rtl" placeholder=" ">
            <span id="last-name-display"></span>
        </div>        
        
        <!-- Animated Illustration Container -->
        <div class="name-input-illustration">
            <div class="floating-pen"></div>
            <div class="floating-document"></div>
        </div>        
    </div>

    <!-- Step 2: Goal Selection -->
    <div id="goal-selection-step" class="step">
        <h2>هدف شما از این برنامه غذایی چیست؟</h2>
        <p class="step-description">لطفاً هدف اصلی خود را از دنبال کردن این رژیم انتخاب کنید</p>
        
        <div id="goal-selection">
            <div class="goal-option" data-goal="weight-loss">
                <div class="goal-icon" data-meals="2">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/lose-weight-min.png" alt="کاهش وزن">
                </div>
                <div class="goal-details">
                    <h3>کاهش وزن</h3>
                    <p>برنامه‌ای برای رسیدن به وزن ایده‌آل و سالم</p>
                </div>
            </div>
            
            <div class="goal-option" data-goal="weight-gain">
                <div class="goal-icon" data-meals="2">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/gain-weight-min.png" alt="افزایش وزن">
                </div>
                <div class="goal-details">
                    <h3>افزایش وزن سالم</h3>
                    <p>برنامه‌ای برای افزایش وزن اصولی و عضله‌سازی</p>
                </div>
            </div>
            
            <div class="goal-option" data-goal="fitness">
                <div class="goal-icon" data-meals="2">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/stay-fit-min.png" alt="حفظ سلامت">
                </div>
                <div class="goal-details">
                    <h3>حفظ سلامت و تناسب</h3>
                    <p>برنامه‌ای برای حفظ وزن فعلی و بهبود سلامت عمومی</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Age Input -->
    <div id="age-input-step" class="step">
        <h2>سن شما چند سال است؟</h2>
        <div class="input-container">
            <input type="text" inputmode="numeric" id="age-input">
            <span id="age-display">0 سال</span>
        </div>
        <div id="age-validation-container">
            <p id="age-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/age-min.png" width="30" height="30" alt="سن">
                    <div class="info-text">
                        <span class="first-line">سن شما را می‌پرسیم تا برنامه شخصی شما را ایجاد کنیم.</span>
                        <span class="second-line">افراد مسن‌تر نسبت به افراد جوان‌تر با همان شاخص توده بدنی (BMI)، معمولاً چربی بدن بیشتری دارند</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Height Input -->
    <div id="height-input-step" class="step">
        <h2>قد شما چقدر است؟</h2>
        <div class="input-container">
            <input type="text" inputmode="numeric" id="height-input">
            <span id="height-display">0 سانتی‌متر</span>
        </div>
        <div id="height-validation-container">
            <p id="height-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/height-min.png" width="30" height="30" alt="قد">
                    <div class="info-text">
                        <span class="first-line">محاسبه شاخص توده بدنی شما</span>
                        <span class="second-line">شاخص توده بدنی (BMI) به طور گسترده به عنوان یک معیار برای سنجش خطر ابتلا یا شیوع برخی مشکلات سلامتی مورد استفاده قرار می‌گیرد</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 5: Weight Input -->
    <div id="weight-input-step" class="step">
        <h2>وزن شما چقدر است؟</h2>
        <div class="input-container">
            <input type="text" inputmode="numeric" id="weight-input">
            <span id="weight-display">0 کیلوگرم</span>
        </div>
        <div id="weight-validation-container">
            <p id="weight-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div id="bmi-result-container">
                <div class="bmi-info">
                    <h3>شاخص توده بدنی (BMI) شما: <span id="bmi-value">0</span></h3>
                    <p id="bmi-category" class="bmi-category"></p>
                </div>
                <div class="bmi-scale-container">
                    <div class="bmi-scale-labels">
                        <span>کمبود وزن</span>
                        <span>نرمال</span>
                        <span>اضافه وزن</span>
                        <span>چاق</span>
                        <span>چاقی شدید</span>
                    </div>
                    <div class="bmi-scale">
                        <div id="bmi-indicator" class="bmi-indicator"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 6: Target Weight -->
    <div id="target-weight-step" class="step">
        <h2>وزن هدف شما چقدر است؟</h2>
        <div class="input-container">
            <input type="text" inputmode="numeric" id="target-weight-input">
            <span id="target-weight-display">0 کیلوگرم</span>
        </div>
        <div id="target-weight-validation-container">
            <p id="targetWeight-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/gain-weight-min.png" width="30" height="30" alt="وزن هدف">
                    <div class="info-text">
                        <span class="first-line">وزن هدف شما را می‌پرسیم تا برنامه مناسب برای رسیدن به آن را طراحی کنیم.</span>
                        <span class="second-line">لطفاً وزن واقع‌بینانه‌ای را وارد کنید که با قد و ساختار بدنی شما تناسب داشته باشد</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 7: Goal Weight Display -->
    <div id="goal-weight-display" class="step">
        <div class="step7-image-container">
            <div class="goal-title-container">
                <h2 class="goal-title" id="goal-title-text">هدف: در حال بارگذاری...</h2>
            </div>
            <div class="weight-display-container">
                <div class="weight-display-box current-weight">
                    <div class="weight-value">${state.formData.weight || 0}</div>
                    <div class="weight-unit">کیلوگرم</div>
                    <div class="weight-label">وزن فعلی شما</div>
                </div>
                <div class="weight-display-box target-weight">
                    <div class="weight-value">${state.formData.targetWeight || 0}</div>
                    <div class="weight-unit">کیلوگرم</div>
                    <div class="weight-label">وزن هدف شما</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 9: Chronic Conditions (بیماری‌های مزمن اصلی) -->
    <div id="chronic-conditions-step" class="step checkbox-step-container">
        <h2>بیماری‌های مزمن و زمینه‌ای</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="chronic-none" class="real-checkbox">
            <label for="chronic-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچگونه بیماری مزمن یا زمینه‌ای ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="chronic-conditions-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <!-- اختلالات متابولیک -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">اختلالات متابولیک</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-diabetes" class="real-checkbox">
                <label for="chronic-diabetes" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">دیابت</span>
                </label>
            </div>
            
            <!-- جزئیات دیابت -->
            <div id="chronic-diabetes-details" style="display: none; margin: 15px 0 20px 25px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-right: 3px solid #4CAF50;">
                <h4 style="margin: 0 0 15px 0; color: #333; font-size: 14px;">نوع دیابت:</h4>
                <div class="diabetes-options" style="display: flex; flex-direction: column; gap: 10px;">
                    <div class="diabetes-option" data-value="type1">
                        <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center;">💉</div>
                        <div class="diabetes-text" style="display: inline-block; margin-right: 8px;">دیابت نوع 1</div>
                    </div>
                    <div class="diabetes-option" data-value="type2">
                        <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center;">🩺</div>
                        <div class="diabetes-text" style="display: inline-block; margin-right: 8px;">دیابت نوع 2</div>
                    </div>
                    <div class="diabetes-option" data-value="gestational">
                        <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center;">🤰</div>
                        <div class="diabetes-text" style="display: inline-block; margin-right: 8px;">دیابت بارداری</div>
                    </div>
                    <div class="diabetes-option" data-value="prediabetes">
                        <div class="diabetes-icon" style="display: inline-block; width: 24px; text-align: center;">⚠️</div>
                        <div class="diabetes-text" style="display: inline-block; margin-right: 8px;">پیش‌دیابت</div>
                    </div>
                </div>
                
                <!-- اطلاعات تکمیلی -->
                <div id="chronic-diabetes-additional" style="margin-top: 15px; display: none;">
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">میزان قند خون ناشتا (اختیاری):</label>
                        <input type="number" id="chronic-fasting-blood-sugar" placeholder="مثلاً 120" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                        <span style="margin-right: 5px; font-size: 13px;">mg/dL</span>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">HbA1c (اختیاری):</label>
                        <input type="number" id="chronic-hba1c-level" step="0.1" placeholder="مثلاً 6.5" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                        <span style="margin-right: 5px; font-size: 13px;">%</span>
                    </div>
                </div>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hypertension" class="real-checkbox">
                <label for="chronic-hypertension" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">فشار خون بالا</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-cholesterol" class="real-checkbox">
                <label for="chronic-cholesterol" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کلسترول یا تری گلیسیرید بالا</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-fatty-liver" class="real-checkbox">
                <label for="chronic-fatty-liver" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کبد چرب</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-insulin-resistance" class="real-checkbox">
                <label for="chronic-insulin-resistance" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">مقاومت به انسولین</span>
                </label>
            </div>
    
            <!-- اختلالات هورمونی -->
            <h3 class="diffrent-category-titles">اختلالات هورمونی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hypothyroidism" class="real-checkbox">
                <label for="chronic-hypothyroidism" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کم کاری تیروئید (هیپوتیروئیدی)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hyperthyroidism" class="real-checkbox">
                <label for="chronic-hyperthyroidism" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">پرکاری تیروئید (هیپرتیروئیدی)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-hashimoto" class="real-checkbox">
                <label for="chronic-hashimoto" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">هاشیموتو (التهاب خودایمنی تیروئید)</span>
                </label>
            </div>
            
            <div class="checkbox-container female-only">
                <input type="checkbox" id="chronic-pcos" class="real-checkbox">
                <label for="chronic-pcos" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سندرم تخمدان پلی کیستیک (PCOS)</span>
                </label>
            </div>
            
            <div class="checkbox-container female-only">
                <input type="checkbox" id="chronic-menopause" class="real-checkbox">
                <label for="chronic-menopause" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">یائسگی یا پیش یائسگی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-cortisol" class="real-checkbox">
                <label for="chronic-cortisol" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">مشکلات کورتیزول (استرس مزمن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-growth" class="real-checkbox">
                <label for="chronic-growth" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">اختلال هورمون رشد</span>
                </label>
            </div>
    
            <!-- سایر بیماری‌های مزمن -->
            <h3 class="diffrent-category-titles">سایر بیماری‌های مزمن</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-kidney" class="real-checkbox">
                <label for="chronic-kidney" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری کلیوی مزمن</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-heart" class="real-checkbox">
                <label for="chronic-heart" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری قلبی عروقی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="chronic-autoimmune" class="real-checkbox">
                <label for="chronic-autoimmune" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری خودایمنی</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Step 10: Digestive Conditions (مرحله جدید - ادغام مشکلات گوارشی و عدم تحمل‌ها) -->
    <div id="digestive-conditions-step" class="step checkbox-step-container">
        <h2>مشکلات گوارشی و عدم تحمل‌های غذایی</h2>
        <p class="step-description">لطفاً مشکلات گوارشی و عدم تحمل‌های غذایی خود را انتخاب کنید</p>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="digestive-none" class="real-checkbox">
            <label for="digestive-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچگونه مشکل گوارشی یا عدم تحمل غذایی ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="digestive-conditions-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <!-- بیماری‌های گوارشی ساختاری -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">🩺 بیماری‌های گوارشی ساختاری</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-ibs" class="real-checkbox">
                <label for="digestive-ibs" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سندرم روده تحریک‌پذیر (IBS)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-ibd" class="real-checkbox">
                <label for="digestive-ibd" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری التهابی روده (کرون یا کولیت اولسراتیو)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-gerd" class="real-checkbox">
                <label for="digestive-gerd" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">ریفلاکس معده-مروی (GERD)</span>
                </label>
            </div>
    
            <!-- علائم و مشکلات عملکردی -->
            <h3 class="diffrent-category-titles">🌀 علائم و مشکلات عملکردی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-bloating" class="real-checkbox">
                <label for="digestive-bloating" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">نفخ یا گاز معده</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-pain" class="real-checkbox">
                <label for="digestive-pain" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">درد یا گرفتگی معده</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-heartburn" class="real-checkbox">
                <label for="digestive-heartburn" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سوزش سر دل یا ترش کردن</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-constipation" class="real-checkbox">
                <label for="digestive-constipation" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">یبوست مزمن</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-diarrhea" class="real-checkbox">
                <label for="digestive-diarrhea" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">اسهال مزمن</span>
                </label>
            </div>
    
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-fullness" class="real-checkbox">
                <label for="digestive-fullness" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سیری زودرس</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-nausea" class="real-checkbox">
                <label for="digestive-nausea" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حالت تهوع</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-slow-digestion" class="real-checkbox">
                <label for="digestive-slow-digestion" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">هضم کند غذا</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-indigestion" class="real-checkbox">
                <label for="digestive-indigestion" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سوء هاضمه مزمن</span>
                </label>
            </div>
    
            <!-- عفونت‌ها و مشکلات خاص -->
            <h3 class="diffrent-category-titles">🦠 عفونت‌ها و مشکلات خاص</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="digestive-helicobacter" class="real-checkbox">
                <label for="digestive-helicobacter" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عفونت هلیکوباکتر پیلوری (H. Pylori)</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Step 10: Surgery History -->
    <div id="surgery-step" class="step checkbox-step-container">
        <h2>سابقه جراحی و اقدامات پزشکی</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="surgery-none" class="real-checkbox">
            <label for="surgery-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچگونه سابقه جراحی ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="surgery-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <!-- 🔪 جراحی‌های گوارشی و متابولیک -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">🔪 جراحی‌های گوارشی و متابولیک</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-metabolic" class="real-checkbox">
                <label for="surgery-metabolic" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی متابولیک (بایپس معده، اسلیو)</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-gallbladder" class="real-checkbox">
                <label for="surgery-gallbladder" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی کیسه صفرا</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-intestine" class="real-checkbox">
                <label for="surgery-intestine" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی روده</span>
                </label>
            </div>
            
            <!-- ❤️ جراحی‌های عمده و ارگان‌ها -->
            <h3 class="diffrent-category-titles">❤️ جراحی‌های عمده و ارگان‌ها</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-thyroid" class="real-checkbox">
                <label for="surgery-thyroid" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی تیروئید/پاراتیروئید</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-pancreas" class="real-checkbox">
                <label for="surgery-pancreas" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی لوزالمعده (پانکراس)</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-heart" class="real-checkbox">
                <label for="surgery-heart" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی قلب</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-kidney" class="real-checkbox">
                <label for="surgery-kidney" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">پیوند کلیه</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="surgery-liver" class="real-checkbox">
                <label for="surgery-liver" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">پیوند کبد</span>
                </label>
            </div>
            
            <!-- 🎗️ سرطان و جراحی‌های مرتبط -->
            <h3 class="diffrent-category-titles">🎗️ سرطان و جراحی‌های مرتبط</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="cancer-history" class="real-checkbox">
                <label for="cancer-history" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">سابقه سرطان (همراه جزئیات نوع و درمان)</span>
                </label>
            </div>
            
            <!-- جزئیات سرطان -->
            <div id="cancer-details" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-right: 3px solid #e91e63;">
                <!-- وضعیت درمان -->
                <h4 style="margin: 0 0 15px 0; color: #333; font-size: 14px;">وضعیت درمان:</h4>
                <div class="cancer-options" style="display: flex; flex-direction: column; gap: 10px;">
                    <div class="cancer-option" data-value="chemo">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">💊</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">شیمی درمانی</div>
                    </div>
                    <div class="cancer-option" data-value="radio">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">☢️</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پرتو درمانی</div>
                    </div>
                    <div class="cancer-option" data-value="surgery">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🔪</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">اخیراً جراحی شده‌ام</div>
                    </div>
                    <div class="cancer-option" data-value="finished">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">✅</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">درمانم تمام شده</div>
                    </div>
                </div>
            
                <!-- نوع سرطان -->
                <h4 style="margin: 20px 0 10px 0; color: #333; font-size: 14px;">نوع سرطان:</h4>
                <div class="cancer-options" style="display: flex; flex-direction: column; gap: 10px;">
                    <div class="cancer-option" data-value="breast">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🎀</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پستان</div>
                    </div>
                    <div class="cancer-option" data-value="colon">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🩸</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">روده</div>
                    </div>
                    <div class="cancer-option" data-value="prostate">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">👨</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">پروستات</div>
                    </div>
                    <div class="cancer-option" data-value="lung">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🫁</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">ریه</div>
                    </div>
                    <div class="cancer-option" data-value="blood">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">🩸</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">خون</div>
                    </div>
                    <div class="cancer-option" data-value="other">
                        <div class="cancer-icon" style="display: inline-block; width: 24px; text-align: center;">❓</div>
                        <div class="cancer-text" style="display: inline-block; margin-right: 8px;">سایر</div>
                    </div>
                </div>
            </div>
    
            <!-- جراحی‌های زنان -->
            <h3 class="diffrent-category-titles female-only">جراحی‌های زنان</h3>
            <div class="checkbox-container female-only">
                <input type="checkbox" id="surgery-gynecology" class="real-checkbox">
                <label for="surgery-gynecology" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">جراحی‌های زنان</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Step 11: Water Intake -->
    <div id="water-intake-step" class="step">
        <h2>به طور میانگین چه مقدار آب در روز می‌نوشید؟</h2>
        <div id="water-intake-container">
            <div class="water-cups-container">
                <!-- ردیف 1 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="1"></div>
                    <div class="water-cup" data-amount="2"></div>
                    <div class="water-cup" data-amount="3"></div>
                    <div class="water-cup" data-amount="4"></div>
                    <div class="water-cup" data-amount="5"></div>
                    <div class="water-cup" data-amount="6"></div>
                </div>
                <!-- ردیف 2 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="7"></div>
                    <div class="water-cup" data-amount="8"></div>
                    <div class="water-cup" data-amount="9"></div>
                    <div class="water-cup" data-amount="10"></div>
                    <div class="water-cup" data-amount="11"></div>
                    <div class="water-cup" data-amount="12"></div>
                </div>
                <!-- ردیف 3 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="13"></div>
                    <div class="water-cup" data-amount="14"></div>
                    <div class="water-cup" data-amount="15"></div>
                    <div class="water-cup" data-amount="16"></div>
                    <div class="water-cup" data-amount="17"></div>
                    <div class="water-cup" data-amount="18"></div>
                </div>
                <!-- ردیف 4 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="19"></div>
                    <div class="water-cup" data-amount="20"></div>
                    <div class="water-cup" data-amount="21"></div>
                    <div class="water-cup" data-amount="22"></div>
                    <div class="water-cup" data-amount="23"></div>
                    <div class="water-cup" data-amount="24"></div>
                </div>
                <!-- ردیف 5 -->
                <div class="water-cup-row">
                    <div class="water-cup" data-amount="25"></div>
                    <div class="water-cup" data-amount="26"></div>
                    <div class="water-cup" data-amount="27"></div>
                    <div class="water-cup" data-amount="28"></div>
                    <div class="water-cup" data-amount="29"></div>
                    <div class="water-cup" data-amount="30"></div>
                </div>
            </div>

            <div class="water-amount-display">
                <div id="water-amount-text" class="water-amount-text">
                    <span id="water-amount">0</span> لیوان در روز 
                    <span class="water-liter">(≈<span id="water-liter">0</span> لیتر)</span>
                </div>
                <div id="water-dont-know-text" class="dont-know-text" style="display:none;">
                    مقدار آب مصرفی مشخص نیست
                </div>
            </div>
            
            <div class="dont-know-container">
                <div class="checkbox-container first-option stand-alone-none">
                    <input type="checkbox" id="water-dont-know" class="real-checkbox">
                    <label for="water-dont-know" class="checkbox-label">
                        <span class="check-icon"></span>
                        <span class="label-text">نمی‌دانم / مطمئن نیستم</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <div id="activity-selection-step" class="step">
        <h2>میزان فعالیت روزانه شما چقدر است؟</h2>
        <p class="step-description">لطفاً سطح فعالیت روزمره خود را بر اساس شغل و فعالیت‌های معمول روزانه انتخاب کنید</p>
        
        <div id="activity-selection">
            <div class="activity-option" data-activity="very-low">
                <div class="activity-icon">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/without-activity-min.png" alt="فعالیت خیلی کم">
                </div>
                <div class="activity-details">
                    <h3>خیلی کم (بی‌تحرک)</h3>
                    <p>بیشتر وقت پشت میز یا در خانه، تحرک بسیار کم</p>
                    <span class="activity-examples">(پشت میز نشینی، کارمند اداری، خانه‌دار با تحرک کم)</span>
                </div>
            </div>
            
            <div class="activity-option" data-activity="low">
                <div class="activity-icon">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/alittle-activity-min.png" alt="فعالیت کم">
                </div>
                <div class="activity-details">
                    <h3>کم (فعالیت سبک)</h3>
                    <p>کارهای سبک خانه، پیاده‌روی کوتاه، ایستادن متوسط</p>
                    <span class="activity-examples">(معلم، منشی، فروشنده با تحرک محدود)</span>
                </div>
            </div>
            
            <div class="activity-option" data-activity="medium">
                <div class="activity-icon">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/middle-activity-min.png" alt="فعالیت متوسط">
                </div>
                <div class="activity-details">
                    <h3>متوسط (فعالیت متوسط)</h3>
                    <p>کارهایی که نیاز به راه رفتن و حرکت مداوم دارد</p>
                    <span class="activity-examples">(فروشندگی، پرستاری، راننده تاکسی، خدمات رسانی)</span>
                </div>
            </div>
            
            <div class="activity-option" data-activity="high">
                <div class="activity-icon">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/alot-activity-min.png" alt="فعالیت زیاد">
                </div>
                <div class="activity-details">
                    <h3>زیاد (فعالیت شدید)</h3>
                    <p>کار فیزیکی سخت که بیشتر روز نیاز به فعالیت بدنی دارد</p>
                    <span class="activity-examples">(کارگر ساختمانی، کشاورز، مکانیک، باربری)</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Step 13: Weekly Exercise Activity - نسخه با آیکون فونت -->
    <div id="exercise-activity-step" class="step">
        <h2>فعالیت ورزشی هفتگی شما چقدر است؟</h2>
        <p class="step-description">لطفاً میزان و شدت فعالیت ورزشی منظم خود در هفته را انتخاب کنید</p>
        
        <div id="exercise-selection">
            <div class="exercise-option" data-exercise="none">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">🚫</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>هیچ ورزشی نمی‌کنم</h3>
                    <p>بدون فعالیت ورزشی منظم در هفته</p>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="light">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">🚶‍♂️</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>سبک</h3>
                    <p>۱-۲ روز در هفته، کمتر از ۳۰ دقیقه</p>
                    <span class="exercise-examples">(پیاده‌روی آرام، یوگا سبک، حرکات کششی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="medium">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">🏃‍♂️</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>متوسط</h3>
                    <p>۳-۴ روز در هفته، ۳۰-۶۰ دقیقه</p>
                    <span class="exercise-examples">(دویدن سبک، شنا، بدنسازی متوسط، ورزش‌های هوازی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="high">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">💪</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>زیاد</h3>
                    <p>۵-۶ روز در هفته یا بیشتر، ۶۰+ دقیقه</p>
                    <span class="exercise-examples">(تمرین سنگین، کراس فیت، ورزش‌های رقابتی)</span>
                </div>
            </div>
            
            <div class="exercise-option" data-exercise="professional">
                <!--<div class="exercise-icon">-->
                <!--    <span class="exercise-icon-text">🏆</span>-->
                <!--</div>-->
                <div class="exercise-details">
                    <h3>ورزشکار حرفه‌ای</h3>
                    <p>تمرین سنگین روزانه و برنامه‌ریزی شده</p>
                    <span class="exercise-examples">(ورزشکاران حرفه‌ای، بدنسازان، ورزش‌های قهرمانی)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 15: Diet Style Selection -->
    <div id="diet-style-step" class="step checkbox-step-container">
        <h2>سبک غذایی اصلی شما چیست؟</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="diet-style-none" class="real-checkbox">
            <label for="diet-style-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">سبک غذایی خاصی ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="diet-style-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <div class="checkbox-container">
                <input type="checkbox" id="diet-style-vegetarian" class="real-checkbox">
                <label for="diet-style-vegetarian" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">گیاهخواری (Vegetarian)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="diet-style-vegan" class="real-checkbox">
                <label for="diet-style-vegan" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">وگان (Vegan - بدون هیچ محصول حیوانی)</span>
                </label>
            </div>
        </div>
    </div>

    <div id="food-limitations-step" class="step checkbox-step-container">
        <h2>چه محدودیت‌های غذایی دارید؟</h2>
        <p class="step-description">شامل محدودیت‌های پزشکی (حساسیت، عدم تحمل) و ترجیحات شخصی</p>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="limitations-none" class="real-checkbox">
            <label for="limitations-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچ محدودیت غذایی ندارم</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="food-limitations-selection" class="checkbox-selection-container checkbox-list-container scrollable-container">
            <!-- محدودیت‌های پزشکی -->
            <h3 class="diffrent-category-titles" style="margin-top: 0px;">🩺 محدودیت‌های پزشکی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-celiac" class="real-checkbox">
                <label for="limitation-celiac" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">بیماری سلیاک (حساسیت به گلوتن)</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-lactose" class="real-checkbox">
                <label for="limitation-lactose" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدم تحمل لاکتوز</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-seafood-allergy" class="real-checkbox">
                <label for="limitation-seafood-allergy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حساسیت به غذاهای دریایی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-eggs-allergy" class="real-checkbox">
                <label for="limitation-eggs-allergy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حساسیت به تخم‌مرغ</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-nuts-allergy" class="real-checkbox">
                <label for="limitation-nuts-allergy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">حساسیت به آجیل و مغزها</span>
                </label>
            </div>
    
            <!-- ترجیحات شخصی -->
            <h3 class="diffrent-category-titles">🌱 ترجیحات شخصی</h3>
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-no-seafood" class="real-checkbox">
                <label for="limitation-no-seafood" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدم مصرف غذاهای دریایی</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-no-redmeat" class="real-checkbox">
                <label for="limitation-no-redmeat" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدم مصرف گوشت قرمز</span>
                </label>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" id="limitation-no-dairy" class="real-checkbox">
                <label for="limitation-no-dairy" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">عدم مصرف لبنیات</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Step 18: Terms Agreement -->
    <div id="terms-agreement-step" class="step">
        <h2>توافق‌نامه استفاده از خدمات Aidastyar</h2>
        <div id="terms-agreement-container">
            <div class="terms-agreement-content">
                <div class="terms-section">
                    <p>با ثبت‌نام، پرداخت یا استفاده از هر بخش خدمات Aidastyar، کاربر با مفاد این توافق‌نامه به‌صورت کامل، آگاهانه و غیرقابل رجوع موافقت می‌نماید.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۱. اهلیت قانونی و مسئولیت استفاده</h3>
                    <p>کاربر تأیید می‌کند بالای ۱۸ سال سن دارد و از نظر قانونی اختیار استفاده از خدمات را دارد.</p>
                    <p>در صورت استفاده افراد زیر ۱۸ سال، ثبت‌نام و پرداخت باید توسط والدین یا قیم قانونی انجام شود و مسئولیت کامل متوجه ایشان خواهد بود.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۲. ماهیت خدمات و حدود مسئولیت</h3>
                    <p>رژیم‌ها به‌صورت خودکار و فقط بر اساس داده‌های واردشده توسط کاربر، از طریق الگوریتم‌های هوش مصنوعی تولید می‌شوند.</p>
                    <p>این خدمات به هیچ‌وجه جایگزین مشاوره پزشکی یا تغذیه‌ای نیستند.</p>
                    <p>کاربر می‌پذیرد که ممکن است رژیم تولیدشده با وضعیت بدنی، پزشکی یا نیازهای خاص وی ناسازگار باشد.</p>
                    <p>کاربر موظف است در صورت داشتن بیماری خاص، مصرف دارو، بارداری یا وضعیت حساس پزشکی، پیش از استفاده با پزشک مشورت نماید.</p>
                    <div class="disclaimer-box">
                        <strong>سلب مسئولیت محدود:</strong>
                        <p>Aidastyar در چارچوب قوانین جاری جمهوری اسلامی ایران، تنها مسئول ارائه خدمات به شکلی است که در شرح آن آمده است و مسئولیتی در قبال نتایج شخصی، پزشکی، روحی یا روانی ناشی از اجرای رژیم ندارد، مگر در مواردی که بر اساس حکم قطعی مرجع قضایی، تقصیر مستقیم Aidastyar اثبات شود.</p>
                    </div>
                </div>
    
                <div class="terms-section">
                    <h3>۳. صحت اطلاعات</h3>
                    <p>کلیه اطلاعات واردشده باید دقیق و واقعی باشد.</p>
                    <p>مسئولیت پیامدهای ناشی از اطلاعات ناقص یا نادرست، کاملاً با کاربر است.</p>
                    <p>تغییر وضعیت سلامت باید به‌صورت فوری در سامانه ثبت شود؛ در غیر این‌صورت، رژیم معتبر نخواهد بود.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۴. تضمین نتایج و به‌روزرسانی</h3>
                    <p>هیچ تضمینی درباره کاهش وزن، بهبود بیماری یا موفقیت قطعی رژیم وجود ندارد.</p>
                    <p>الگوریتم‌ها ممکن است به‌روزرسانی شوند و خروجی‌های متفاوتی تولید کنند.</p>
                    <p>در صورت تغییر شرایط بدنی یا بیماری، استفاده از رژیم قبلی توصیه نمی‌شود.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۵. پرداخت، انصراف و بازگشت وجه</h3>
                    <p>پرداخت هزینه به‌منزله درخواست قطعی تولید رژیم تلقی می‌شود.</p>
                    <p>چنانچه رژیم هنوز تولید نشده باشد، کاربر مطابق ماده ۳۷ قانون تجارت الکترونیکی، تا ۷ روز پس از پرداخت امکان انصراف دارد.</p>
                    <p>برای اعمال انصراف، کاربر باید از طریق پنل کاربری اقدام کند.</p>
                    <p>در صورت شروع تولید رژیم (حتی در کمتر از ۷ روز)، حق انصراف از بین می‌رود.</p>
                    <p>وجه در صورت انصراف، ظرف حداکثر ۷۲ ساعت کاری به حساب اولیه بازگردانده می‌شود.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۶. مالکیت فکری</h3>
                    <p>تمامی الگوریتم‌ها، محتواها، فایل‌های رژیم و ساختارهای سامانه متعلق به Aidastyar است.</p>
                    <p>بازنشر، فروش، یا استفاده تجاری از محتوای دریافتی بدون مجوز کتبی ممنوع می‌باشد.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۷. اطلاعات شخصی و محرمانگی</h3>
                    <p>اطلاعات کاربران با روش‌های رمزنگاری ذخیره شده و تنها برای تیم فنی مجاز قابل‌دسترسی است.</p>
                    <p>در موارد زیر اطلاعات کاربر ممکن است افشا شود:</p>
                    <ul>
                        <li>با حکم یا دستور مقام قضایی</li>
                        <li>در موارد بررسی تخلف یا حملات امنیتی</li>
                        <li>در صورت انتقال مالکیت سامانه</li>
                    </ul>
                </div>
    
                <div class="terms-section">
                    <h3>۸. محدودیت استفاده و تخلفات</h3>
                    <p>استفاده فقط برای اهداف قانونی مجاز است.</p>
                    <p>ورود اطلاعات جعلی، استفاده برای آسیب جسمی/روانی یا انتشار محتوای دریافتی ممنوع است.</p>
                    <p>در صورت تخلف، حساب کاربر مسدود شده و امکان پیگرد قانونی وجود دارد.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۹. پشتیبانی و ارتباط رسمی</h3>
                    <p>ارتباط رسمی فقط از طریق پنل کاربری یا ایمیل رسمی سامانه معتبر است.</p>
                    <p>زمان پاسخ‌گویی: روزهای کاری، ساعت ۹ تا ۱۷</p>
                    <p>ارتباط از طریق سایر کانال‌ها (شبکه‌های اجتماعی، تلفن شخصی و...) مورد قبول نیست.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۱۰. شرایط خارج از کنترل (فورس‌ماژور)</h3>
                    <p>Aidastyar در برابر اختلالاتی از قبیل قطعی اینترنت، حملات سایبری، بلایای طبیعی یا دستورات قانونی غیرمترقبه مسئولیتی ندارد.</p>
                    <p>خدمات پس از رفع مشکل از سر گرفته خواهد شد.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۱۱. تحریم‌ها و محدودیت‌های ملی</h3>
                    <p>کاربر تأیید می‌کند در لیست تحریم‌های جمهوری اسلامی ایران قرار ندارد.</p>
                    <p>استفاده از خدمات برای اهداف غیرقانونی داخلی یا خارجی ممنوع است.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۱۲. قانون حاکم و مرجع رسیدگی</h3>
                    <p>این توافق‌نامه مشمول قوانین جمهوری اسلامی ایران است.</p>
                    <p>در صورت بروز اختلاف، ابتدا از طریق مذاکره حل‌وفصل خواهد شد.</p>
                    <p>در صورت عدم توافق، مرجع رسمی رسیدگی دادگاه عمومی حقوقی تهران - مجتمع قضایی شهید صدر خواهد بود.</p>
                </div>
    
                <div class="terms-section">
                    <h3>۱۳. تغییرات در توافق‌نامه</h3>
                    <p>Aidastyar مجاز است هر زمان متن توافق‌نامه را تغییر دهد.</p>
                    <p>ادامه استفاده از خدمات به منزله پذیرش نسخه جدید است.</p>
                    <p>نسخه به‌روز توافق‌نامه در همین صفحه قابل‌مشاهده خواهد بود.</p>
                </div>
    
                <!-- چک‌باکس تأیید در انتهای متن -->
                <div id="terms-agreement-checkbox" class="checkbox-container">
                    <input type="checkbox" id="agree-terms" class="real-checkbox">
                    <label for="agree-terms" class="checkbox-label">
                        <span class="check-icon"></span>
                        <span class="label-text">تمام شرایط و قوانین را مطالعه کرده‌ام و می‌پذیرم</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 19: Confirmation -->
    <div id="confirm-submit-step" class="step">
        <h2>خلاصه اطلاعات شما</h2>
        <div id="summary-container"></div>
        <div id="confirmation-checkbox" class="checkbox-container">
            <input type="checkbox" id="confirm-info" class="real-checkbox">
            <label for="confirm-info" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">اطلاعات وارد شده را تأیید می‌کنم</span>
            </label>
        </div>
        <div id="submit-button-container">
            <?php if (is_user_logged_in()): ?>
                <button type="submit" id="SubmitBtn" class="submit-form">ثبت نهایی</button>
            <?php else: ?>
                <button type="button" id="SubmitBtn" class="submit-form" onclick="saveFormAndRedirect()">ورود و ثبت نهایی</button>
            <?php endif; ?>                
        </div>
    
        <div id="ai-diet-result" style="display:none;">
            <div class="ai-response-content"></div>
            <button id="downloadPdf" style="display:none">دانلود PDF</button>
        </div>
    </div>
    
    <div id="next-button-container">
        <button type="button" class="next-step">گام بعد</button>
    </div>
        
    <script>
    // اضافه کردن مدیریت تم به آبجکت state
    window.state = {
        ...window.state,
        toggleTheme: function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('diet-theme', isDark ? 'dark' : 'light');
        }
    };

    // بارگذاری تم ذخیره شده
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('diet-theme') || 'light';
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
        
        // ایجاد دکمه تغییر تم
        const themeToggle = document.createElement('button');
        themeToggle.className = 'theme-toggle';
        themeToggle.title = 'تغییر تم تاریک/روشن';
        
        themeToggle.addEventListener('click', state.toggleTheme);
        document.body.appendChild(themeToggle);
    });
    </script>  
</form>

<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/chart.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/script.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/diet.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/form-events.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/form-validation.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/form-steps.js"></script>
<script src="<?php echo $theme_assets; ?>/assets/js/services/<?php echo esc_attr($service_id); ?>/form-inputs.js"></script>


<script>
function saveFormAndRedirect() {
  // ذخیره داده‌های فرم
  sessionStorage.setItem('diet_form_data', JSON.stringify({
    ...window.state.formData,
    _timestamp: Date.now(),
    _currentStep: window.state.currentStep
  }));
  
  // ذخیره URL فعلی
  const currentUrl = window.location.href.split('#')[0];
  sessionStorage.setItem('diet_form_redirect_url', currentUrl);
  
  // هدایت به صفحه لاگین با کامپوننت لودینگ
  const loginUrl = '<?php echo wp_login_url(); ?>?redirect_to=' + encodeURIComponent(currentUrl);
  
  const loader = new AiDastyarLoader({
    message: 'در حال انتقال به صفحه ورود...',
    duration: 1500,
    closable: false,
    persistent: false,
    showProgress: true, 
    redirectOnClose: null    
  });
  loader.redirect(loginUrl);
}

window.addEventListener('load', function() {
    // پنهان کردن لودینگ در صورت وجود
    if (window.AiDastyarLoader && window.AiDastyarLoader.hide) {
        window.AiDastyarLoader.hide();
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const loggedIn = urlParams.get('logged_in');
    
    if (loggedIn === '1' && sessionStorage.getItem('diet_form_data')) {
        // نمایش loader هنگام بازیابی داده‌ها
        const loader = new AiDastyarLoader({
            message: 'در حال بازیابی اطلاعات...',
            duration: 1500,
            closable: false,
            persistent: false,
            showProgress: true, 
            redirectOnClose: null                
        });
        loader.show();

        // بازیابی داده‌ها
        const savedData = JSON.parse(sessionStorage.getItem('diet_form_data'));
        const savedStep = savedData._currentStep || 1;
        
        if (savedData) {
            const {_timestamp, _currentStep, ...formData} = savedData;
            Object.assign(window.state.formData, formData);
            
            window.navigateToStep(STEPS.TERMS_AGREEMENT);
            
            if (typeof window.state.updateFormElementsFromState === 'function') {
                window.state.updateFormElementsFromState();
            }
        }
        
        // پاک کردن داده‌های ذخیره شده
        sessionStorage.removeItem('diet_form_data');
        sessionStorage.removeItem('diet_form_redirect_url');
        
        // حذف پارامتر logged_in از URL
        if (window.history.replaceState) {
            const newUrl = window.location.pathname + window.location.hash;
            window.history.replaceState({}, document.title, newUrl);
        }
        
        // مخفی کردن loader پس از 1 ثانیه
        setTimeout(() => loader.hide(), 1000);
    }
});
</script>