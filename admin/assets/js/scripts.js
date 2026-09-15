document.addEventListener('DOMContentLoaded', () => {

    let hamburger = document.querySelector('.hamburger');

    let aside = document.querySelector('aside');
    let nav = document.querySelector('nav');
    let main = document.querySelector('main');

    hamburger.addEventListener('click', () => {
        aside.classList.toggle('open');
        nav.classList.toggle('open');
        main.classList.toggle('open');
    });

});