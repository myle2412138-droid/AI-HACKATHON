// All DOM interactions inside DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý form submit (fix: use #user-input)
    const uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const imageUrl = document.getElementById('user-input') ? document.getElementById('user-input').value : '';

            // Hiển thị loading
            const submitBtn = this.querySelector('button[type="submit"]') || this.querySelector('.btn');
            const originalText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.textContent = 'Đang xử lý...';
                submitBtn.disabled = true;
            }

            // Giả lập gọi API hoặc backend (thay bằng call thực tế khi có)
            setTimeout(function() {
                displayResults({
                    img1: 'https://via.placeholder.com/300x200/808080/ffffff?text=Image+1',
                    img2: 'https://via.placeholder.com/300x200/c084fc/ffffff?text=Image+2',
                    img3: 'https://via.placeholder.com/300x200/808080/ffffff?text=Image+3',
                    img4: 'https://via.placeholder.com/300x200/84c9f0/ffffff?text=Image+4',
                    img5: 'https://via.placeholder.com/300x200/808080/ffffff?text=Image+5',
                    preview: 'https://via.placeholder.com/800x200/cccccc/ffffff?text=Preview+All'
                });

                if (submitBtn) {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            }, 1200);
        });
    }

    // Hiển thị kết quả
    function displayResults(data) {
        // Đặt các ảnh (có kiểm tra tồn tại phần tử)
        ['img1','img2','img3','img4','img5'].forEach(id => {
            const el = document.getElementById(id);
            if (el && data[id]) el.src = data[id];
        });
        const preview = document.getElementById('preview-all');
        if (preview && data.preview) preview.src = data.preview;

        document.querySelectorAll('.image-item .download-btn').forEach((btn, idx) => {
            btn.href = `#/download/image${idx + 1}`;
        });

        const resultsSection = document.getElementById('results-section');
        if (resultsSection) resultsSection.style.display = 'block';
        if (resultsSection) resultsSection.scrollIntoView({ behavior: 'smooth' });
    }

    // Nút "Apply Now" hoặc tương tự (some buttons have same class .btn)
    document.querySelectorAll('.btn').forEach(btn => {
        if (btn.textContent && btn.textContent.toLowerCase().includes('apply now')) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const el = document.getElementById('process');
                if (el) el.scrollIntoView({ behavior: 'smooth' });
            });
        }
    });

    // Nút "Get Started" trên header
    const headerBtn = document.querySelector('.header .btn');
    if (headerBtn) {
        headerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const el = document.getElementById('process');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Nút "Bắt Đầu Miễn Phí"
    const ctaBtn = document.querySelector('.cta-section .btn-large');
    if (ctaBtn) {
        ctaBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const el = document.getElementById('process');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Smooth scroll cho menu
    document.querySelectorAll('.menu a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            if (target && target.startsWith('#')) {
                const element = document.querySelector(target);
                if (element) element.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Smooth scroll cho footer links
    document.querySelectorAll('.footer-section a').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                const element = document.querySelector(href);
                if (element) element.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Header: toggle .header--scrolled when scrolling past threshold (use requestAnimationFrame for perf)
    const header = document.querySelector('.header');
    if (header) {
        let ticking = false;
        const onScroll = () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    if (window.scrollY > 40) header.classList.add('header--scrolled');
                    else header.classList.remove('header--scrolled');
                    ticking = false;
                });
                ticking = true;
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        // apply initial state on load
        if (window.scrollY > 40) header.classList.add('header--scrolled');
    }

    // Sign in is handled on separate page; no inline modal handlers.
});

// Hiển thị kết quả
function displayResults(data) {
    // Đặt các ảnh
    document.getElementById('img1').src = data.img1;
    document.getElementById('img2').src = data.img2;
    document.getElementById('img3').src = data.img3;
    document.getElementById('img4').src = data.img4;
    document.getElementById('img5').src = data.img5;
    document.getElementById('preview-all').src = data.preview;
    
    // Đặt các link tải (sẽ được thay thế bằng URLs thực từ server)
    document.querySelectorAll('.image-item .download-btn').forEach((btn, idx) => {
        btn.href = `#/download/image${idx + 1}`;
    });
    
    // Hiển thị section kết quả
    document.getElementById('results-section').style.display = 'block';
    
    // Scroll tới kết quả
    document.getElementById('results-section').scrollIntoView({ behavior: 'smooth' });
}

// Nút "Apply Now" trên hero
document.querySelectorAll('.btn').forEach(btn => {
    if (btn.textContent.includes('Apply Now')) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('process').scrollIntoView({ behavior: 'smooth' });
        });
    }
});

// Nút "Get Started" trên header
document.querySelector('.header .btn').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('process').scrollIntoView({ behavior: 'smooth' });
});

// Nút "Bắt Đầu Miễn Phí"
document.querySelector('.cta-section .btn-large').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('process').scrollIntoView({ behavior: 'smooth' });
});

// Smooth scroll cho menu
document.querySelectorAll('.menu a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const target = this.getAttribute('href');
        if (target.startsWith('#')) {
            const element = document.querySelector(target);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }
    });
});

// Smooth scroll cho footer links
document.querySelectorAll('.footer-section a').forEach(link => {
    link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href && href.startsWith('#')) {
            e.preventDefault();
            const element = document.querySelector(href);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }
    });
});

// Thêm hiệu ứng khi scroll (optional)
window.addEventListener('scroll', function() {
    const header = document.querySelector('.header');
    if (window.scrollY > 100) {
        header.style.boxShadow = '0 .5rem 1rem rgba(0,0,0,.3)';
    } else {
        header.style.boxShadow = 'var(--box-shadow)';
    }
});
