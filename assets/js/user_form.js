import {collectionType} from "./components/collection_type";

const $ = require('jquery');
require('./components/select2');

$(function() {
    collectionType($('div#user_medicalCertificates, div#user_edit_medicalCertificates'), 'Ajouter une attestation médicale');
});
