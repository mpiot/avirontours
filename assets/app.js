/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// start the Stimulus application
import './bootstrap';

// import jQuery & Bootstrap
import '@popperjs/core';
import 'bootstrap';
import { Toast } from 'bootstrap';

document.addEventListener("DOMContentLoaded", function() {
    // Sidebar display
    if (window.innerWidth < 1200) {
        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggle-sidebar');

        sidebar.classList.remove('show');
        toggleSidebar.setAttribute('aria-expanded', 'false');
    }

    // let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    // tooltipTriggerList.map(function (tooltipTriggerEl) {
    //     return new bootstrap.Tooltip(tooltipTriggerEl);
    // })

    let toastElList = [].slice.call(document.querySelectorAll('.toast'))
    let toastList = toastElList.map(function (toastEl) {
        return new Toast(toastEl);
    })
    toastList.forEach(toast => toast.show());
});
