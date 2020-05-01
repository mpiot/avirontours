import {collectionType} from "./components/collection_type";

const $ = require('jquery');
require('./components/select2');

$(function() {
    collectionType($('div#season_seasonCategories'), 'Ajouter une catégorie', null, true, false);
});
