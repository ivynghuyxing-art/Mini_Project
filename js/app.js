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