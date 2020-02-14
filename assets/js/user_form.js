const $ = require('jquery');
require('./components/select2');

$(function() {
    console.log('tst');
    $('#user_roles, #user_edit_roles').select2();
});
