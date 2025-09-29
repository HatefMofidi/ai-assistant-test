jQuery(document).ready(function($) {
    let currentTab = 'pending';

    // مدیریت تب‌ها
    $('.comments-tab-button').on('click', function() {
        $('.comments-tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.comments-tab-pane').removeClass('active');
        currentTab = $(this).data('tab');
        $(`#${currentTab}-tab`).addClass('active');
        
        loadComments(currentTab);
    });

    // بارگذاری اولیه
    loadComments(currentTab);

    function loadComments(status) {
        const listElement = $(`#${status}-tab .comments-list`);
        listElement.html('<div class="comments-loading">' + commentsFrontendAdminVars.i18n.loading + '</div>');

        $.ajax({
            url: commentsFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_comments_for_admin',
                status: status,
                nonce: commentsFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    listElement.html(response.data.html);
                    updateStats(response.data.stats);
                    bindCommentActions();
                } else {
                    listElement.html('<div class="comments-error">خطا در بارگذاری نظرات</div>');
                }
            },
            error: function() {
                listElement.html('<div class="comments-error">خطا در ارتباط با سرور</div>');
            }
        });
    }

    function updateStats(stats) {
        $('#pending-count').text(stats.pending);
        $('#approved-count').text(stats.approved);
        $('#rejected-count').text(stats.rejected);
    }

    function bindCommentActions() {
        // تایید نظر
        $('.comments-approve-comment').on('click', function() {
            const commentId = $(this).data('comment-id');
            updateCommentStatus(commentId, 'approve');
        });

        // رد نظر
        $('.comments-reject-comment').on('click', function() {
            const commentId = $(this).data('comment-id');
            updateCommentStatus(commentId, 'reject');
        });

        // حذف نظر
        $('.comments-delete-comment').on('click', function() {
            if (!confirm(commentsFrontendAdminVars.i18n.confirm_delete)) {
                return;
            }
            
            const commentId = $(this).data('comment-id');
            deleteComment(commentId);
        });

        // مشاهده جزئیات
        $('.comments-view-comment-details').on('click', function() {
            const commentId = $(this).data('comment-id');
            showCommentDetails(commentId);
        });

        // بستن modal
        $('.comments-close-modal').on('click', function() {
            $('#comments-modal').hide();
        });
    }

    function updateCommentStatus(commentId, action) {
        const button = $(`.comments-actions button[data-comment-id="${commentId}"]`);
        const originalText = button.html();
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> در حال پردازش...');

        $.ajax({
            url: commentsFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: `${action}_service_comment`,
                comment_id: commentId,
                nonce: commentsFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    updateStats(response.data.stats);
                    
                    // رفرش لیست فعلی
                    setTimeout(() => {
                        loadComments(currentTab);
                    }, 1000);
                } else {
                    showMessage(response.data, 'error');
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showMessage(commentsFrontendAdminVars.i18n.error, 'error');
                button.prop('disabled', false).html(originalText);
            }
        });
    }

    function deleteComment(commentId) {
        const commentItem = $(`.comments-item[data-comment-id="${commentId}"]`);
        
        $.ajax({
            url: commentsFrontendAdminVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_service_comment',
                comment_id: commentId,
                nonce: commentsFrontendAdminVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    updateStats(response.data.stats);
                    
                    commentItem.fadeOut(300, function() {
                        $(this).remove();
                        
                        // اگر آیتمی باقی نمانده، پیام نشان دهید
                        if ($('.comments-item').length === 0) {
                            $(`#${currentTab}-tab .comments-list`).html('<div class="comments-no-comments">هیچ نظری یافت نشد.</div>');
                        }
                    });
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage(commentsFrontendAdminVars.i18n.error, 'error');
            }
        });
    }

    function showCommentDetails(commentId) {
        // در اینجا می‌توانید جزئیات بیشتری از نظر را نمایش دهید
        const commentItem = $(`.comments-item[data-comment-id="${commentId}"]`);
        const commentText = commentItem.find('.comments-text').text();
        const author = commentItem.find('.comments-author').text();
        const service = commentItem.find('.comments-service').text();
        const date = commentItem.find('.comments-date').text();
        
        const detailsHtml = `
            <h3>جزئیات نظر</h3>
            <div class="comments-detail">
                <p><strong>نویسنده:</strong> ${author}</p>
                <p><strong>سرویس:</strong> ${service}</p>
                <p><strong>تاریخ:</strong> ${date}</p>
                <p><strong>متن نظر:</strong></p>
                <div class="comments-text-detail">${commentText}</div>
            </div>
        `;
        
        $('#comments-modal-comment-details').html(detailsHtml);
        $('#comments-modal').show();
    }

    function showMessage(message, type) {
        // حذف پیام قبلی اگر وجود دارد
        $('.comments-admin-message').remove();
        
        const messageClass = type === 'success' ? 'comments-admin-message success' : 'comments-admin-message error';
        const messageHtml = `<div class="${messageClass}">${message}</div>`;
        
        $('.comments-admin-panel-header').after(messageHtml);
        
        // حذف خودکار پیام بعد از 5 ثانیه
        setTimeout(function() {
            $('.comments-admin-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // بستن modal با کلیک خارج از آن
    $(window).on('click', function(event) {
        if ($(event.target).is('#comments-modal')) {
            $('#comments-modal').hide();
        }
    });
});