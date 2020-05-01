import {collectionType} from "./components/collection_type";

const $ = require('jquery');
require('./components/select2');

$(function() {
    collectionType($('div#user_licenses, div#user_edit_licenses'), 'Ajouter une license', null, true, false);
});
