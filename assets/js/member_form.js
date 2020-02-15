import {collectionType} from "./components/collection_type";

const $ = require('jquery');
require('./components/select2');

$(function() {
    collectionType($('div#member_medicalCertificates'), 'Ajouter une attestation médicale');
});
