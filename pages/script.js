document.addEventListener('DOMContentLoaded', () => {
    const chatbot = document.querySelector('.chatbot');
    if (!chatbot) {
        return;
    }

    const toggleBtn = chatbot.querySelector('.chatbot-toggle');
    const closeBtn = chatbot.querySelector('.chatbot-close');
    const windowEl = chatbot.querySelector('.chatbot-window');
    const messagesEl = chatbot.querySelector('.chatbot-messages');
    const form = chatbot.querySelector('.chatbot-form');
    const input = chatbot.querySelector('#chatbot-input');

    const state = {
        openedOnce: false
    };

    const knowledgeBase = [
        {
            pattern: /(xin chào|chào|hi|hello)/i,
            response: 'Xin chào! Mình là Victoria Assistant. Bạn cần hỗ trợ điều gì hôm nay?'
        },
        {
            pattern: /(giá|pricing|chi phí|bao nhiêu)/i,
            response: 'Gói cơ bản của chúng tôi hoàn toàn miễn phí. Các gói Pro bắt đầu từ 299.000đ/tháng với nhiều tính năng nâng cao.'
        },
        {
            pattern: /(liên hệ|contact|support|hỗ trợ)/i,
            response: 'Bạn có thể liên hệ đội ngũ hỗ trợ qua email support@victoria.ai hoặc gọi hotline 1900-123-456.'
        },
        {
            pattern: /(tính năng|feature|làm được gì)/i,
            response: 'Victoria AI cung cấp phân tích dữ liệu tự động, gợi ý hành động và báo cáo realtime để giúp bạn đưa ra quyết định nhanh hơn.'
        },
        {
            pattern: /(đăng ký|signup|bắt đầu|create account)/i,
            response: 'Để bắt đầu, bạn chỉ cần nhấn nút Get Started ở đầu trang và làm theo hướng dẫn đăng ký tài khoản.'
        },
        {
            pattern: /(demo|thử|dùng thử|test)/i,
            response: 'Bạn có thể sử dụng mục “Thử Nghiệm Ngay” trên trang để upload dữ liệu và xem kết quả demo trong vài giây.'
        },
        {
            pattern: /(bảo mật|an toàn|security)/i,
            response: 'Chúng tôi sử dụng mã hóa đầu-cuối và tuân thủ các tiêu chuẩn bảo mật ISO 27001 để bảo vệ dữ liệu của bạn.'
        },
        {
            pattern: /(cảm ơn|thanks|thank you)/i,
            response: 'Rất vui được hỗ trợ bạn! Nếu còn câu hỏi gì, cứ tiếp tục trò chuyện nhé.'
        }
    ];

    const fallbackResponses = [
        'Mình chưa hiểu câu hỏi lắm, bạn có thể mô tả chi tiết hơn không?',
        'Câu hỏi hay đấy! Bạn có thể cho mình thêm thông tin để hỗ trợ tốt hơn chứ?',
        'Hiện mình chưa có câu trả lời chính xác. Bạn thử để lại email để đội ngũ chuyên gia liên hệ nhé?'
    ];

    const getBotResponse = (message) => {
        const matched = knowledgeBase.find(item => item.pattern.test(message));
        if (matched) {
            return matched.response;
        }
        const randomIndex = Math.floor(Math.random() * fallbackResponses.length);
        return fallbackResponses[randomIndex];
    };

    const addMessage = (text, sender = 'bot') => {
        const bubble = document.createElement('div');
        bubble.className = `chatbot-message ${sender}`;
        bubble.textContent = text;
        messagesEl.appendChild(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const openChat = () => {
        if (chatbot.classList.contains('is-open')) {
            return;
        }
        chatbot.classList.add('is-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
        windowEl.setAttribute('aria-modal', 'true');
        windowEl.setAttribute('aria-hidden', 'false');

        if (!state.openedOnce) {
            addMessage('Chào bạn! Mình là Victoria Assistant. Hôm nay mình có thể giúp gì cho bạn?');
            state.openedOnce = true;
        }

        setTimeout(() => {
            input.focus();
        }, 120);
    };

    const closeChat = () => {
        if (!chatbot.classList.contains('is-open')) {
            return;
        }
        chatbot.classList.remove('is-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
        windowEl.setAttribute('aria-modal', 'false');
        windowEl.setAttribute('aria-hidden', 'true');
        toggleBtn.focus();
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        const userMessage = input.value.trim();
        if (!userMessage) {
            return;
        }

        addMessage(userMessage, 'user');
        input.value = '';

        setTimeout(() => {
            addMessage(getBotResponse(userMessage), 'bot');
        }, 350);
    };

    toggleBtn.addEventListener('click', () => {
        if (chatbot.classList.contains('is-open')) {
            closeChat();
        } else {
            openChat();
        }
    });

    closeBtn.addEventListener('click', closeChat);
    form.addEventListener('submit', handleSubmit);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeChat();
        }
    });

    document.addEventListener('click', (event) => {
        if (!chatbot.contains(event.target) && chatbot.classList.contains('is-open')) {
            closeChat();
        }
    });

    windowEl.setAttribute('aria-hidden', 'true');
});
