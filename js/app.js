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
    const progress_bar = document.getElementById('percentage_password');
    const message = document.getElementById('message_password');

    let percentages = 0;

    if(strength.length >= 8 && strength.length <= 20){
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

        if(percentages === 100){
            progress_bar.style.background = 'green';
            message.textContent = percentages +'%. The password is strong.';
        }
        else if(percentages >=80){
            progress_bar.style.background = 'lightgreen';
            message.textContent = percentages +'%. The password is moderate.';
        }
        else if(percentages >=60){
            progress_bar.style.background = 'orange';
            message.textContent = percentages +'%. The password is weak.';
        }
        else if(percentages >=20){
            progress_bar.style.background = 'red';
            message.textContent = percentages +'%. The password is very weak.';
        }else {
            progress_bar.style.background = 'gray';
            message.textContent = percentages +'%.';
            
        }

    }
document.getElementById('password').addEventListener('input', evalPasswordStrength);
evalPasswordStrength();

document.addEventListener("click", function(e){
    const link=e.target.closest(".ajax-link");
    if(!link)
        return;
    e.preventDefault();

    const url=link.href;
    fetch(url)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('#main-content');

            if(!newContent){
                console.error('No element with id "main-content" found in the fetched HTML.');
                return;
            }
            document.querySelector('#main-content').innerHTML = newContent.innerHTML;
            history.pushState({}, '', url);

            document.title = doc.title;
        });
});