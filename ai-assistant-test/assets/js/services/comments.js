// /home/aidastya/public_html/test/wp-content/themes/ai-assistant-test/assets/js/services/comments.js
class ServiceComments {
    constructor(serviceId) {
        this.serviceId = serviceId;
        this.currentPage = 1;
        this.ajaxurl = serviceCommentsVars.ajaxurl; // تغییر این خط
        this.security = serviceCommentsVars.security; // اضافه کردن security
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadComments();
        this.loadRating();
    }

    bindEvents() {
        // Add comment button
        jQuery(document).on('click', `.add-comment-btn[data-service="${this.serviceId}"]`, (e) => {
            this.toggleCommentForm(e.target);
        });

        // Star rating
        jQuery(document).on('click', `.stars-input[data-service="${this.serviceId}"] i`, (e) => {
            this.setRating(e.target);
        });

        // Submit comment
        jQuery(document).on('click', `.comment-submit-btn[data-service="${this.serviceId}"]`, (e) => {
            this.submitComment(e.target);
        });

        // Load more comments
        jQuery(document).on('click', `.load-more-btn[data-service="${this.serviceId}"]`, (e) => {
            this.loadMoreComments(e.target);
        });
    }

    toggleCommentForm(button) {
        const form = jQuery(`.comment-form[data-service="${this.serviceId}"]`);
        form.toggleClass('active');
        
        if (form.hasClass('active')) {
            jQuery(button).text('لغو ثبت نظر');
        } else {
            jQuery(button).text('ثبت نظر');
        }
    }

    setRating(star) {
        const stars = jQuery(star).parent().find('i');
        const rating = parseInt(jQuery(star).data('value'));
        
        stars.removeClass('active');
        stars.each(function() {
            if (parseInt(jQuery(this).data('value')) <= rating) {
                jQuery(this).addClass('active');
            }
        });
        
        jQuery(star).closest('.rating-input').find('input[type="hidden"]').val(rating);
    }

    submitComment(button) {
        const form = jQuery(button).closest('.comment-form');
        const commentText = form.find('.comment-textarea').val().trim();
        const rating = form.find('input[name="rating"]').val();
        
        if (!commentText) {
            alert('لطفاً متن نظر خود را وارد کنید.');
            return;
        }
        
        if (!rating || rating < 1) {
            alert('لطفاً امتیاز دهید.');
            return;
        }

        jQuery(button).prop('disabled', true).text('در حال ثبت...');

        jQuery.ajax({
            url: this.ajaxurl, // تغییر این خط
            type: 'POST',
            data: {
                action: 'submit_service_comment',
                security: this.security, // تغییر این خط
                service_id: this.serviceId,
                comment_text: commentText,
                rating: rating
            },
            success: (response) => {
                if (response.success) {
                    alert(response.data);
                    form.find('.comment-textarea').val('');
                    form.find('input[name="rating"]').val('0');
                    form.find('.stars-input i').removeClass('active');
                    form.removeClass('active');
                    jQuery(`.add-comment-btn[data-service="${this.serviceId}"]`).text('ثبت نظر');
                    
                    // Reload comments and rating
                    this.loadComments(true);
                    this.loadRating();
                } else {
                    alert(response.data);
                }
            },
            error: () => {
                alert('خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
            },
            complete: () => {
                jQuery(button).prop('disabled', false).text('ثبت نظر');
            }
        });
    }

    loadComments(reset = false) {
        if (reset) {
            this.currentPage = 1;
        }

        const container = jQuery(`.comments-list[data-service="${this.serviceId}"]`);
        
        if (this.currentPage === 1) {
            container.html('<div class="loading">در حال بارگذاری نظرات...</div>');
        }

        jQuery.ajax({
            url: this.ajaxurl, // تغییر این خط
            type: 'POST',
            data: {
                action: 'get_service_comments',
                service_id: this.serviceId,
                page: this.currentPage
            },
            success: (response) => {
                if (response.success) {
                    this.renderComments(response.data.comments, container, reset);
                    this.renderPagination(response.data, container);
                }
            },
            error: () => {
                container.html('<div class="error">خطا در بارگذاری نظرات</div>');
            }
        });
    }

    renderComments(comments, container, reset) {
        if (reset) {
            container.empty();
        }

        if (!comments || comments.length === 0) {
            if (this.currentPage === 1) {
                container.html(`
                    <div class="no-comments">
                        <i class="fas fa-comment-slash" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>هنوز نظری برای این سرویس ثبت نشده است.</p>
                    </div>
                `);
            }
            return;
        }

        let html = '';
        comments.forEach(comment => {
            const date = new Date(comment.created_at).toLocaleDateString('fa-IR');
            const stars = this.generateStars(comment.rating);
            
            html += `
                <div class="comment-item">
                    <div class="comment-header">
                        <span class="comment-author">${comment.display_name || comment.user_login}</span>
                        <span class="comment-date">${date}</span>
                        <span class="comment-rating">${stars}</span>
                    </div>
                    <p class="comment-text">${comment.comment_text}</p>
                </div>
            `;
        });

        if (reset) {
            container.html(html);
        } else {
            container.append(html);
        }
    }

    renderPagination(data, container) {
        const loadMoreContainer = jQuery(`.load-more-comments[data-service="${this.serviceId}"]`);
        
        if (data.current_page < data.total_pages) {
            loadMoreContainer.html(`
                <button class="load-more-btn" data-service="${this.serviceId}">
                    بارگذاری نظرات بیشتر
                </button>
            `);
        } else {
            loadMoreContainer.empty();
        }
    }

    loadMoreComments() {
        this.currentPage++;
        this.loadComments();
    }

    loadRating() {
        const container = jQuery(`.average-rating[data-service="${this.serviceId}"]`);
        
        jQuery.ajax({
            url: this.ajaxurl, // تغییر این خط
            type: 'POST',
            data: {
                action: 'get_service_rating',
                service_id: this.serviceId
            },
            success: (response) => {
                if (response.success) {
                    this.renderRating(response.data, container);
                }
            }
        });
    }

    renderRating(data, container) {
        const avgRating = data.average_rating || 0;
        const count = data.comment_count || 0;
        
        const stars = this.generateStars(avgRating, true);
        
        container.html(`
            <span class="rating-text">${avgRating.toFixed(1)}</span>
            <div class="rating-stars">${stars}</div>
            <span class="comments-count">(${count} نظر)</span>
        `);
    }

    generateStars(rating, isAverage = false) {
        let stars = '';
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        
        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                stars += '<i class="fas fa-star filled"></i>';
            } else if (i === fullStars + 1 && hasHalfStar) {
                stars += '<i class="fas fa-star-half-alt filled"></i>';
            } else {
                stars += '<i class="far fa-star"></i>';
            }
        }
        
        return stars;
    }
}

// Initialize comments for each service
jQuery(document).ready(function($) {
    $('.service-comments-section').each(function() {
        const serviceId = $(this).data('service');
        new ServiceComments(serviceId);
    });
});