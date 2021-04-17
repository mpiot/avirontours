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
import { Offcanvas, Toast } from 'bootstrap';

document.addEventListener("DOMContentLoaded", function() {
    // Sidebar
    let sidebarToggle = document.getElementById('sidebarToggle');
    let sidebar = document.getElementById('sidebar');
    let bsSidebar = new Offcanvas(sidebar, {
        backdrop: false,
        keyboard: false,
        scroll: true
    })
    let displayed = false;

    sidebarToggle.addEventListener('click', function() {
        event.stopPropagation();
        displayed = !displayed;
        bsSidebar.toggle();
    })

    sidebar.addEventListener('hide.bs.offcanvas', function(event) {
        if (true === displayed && window.innerWidth >= 1200) {
            event.preventDefault();
        }
    })

    if (window.innerWidth >= 1200) {
        displayed = true;
        bsSidebar.show();
    }

    // Toasts
    let toastElList = [].slice.call(document.querySelectorAll('.toast'))
    let toastList = toastElList.map(function (toastEl) {
        return new Toast(toastEl);
    })
    toastList.forEach(toast => toast.show());
});
