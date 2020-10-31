import {collectionType} from "./components/collection_type";

const $ = require('jquery');
require('./components/select2');

$(function() {
    collectionType($('div#logbook_entry_shellDamages, div#logbook_entry_finish_shellDamages'), 'Ajouter une avarie');
    $('#logbook_entry_start_crewMembers, #logbook_entry_crewMembers').select2({
        templateResult: formatState,
    });
    $('#logbook_entry_start_shell, #logbook_entry_shell').select2({
        templateResult: formatState,
    });

    function formatState (state) {
        const badges = $(state.element).data('badges');

        if (undefined === badges) {
            return state.text;
        }

        let $state = $(document.createTextNode(state.text));
        for (let i = 0, len = badges.length; i < len; i++) {
            $state = $state.add($('<span class="badge badge-' + badges[i]['color'] + ' ml-2">' + badges[i]['value'] + '</span>'));
        }

        return $state;
    }
});
