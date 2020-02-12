import {collectionType} from "./components/collection_type";

const $ = require('jquery');
require('./components/select2');

$(function() {
    collectionType($('div#logbook_entry_finish_shellDamages, div#logbook_entry_shellDamages'), 'Ajouter une avarie');
    $('#logbook_entry_new_crewMembers, #logbook_entry_crewMembers').select2();
    $('#logbook_entry_new_shell, #logbook_entry_shell').select2({
        templateResult: formatState,
    });

    function formatState (data) {
        let badge = $(data.element).data('badge');

        if ('competition' === badge) {
            return $(document.createTextNode(data.text)).add($('<span class="badge badge-primary ml-2">Compétition</span>'));
        }

        if ('personnal' === badge) {
             return $(document.createTextNode(data.text)).add($('<span class="badge badge-warning ml-2">Personnel</span>'));
        }

        return data.text;
    }
});
