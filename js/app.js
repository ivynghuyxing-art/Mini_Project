function toggleAllPassword() {
    const $inputs = $('.password_input');
    const $btns = $('#allToggleBtn');
    const isHiddens = $inputs.prop('type') === 'password';

    $inputs.prop('type', isHiddens ? 'text' : 'password');
    $btns.text(isHiddens ? "Hide All Passwords" : "Show All Passwords");
}
function toggleViews() {
    const $input = $('#password');
    const $btn = $('#toggleBtn');
    const isHidden = $input.attr('type') === 'password';

    $input.attr('type', isHidden ? 'text' : 'password');
    $btn.text(isHidden ? "Hide Password" : "Show Password");
}

function evalPasswordStrength() {
    const strength = document.getElementById('password').value;
    const percentage_password = document.getElementById('percentage_password');

    let percentage = 0;

    if(strength.length >= 8 && strength.length <=20){
        percentage += 20;
    }
    if (strength.match(/[!@#$%^&*()_+]/)) {
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

    if(percentage === 100){
        percentage_password.style.backgroundColor = 'green';
    }
    else if(percentage >=80){
        percentage_password.style.backgroundColor = 'lightgreen';
    }
    else if(percentage >=60){
        percentage_password.style.backgroundColor = 'orange';
    }
    else if(percentage >=20){
        percentage_password.style.backgroundColor = 'red';
    }else {
        percentage_password.style.backgroundColor = 'gray';
    }

}
