
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
// 6. Count character 
// ========================================

const message = document.getElementById('description');
const count = document.getElementById('count');

message.addEventListener('input', () =>{
    count.textContent= message.value.length;
});