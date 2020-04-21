import {collectionType} from "./components/collection_type";

const $ = require('jquery');
require('./components/select2');

$(function() {
    collectionType($('div#user_seasonUsers, div#user_edit_seasonUsers'), 'Ajouter une saison', null, true, false);
});
