
// ========================================
// 1. Toggle All Passwords
// ========================================

function toggleAllPassword() {
    const $inputs = $('.password_input');
    const $btns = $('#allToggleBtn');

    const isHiddens = $inputs.prop('type') === 'password';

    $inputs.prop('type', isHiddens ? 'text' : 'password');

    $btns.text(
        isHiddens ? "Hide All Passwords" : "Show All Passwords"
    );
}


// ========================================
// 2. Toggle Individual Password
// ========================================

function toggleViews() {
    const $input = $('#password');
    const $btn = $('#toggleBtn');

    const isHidden = $input.attr('type') === 'password';

    $input.attr('type', isHidden ? 'text' : 'password');

    $btn.text(
        isHidden ? "Hide Password" : "Show Password"
    );
}


// ========================================
// 3. Evaluate Password Strength
// ========================================

function evalPasswordStrength() {

    const strength = document.getElementById('password').value;
    const progress_bar = document.getElementById('percentage_password');
    const message = document.getElementById('message_password');

    let percentages = 0;

    if (strength.length >= 8 && strength.length <= 20) {
        percentages += 20;
    }

    if (/[!@#$%^&*()_+]/.test(strength)) {
        percentages += 20;
    }

    if (/[A-Z]/.test(strength)) {
        percentages += 20;
    }

    if (/[a-z]/.test(strength)) {
        percentages += 20;
    }

    if (/[0-9]/.test(strength)) {
        percentages += 20;
    }

    progress_bar.style.width = percentages + '%';

    if (percentages === 100) {
        progress_bar.style.background = 'green';
        message.textContent = percentages + '%. The password is strong.';
    }
    else if (percentages >= 80) {
        progress_bar.style.background = 'lightgreen';
        message.textContent = percentages + '%. The password is moderate.';
    }
    else if (percentages >= 60) {
        progress_bar.style.background = 'orange';
        message.textContent = percentages + '%. The password is weak.';
    }
    else if (percentages >= 20) {
        progress_bar.style.background = 'red';
        message.textContent = percentages + '%. The password is very weak.';
    }
    else {
        progress_bar.style.background = 'gray';
        message.textContent = percentages + '%.';
    }
}


// ========================================
// 4. Profile Photo Live Preview
// ========================================

document.addEventListener('DOMContentLoaded', function () {

    const photoInput = document.querySelector(
        'input[name="profile_photo"]'
    );

    const profilePreview = document.getElementById('profilePreview');

    if (photoInput && profilePreview) {

        photoInput.addEventListener('change', function () {

            const file = this.files[0];

            if (file) {

                // Check whether the selected file is an image
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file.');
                    this.value = '';
                    return;
                }

                // Preview the selected image immediately
                const imageURL = URL.createObjectURL(file);

                profilePreview.src = imageURL;
            }

        });

    }

});



document.addEventListener('DOMContentLoaded', function () {

    const photo_Input = document.querySelector(
        'input[name="image"]'
    );

    const profilePreview = document.getElementById('profile_Preview');

    if (photo_Input && profilePreview) {

        photo_Input.addEventListener('change', function () {

            const file = this.files[0];

            if (file) {

                // Check whether the selected file is an image
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file.');
                    this.value = '';
                    return;
                }

                // Preview the selected image immediately
                const imageURL = URL.createObjectURL(file);

                profilePreview.src = imageURL;
            }

        });

    }

});

// ========================================
// 5. Delete confirm 
// ========================================

$('[data-confirm]').on('click', e =>{
    const text = e.target.dataset.confirm || 'Are you sure?';
    if(!confirm(text)){
        e.preventDefault();
        e.stopImmediatePropagation();
    }

});
// ========================================
// 6. Clear Search Input
// ========================================

document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');

    if (searchInput && clearButton) {

        function updateClearButton() {
            clearButton.style.display = searchInput.value.length > 0
                ? 'block'
                : 'none';
        }

        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            updateClearButton();
            searchInput.focus();
        });

        searchInput.addEventListener('input', updateClearButton);

        updateClearButton();
    }

});


// ==============================
// AI Chatbot
// ==============================

