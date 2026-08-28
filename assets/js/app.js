/**
 * Swagbag — Main Application JavaScript
 */
(function () {
    'use strict';

    const APP_URL = window.__APP_URL__ || '';

    // Page loader
    window.addEventListener('load', function () {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            setTimeout(function () {
                loader.classList.add('hidden');
            }, 300);
        }
    });

    // Toast notifications
    window.showToast = function (message, type) {
        type = type || 'success';
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        const icon = type === 'error' ? 'exclamation-circle' : 'check-circle';
        toast.innerHTML = '<i class="fas fa-' + icon + '"></i><span>' + message + '</span>';
        container.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            setTimeout(function () { toast.remove(); }, 300);
        }, 3500);
    };

    // Flash message dismiss
    const flashMsg = document.getElementById('flashMessage');
    if (flashMsg) {
        const closeBtn = flashMsg.querySelector('.flash-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                flashMsg.remove();
            });
        }
        setTimeout(function () {
            if (flashMsg.parentNode) flashMsg.remove();
        }, 5000);
    }

    // Back to top button
    const backToTop = document.querySelector('.back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 400) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
    }

    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const categoryNav = document.getElementById('categoryNav');
    if (mobileMenuBtn && categoryNav) {
        mobileMenuBtn.addEventListener('click', function () {
            const isOpen = categoryNav.classList.toggle('mobile-open');
            mobileMenuBtn.setAttribute('aria-expanded', isOpen);
        });
    }

    // Product tabs
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tabId = btn.getAttribute('data-tab');
            const parent = btn.closest('.product-tabs');
            if (!parent) return;

            parent.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
            parent.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            const panel = parent.querySelector('#' + tabId);
            if (panel) panel.classList.add('active');
        });
    });

    // Product gallery thumbnails
    document.querySelectorAll('.thumbnail').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            const mainImg = document.getElementById('mainProductImage');
            const src = thumb.querySelector('img');
            if (mainImg && src) {
                mainImg.src = src.src;
                mainImg.alt = src.alt;
            }
            document.querySelectorAll('.thumbnail').forEach(function (t) { t.classList.remove('active'); });
            thumb.classList.add('active');
        });
    });

    // Quantity selector
    document.querySelectorAll('.qty-selector').forEach(function (selector) {
        const minus = selector.querySelector('.qty-minus');
        const plus = selector.querySelector('.qty-plus');
        const input = selector.querySelector('.qty-input');

        if (minus && input) {
            minus.addEventListener('click', function () {
                const min = parseInt(input.min) || 1;
                const val = parseInt(input.value) || 1;
                if (val > min) input.value = val - 1;
            });
        }
        if (plus && input) {
            plus.addEventListener('click', function () {
                const max = parseInt(input.max) || 99;
                const val = parseInt(input.value) || 1;
                if (val < max) input.value = val + 1;
            });
        }
    });

    // Form validation
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            let valid = true;
            form.querySelectorAll('[required]').forEach(function (field) {
                const errorEl = field.parentNode.querySelector('.error');
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#dc2626';
                    if (errorEl) errorEl.textContent = 'This field is required';
                } else if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                    valid = false;
                    field.style.borderColor = '#dc2626';
                    if (errorEl) errorEl.textContent = 'Please enter a valid email';
                } else {
                    field.style.borderColor = '';
                    if (errorEl) errorEl.textContent = '';
                }
            });
            if (!valid) e.preventDefault();
        });
    });

    // Newsletter form AJAX
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const email = newsletterForm.querySelector('[name="email"]').value;
            const formData = new FormData();
            formData.append('email', email);

            fetch(APP_URL + '/actions/newsletter.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) newsletterForm.reset();
            })
            .catch(function () {
                showToast('Something went wrong. Please try again.', 'error');
            });
        });
    }

    // Countdown timer
    const countdownEl = document.getElementById('flashCountdown');
    if (countdownEl) {
        let hours = 4, minutes = 15, seconds = 45;
        setInterval(function () {
            seconds--;
            if (seconds < 0) { seconds = 59; minutes--; }
            if (minutes < 0) { minutes = 59; hours--; }
            if (hours < 0) { hours = 4; minutes = 15; seconds = 45; }
            const h = countdownEl.querySelector('.time-h');
            const m = countdownEl.querySelector('.time-m');
            const s = countdownEl.querySelector('.time-s');
            if (h) h.textContent = String(hours).padStart(2, '0');
            if (m) m.textContent = String(minutes).padStart(2, '0');
            if (s) s.textContent = String(seconds).padStart(2, '0');
        }, 1000);
    }

    // Sort select auto-submit
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortSelect.value);
            window.location.href = url.toString();
        });
    }

})();
