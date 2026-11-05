function toggleView() {
    const $input = $('#password');
    const $btn = $('#toggleBtn');
    const isHidden = $input.attr('type') === 'password';

    $input.attr('type', isHidden ? 'text' : 'password');
    $btn.text(isHidden ? "Hide Password" : "Show Password");
}

function toggleAllPasswords() {
    const $inputs = $('.pass-input');
    const $btn = $('#allToggleBtn');
    const isHidden = $inputs.attr('type') === 'password';

    $inputs.attr('type', isHidden ? 'text' : 'password');
    $btn.text(isHidden ? "Hide All Passwords" : "Show All Passwords");
}

function evalPasswordStrength() {
    const strength = document.getElementById('password').value;
    const percentage_password = document.getElementById('percent_pass');

    let percentage = 0;

    if(strength.length >= 8 && strength.length <=20){
        percentage += 20;
    }
    if (strength.match(/[!@#$%^&*(),.?":{}|<>]/)) {
        percentage += 20;
    }
    if (strength.match(/[A-Z]/)) {
        percentage += 20;
    }
    if (strength.match(/[a-z]/)) {
        percentage += 20;
    }
    if (strength.match(/[0-9]/)) {
        percentage += 20;
    }

    percentage_password.style.width = percentage + '%';

    switch(percentage){
        case 100:
            percentage_password.style.backgroundColor = 'green';
            break;
        case 80:
            percentage_password.style.backgroundColor = 'lightgreen';
            break;
        case 50:
                percentage_password.style.backgroundColor = 'orange';
                break;
        case 20:
                percentage_password.style.backgroundColor = 'red';
                break;
        default:
                percentage_password.style.backgroundColor = 'transparent';
                break;
    }

}