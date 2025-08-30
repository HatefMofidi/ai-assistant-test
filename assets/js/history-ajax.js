// /home/aidastya/public_html/wp-content/themes/ai-assistant/assets/js/history-ajax.js
jQuery(document).ready(function($) {
    $('.delete-history').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var post_id = $this.data('id');
        var nonce = $this.data('nonce');
        var $row = $this.closest('tr');

        if (!confirm('آیا مطمئن هستید که می‌خواهید این آیتم را حذف کنید؟')) {
            return false;
        }

        $.ajax({
            url: history_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_history_item',
                post_id: post_id,
                nonce: nonce,
                _wpnonce: history_ajax.nonce
            },
            beforeSend: function() {
                $row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('خطا در حذف آیتم: ' + response.data);
                    $row.css('opacity', '1');
                }
            }
        });
    });
});