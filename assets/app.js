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
import 'popper.js';
import 'bootstrap';
import $ from "jquery";

$('.custom-file-input').on('change', function(event) {
    let inputFile = event.currentTarget;

    $(inputFile).parent()
        .find('.custom-file-label')
        .html(inputFile.files[0].name);
});

$(document).ready(() => {
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
    $('.toast').toast('show');
});
