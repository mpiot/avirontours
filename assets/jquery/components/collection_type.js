const $ = require('jquery');

export function collectionType(container, buttonText, buttonId, fieldStart, allowDelete, functions) {
    // If the container is not a div, exit the function
    if (!container.length > 0) {
        return;
    }

    if (buttonId === undefined) {
        buttonId = null;
    }

    if (fieldStart === undefined) {
        fieldStart = false;
    }

    if (allowDelete === undefined) {
        allowDelete = true;
    }

    if (functions === undefined) {
        functions = [];
    }

    // Delete the first label (the number of the field), and the required class
    container.children('fieldset').find('legend:first').remove();

    // Create and add a button to add new field
    let $addButton = $('<a href="#" class="btn btn-outline-secondary btn-sm"><span class="fas fa-plus"></span> '+buttonText+'</a>');

    if (buttonId) {
        $addButton.attr('id', buttonId);
    }

    container.append($addButton);

    // Add a click event on the add button
    $addButton.click(function(e) {
        e.preventDefault();
        // Call the addField method
        addField(container);
        return false;
    });

    // Define an index to count the number of added field (used to give name to fields)
    let index = container.children('fieldset').length;

    // If the index is > 0, fields already exists, then, add a deleteButton to this fields
    if (index > 0 && true === allowDelete) {
        container.children('fieldset').each(function() {
            addDeleteButton($(this));
            addFunctions($(this));
        });
    }

    // If we want to have a field at start
    if (true === fieldStart && 0 === index) {
        addField(container);
    }

    // The addField function
    function addField(container) {
        // Replace some value in the « data-prototype »
        // - "__name__label__" by the name we want to use, here nothing
        // - "__name__" by the name of the field, here the index number
        let $prototype = $(container.attr('data-prototype')
            .replace(/class="col-sm-2 control-label required"/, 'class="col-sm-2 control-label"')
            .replace(/<legend class="col-form-label required">/, '')
            .replace(/__name__label__/g, '')
            .replace(/__name__/g, index));

        // Add a delete button to the new field
        addDeleteButton($prototype);

        // If there are supplementary functions
        addFunctions($prototype);

        // Add the field in the form
        $addButton.before($prototype);

        // Increment the counter
        index++;
    }

    // A function called to add deleteButton
    function addDeleteButton(prototype) {
        // First, create the button
        let $deleteButton = $('<div class="align-self-end ml-3 mb-3"><a href="#" class="btn btn-danger btn-sm"><span class="fas fa-trash-alt"></span></a></div>');
        prototype.wrapInner( '<div class="flex-fill"></div>');

        // Add the button on the field
        prototype.append($deleteButton);
        prototype.wrapInner( '<div class="d-flex"></div>');

        // Create a listener on the click event
        $deleteButton.click(function(e) {
            e.preventDefault();
            // Remove the field
            prototype.remove();
            return false;
        });
    }

    function addFunctions(prototype) {
        // If there are supplementary functions
        if (functions.length > 0) {
            // Do a while on functions, and apply them to the prototype
            for (let i = 0; functions.length > i; i++) {
                functions[i](prototype);
            }
        }
    }
}