document.addEventListener('DOMContentLoaded', function () {

    // Only run on AI Chatbot page
    if (
        !window.chatConfig ||
        !document.getElementById('chatForm')
    ) {
        return;
    }

    // 1. Conversation Configuration
    let conversationId = window.chatConfig.conversationId;

    // 2. DOM Elements
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const messages = document.getElementById('messages');
    const errorMessage = document.getElementById('errorMessage');
    const sendBtn = document.getElementById('sendBtn');

    const historyModal = document.getElementById('historyModal');

    // 3. Display Chat Message
    function addMessage(content, sender) {

        const messageDiv = document.createElement('div');
        messageDiv.className = 'ai-message ' + sender;

        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';

        if (sender === 'ai') {
            contentDiv.innerHTML = DOMPurify.sanitize(
                marked.parse(content)
            );
        } else {
            contentDiv.textContent = content;
        }

        messageDiv.appendChild(contentDiv);
        messages.appendChild(messageDiv);

        scrollToBottom();
    }

    // 4. Auto Scroll
    function scrollToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    // 5. Send Message
    chatForm.addEventListener('submit', async (e) => {

        e.preventDefault();

        const message = messageInput.value.trim();

        if (!message) return;

        errorMessage.textContent = '';

        addMessage(message, 'member');

        messageInput.value = '';

        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';

        try {

            const response = await fetch('../../api/chat.php', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify({
                    message: message,
                    conversation_id: conversationId
                })

            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Unable to get AI response.');
            }

            conversationId = data.conversation_id;

            addMessage(data.reply, 'ai');

        } catch (error) {

            errorMessage.textContent = error.message;

        } finally {

            sendBtn.disabled = false;
            sendBtn.textContent = 'Send';

            messageInput.focus();

        }

    });

    // 6. Start New Conversation
    document.getElementById('newChatBtn').addEventListener('click', () => {

        conversationId = '';

        messages.innerHTML = `
            <div class="ai-welcome">
                <h2>Hello! Welcome to Story Vault AI</h2>
                <p>Ask me anything about books or reading.</p>
            </div>
        `;

        messageInput.value = '';
        errorMessage.textContent = '';

        messageInput.focus();

    });

    // 7. Chat History Modal
    document.getElementById('historyBtn').addEventListener('click', () => {
        historyModal.style.display = 'flex';
    });

    document.getElementById('closeHistoryBtn').addEventListener('click', () => {
        historyModal.style.display = 'none';
    });

    historyModal.addEventListener('click', (e) => {

        if (e.target === historyModal) {
            historyModal.style.display = 'none';
        }

    });

    // 8. Initial Scroll
    scrollToBottom();

});


// ==============================
// 9. Borrowing Report Chart
// ==============================

document.addEventListener('DOMContentLoaded', function () {

    const ctx = document.getElementById('borrowingChart');

    // Only run on Report Management page
    if (ctx && typeof Chart !== 'undefined') {
        let chartLabel = '';
        let yAxisLabel = '';

        if (reportType === 'borrowing') {
            chartLabel = 'Total Borrowings';
            yAxisLabel = 'Number of Borrowings';

        } else if (reportType === 'book') {
            chartLabel = 'Book Quantity';
            yAxisLabel = 'Quantity';

        } else if (reportType === 'member') {
            chartLabel = 'Total Members';
            yAxisLabel = 'Number of Members';

        } else if (reportType === 'fine') {
            chartLabel = 'Fine Amount';
            yAxisLabel = 'Amount (RM)';

        } else if (reportType === 'reservation') {
            chartLabel = 'Total Reservations';
            yAxisLabel = 'Number of Reservations';
        }

        new Chart(ctx, {

            type: 'bar',

            data: {
                labels: chartLabels,

                datasets: [{
                    label: chartLabel,
                    data: chartValues,
                    backgroundColor: '#8b5cf6',
                    borderColor: '#7c3aed',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        title: {
                            display: true,
                            text:yAxisLabel
                        }
                    },

                    x: {
                        title: {
                            display: true,
                            text: 'Period'
                        }
                    }
                }
            }

        });

    }

});