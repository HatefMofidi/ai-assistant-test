<?php
// /inc/class-email-template.php

class AI_Assistant_Email_Template {
    
    /**
     * ایجاد قالب اصلی ایمیل
     */
    public static function get_email_template($content, $title = '') {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        return "
        <!DOCTYPE html>
        <html lang='fa' dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
            <style>
                /* Reset CSS */
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Tahoma', 'Arial', sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f7f7f7;
                    direction: rtl;
                    text-align: right;
                }
                
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }
                
                .email-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }
                
                .email-header h1 {
                    font-size: 24px;
                    margin-bottom: 10px;
                    font-weight: 600;
                }
                
                .email-body {
                    padding: 40px 30px;
                    direction: rtl;
                    text-align: right;
                }
                
                .email-content {
                    font-size: 16px;
                    line-height: 1.8;
                    color: #444;
                }
                
                .email-content p {
                    margin-bottom: 20px;
                }
                
                .email-button {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 14px 32px;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    margin: 20px 0;
                    text-align: center;
                    transition: all 0.3s ease;
                }
                
                .email-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                }
                
                .email-footer {
                    background: #f8f9fa;
                    padding: 25px 30px;
                    text-align: center;
                    border-top: 1px solid #e9ecef;
                    color: #6c757d;
                    font-size: 14px;
                }
                
                .email-footer a {
                    color: #667eea;
                    text-decoration: none;
                }
                
                .info-box {
                    background: #f8f9fa;
                    border-right: 4px solid #667eea;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                
                .deadline {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                
                /* Responsive */
                @media only screen and (max-width: 600px) {
                    .email-body {
                        padding: 20px 15px;
                    }
                    
                    .email-header {
                        padding: 20px 15px;
                    }
                    
                    .email-header h1 {
                        font-size: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h1>{$site_name}</h1>
                    <p>خدمات مشاوره تغذیه و رژیم درمانی</p>
                </div>
                
                <div class='email-body'>
                    <div class='email-content'>
                        {$content}
                    </div>
                </div>
                
                <div class='email-footer'>
                    <p>با تشکر از اعتماد شما</p>
                    <p><strong>{$site_name}</strong></p>
                    <p>
                        <a href='{$site_url}' target='_blank'>وبسایت ما</a> | 
                        <a href='tel:+982100000000'>تماس با پشتیبانی</a>
                    </p>
                    <p style='margin-top: 15px; font-size: 12px; color: #adb5bd;'>
                        این ایمیل به صورت خودکار ارسال شده است. لطفاً به آن پاسخ ندهید.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * ایجاد دکمه CTA
     */
    public static function create_button($url, $text) {
        return "<a href='{$url}' class='email-button' target='_blank'>{$text}</a>";
    }
    
    /**
     * ایجاد باکس اطلاعات
     */
    public static function create_info_box($content) {
        return "<div class='info-box'>{$content}</div>";
    }
    
    /**
     * ایجاد باکس مهلت
     */
    public static function create_deadline_box($content) {
        return "<div class='deadline'>{$content}</div>";
    }
} 