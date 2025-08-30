<?php
$theme_assets = get_stylesheet_directory_uri();
?>

<form id="multi-step-form" class="ai-multistep-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
    <input type="hidden" name="action" value="submit_travel_form">
    <?php wp_nonce_field('travel_form_nonce', 'travel_form_security'); ?>
    <div id="header-container">
        <div id="step-counter"><span id="current-step">1</span>/<span id="total-steps">12</span></div>
        <button type="button" id="back-button">›</button>
    </div>
    <div id="progress-bar-container">
        <div id="progress-bar"></div>
    </div>

    <div id="trip-type-step" class="step active">
        <h1 id="form-title">برنامه‌ریز هوشمند سفر</h1>
        <h2>نوع سفر خود را انتخاب کنید</h2>
        <div id="trip-type-selection">
            <div class="trip-type-option" data-trip-type="leisure">
                <img src="<?php echo $theme_assets; ?>/assets/images/webp/leisure.webp" alt="تفریحی">
                <span>تفریحی</span>
            </div>
            <div class="trip-type-option" data-trip-type="business">
                <img src="<?php echo $theme_assets; ?>/assets/images/webp/business.webp" alt="تجاری">
                <span>تجاری</span>
            </div>
            <div class="trip-type-option" data-trip-type="family">
                <img src="<?php echo $theme_assets; ?>/assets/images/webp/family.webp" alt="خانوادگی">
                <span>خانوادگی</span>
            </div>
            <div class="trip-type-option" data-trip-type="honeymoon">
                <img src="<?php echo $theme_assets; ?>/assets/images/webp/honeymoon.webp" alt="ماه عسل">
                <span>ماه عسل</span>
            </div>
        </div>
    
        <div id="terms-checkbox" class="checkbox-container terms-combined">
            <input type="checkbox" id="confirm-terms" class="real-checkbox">
            <label for="confirm-terms" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">شرایط و قوانین را می‌پذیرم</span>
            </label>
            <div class="terms-box">
                <ul class="terms-list">
                    <li>اطلاعات شخصی من با حداکثر امنیت و مطابق قوانین محرمانگی محفوظ خواهد ماند.</li>
                    <li>برنامه سفر توسط پیشرفته‌ترین الگوریتم‌های هوش مصنوعی ارائه می‌شود.</li>
                    <li>مسئولیت نهایی تصمیمات سفر بر عهده خودم است.</li>
                    <li>متعهد می‌شوم برای مسائل مهم حتماً با متخصصان مشورت کنم.</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="destination-step" class="step">
        <h2>مقصد سفر شما کجاست؟</h2>
        <div id="destination-selection">
            <div class="destination-option" data-destination="domestic">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/domestic-min.png" alt="داخلی">
                <span>داخلی (ایران)</span>
            </div>
            <div class="destination-option" data-destination="international">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/international-min.png" alt="خارجی">
                <span>خارجی</span>
            </div>
        </div>
    </div>

    <div id="travelers-step" class="step">
        <h2>تعداد مسافران چند نفر است؟</h2>
        <div class="input-container">
            <input type="text" inputmode="numeric" id="travelers-input">
            <span id="travelers-display">0 نفر</span>
        </div>
        <div id="travelers-validation-container">
            <p id="travelers-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/travelers-min.png" width="30" height="30" alt="مسافران">
                    <div class="info-text">
                        <span class="first-line">تعداد مسافران را برای برنامه‌ریزی بهتر وارد کنید.</span>
                        <span class="second-line">این اطلاعات به ما کمک می‌کند برنامه مناسب برای گروه شما طراحی کنیم.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="duration-step" class="step">
        <h2>مدت سفر چند روز است؟</h2>
        <div class="input-container">
            <input type="text" inputmode="numeric" id="duration-input">
            <span id="duration-display">0 روز</span>
        </div>
        <div id="duration-validation-container">
            <p id="duration-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/duration-min.png" width="30" height="30" alt="مدت سفر">
                    <div class="info-text">
                        <span class="first-line">مدت سفر را برای برنامه‌ریزی دقیق‌تر وارد کنید.</span>
                        <span class="second-line">این اطلاعات به ما کمک می‌کند برنامه روزانه مناسب طراحی کنیم.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="budget-step" class="step">
        <h2>بودجه سفر شما چقدر است؟ (دلار)</h2>
        <div class="input-container">
            <input type="text" inputmode="numeric" id="budget-input">
            <span id="budget-display">0 دلار</span>
        </div>
        <div id="budget-validation-container">
            <p id="budget-error" class="error-message"></p>
            <div class="separator-dotted"></div>
            <div class="info-box">
                <div class="info-content">
                    <img src="<?php echo $theme_assets; ?>/assets/images/png/budget-min.png" width="30" height="30" alt="بودجه">
                    <div class="info-text">
                        <span class="first-line">بودجه سفر را برای پیشنهادهای مناسب وارد کنید.</span>
                        <span class="second-line">این اطلاعات به ما کمک می‌کند گزینه‌های مناسب با بودجه شما پیشنهاد دهیم.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="travel-style-step" class="step">
        <h2>سبک سفر شما چیست؟</h2>
        <div id="travel-style-selection">
            <div class="travel-style-option" data-travel-style="luxury">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/luxury-min.png" alt="لوکس">
                <span>لوکس</span>
            </div>
            <div class="travel-style-option" data-travel-style="budget">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/budget-min.png" alt="مقرون به صرفه">
                <span>مقرون به صرفه</span>
            </div>
            <div class="travel-style-option" data-travel-style="backpacking">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/backpacking-min.png" alt="کوله‌گردی">
                <span>کوله‌گردی</span>
            </div>
            <div class="travel-style-option" data-travel-style="cultural">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/cultural-min.png" alt="فرهنگی">
                <span>فرهنگی</span>
            </div>
        </div>
    </div>
    <div id="accommodation-step" class="step">
        <h2>ترجیح شما برای اقامت چیست؟</h2>
        <div id="accommodation-selection">
            <div class="accommodation-option" data-accommodation="hotel">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/hotel-min.png" alt="هتل">
                <span>هتل</span>
            </div>
            <div class="accommodation-option" data-accommodation="apartment">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/apartment-min.png" alt="آپارتمان">
                <span>آپارتمان</span>
            </div>
            <div class="accommodation-option" data-accommodation="hostel">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/hostel-min.png" alt="هاستل">
                <span>هاستل</span>
            </div>
            <div class="accommodation-option" data-accommodation="resort">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/resort-min.png" alt="ریزورت">
                <span>ریزورت</span>
            </div>
            <div class="accommodation-option" data-accommodation="camping">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/camping-min.png" alt="چادر">
                <span>چادر</span>
            </div>
        </div>
    </div>
    <div id="transportation-step" class="step">
        <h2>نوع حمل و نقل مورد نظر شما چیست؟</h2>
        <div id="transportation-selection">
            <div class="transportation-option" data-transportation="plane">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/plane-min.png" alt="هواپیما">
                <span>هواپیما</span>
            </div>
            <div class="transportation-option" data-transportation="train">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/train-min.png" alt="قطار">
                <span>قطار</span>
            </div>
            <div class="transportation-option" data-transportation="bus">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/bus-min.png" alt="اتوبوس">
                <span>اتوبوس</span>
            </div>
            <div class="transportation-option" data-transportation="car">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/car-min.png" alt="ماشین شخصی">
                <span>ماشین شخصی</span>
            </div>
            <div class="transportation-option" data-transportation="cruise">
                <img src="<?php echo $theme_assets; ?>/assets/images/png/cruise-min.png" alt="کشتی">
                <span>کشتی</span>
            </div>
        </div>
    </div>
    <div id="activities-step" class="step checkbox-step-container">
        <h2>چه نوع فعالیت‌هایی در سفر می‌پسندید؟</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="activities-none" class="real-checkbox">
            <label for="activities-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچکدام</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="activities-selection" class="checkbox-selection-container checkbox-list-container">
            <div class="checkbox-container">
                <input type="checkbox" id="activities-hiking" class="real-checkbox">
                <label for="activities-hiking" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">کوهنوردی و طبیعت‌گردی</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="activities-cultural" class="real-checkbox">
                <label for="activities-cultural" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">فرهنگی و تاریخی</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="activities-beach" class="real-checkbox">
                <label for="activities-beach" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">ساحلی و استراحت</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="activities-adventure" class="real-checkbox">
                <label for="activities-adventure" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">ماجراجویی</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="activities-shopping" class="real-checkbox">
                <label for="activities-shopping" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">خرید</span>
                </label>
            </div>
        </div>
    </div>
    <div id="food-preferences-step" class="step checkbox-step-container">
        <h2>ترجیحات غذایی شما چیست؟</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="food-none" class="real-checkbox">
            <label for="food-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">بدون ترجیح خاص</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="food-preferences-selection" class="checkbox-selection-container checkbox-list-container">
            <div class="checkbox-container">
                <input type="checkbox" id="food-local" class="real-checkbox">
                <label for="food-local" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">غذای محلی</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="food-international" class="real-checkbox">
                <label for="food-international" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">غذای بین‌المللی</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="food-vegetarian" class="real-checkbox">
                <label for="food-vegetarian" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">گیاهخواری</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="food-vegan" class="real-checkbox">
                <label for="food-vegan" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">وگان</span>
                </label>
            </div>
        </div>
    </div>
    <div id="special-needs-step" class="step checkbox-step-container">
        <h2>آیا نیازمندی خاصی دارید؟</h2>
        
        <div class="checkbox-container first-option stand-alone-none">
            <input type="checkbox" id="needs-none" class="real-checkbox">
            <label for="needs-none" class="checkbox-label">
                <span class="check-icon"></span>
                <span class="label-text">هیچکدام</span>
            </label>
        </div>
        
        <div class="separator"></div>
        
        <div id="special-needs-selection" class="checkbox-selection-container checkbox-list-container">
            <div class="checkbox-container">
                <input type="checkbox" id="needs-wheelchair" class="real-checkbox">
                <label for="needs-wheelchair" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">صندلی چرخدار</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="needs-dietary" class="real-checkbox">
                <label for="needs-dietary" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">محدودیت غذایی خاص</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="needs-medical" class="real-checkbox">
                <label for="needs-medical" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">نیازمندی‌های پزشکی</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="needs-visual" class="real-checkbox">
                <label for="needs-visual" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">مشکلات بینایی</span>
                </label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="needs-hearing" class="real-checkbox">
                <label for="needs-hearing" class="checkbox-label">
                    <span class="check-icon"></span>
                    <span class="label-text">مشکلات شنوایی</span>
                </label>
            </div>
        </div>
    </div>
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
                <button type="button" id="SubmitBtn" class="submit-form" onclick="saveFormAndLogin()">ورود و ثبت نهایی</button>
            <?php endif; ?>                
        </div>
        
        <div id="ai-travel-result" style="display:none;">
            <div class="ai-response-content">
            </div>
            <button id="downloadPdf" style="display:none">دانلود PDF</button>
        </div>
    </div>
    <div id="next-button-container">
        <button type="button" class="next-step">گام بعد</button>
    </div>
</form>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>    

<script>
function saveFormAndLogin() {
    // ذخیره تمام داده‌های فرم
    localStorage.setItem('travel_form_data', JSON.stringify(window.state.formData));
    // ذخیره مرحله فعلی
    localStorage.setItem('travel_form_current_step', window.state.currentStep);
    // هدایت به صفحه لاگین
    window.location.href = '<?php echo wp_login_url(esc_url($_SERVER['REQUEST_URI'])); ?>';
}
</script>