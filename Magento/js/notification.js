// Active button id (users or products)


var activeButtonId = null;
document.observe("dom:loaded", function () { 

    for (var i = 0; i < $$('[data-modal-button]').length; i++) {
        //Modal button clicked and show modal with grid
        $$('[data-modal-button]')[i].observe('click', function (event) {
            event.stop();
            var element = Event.element(event);
            var modal = Modalbox.show(element.readAttribute('data-modal-button'), {title: this.title, width: document.viewport.getDimensions().width * 0.75});
            if ($('loading-mask').length) {
                $('loading-mask').remove(); //remove ajax mask
            }
            activeButtonId = element.readAttribute('id');
            return false;
        });
    }

    $('html-body').on('click', '.selectItems', function (event) { //if items were selected
        event.stop();
        var items = {};
        $$('#select_grid_id input.checkbox:checked').each(function (row, i) {
            items[i] = {
                id: row.value,
                name: row.up().next('td').innerHTML.trim()
            };
        });

        if (typeof items[0] !== 'undefined') {
            showSelected(items); //show selected item in form in list
            saveSelectedSession(items); //save selected products or users to session
        } else {
            return false;
        }
    });

    $('html-body').on('click', 'a[href=#removeItem]', function (event) { //item remove from form
        Event.stop(event);
        var
                element = event.target.up('li'),
                id = element.readAttribute('data-id'),
                data = function () {
                    try {
                        return JSON.parse($$('input' + event.target.up('td').select('button').first().readAttribute('data-referer-to')).first().getValue());
                    } catch (e) {
                        return [];
                    }
                }(),
                index = data.indexOf(id);

        while (data.indexOf(id) >= 0) {
            data.splice(data.indexOf(id), 1);
        }
        
        data = data.unique();

        $$('input' + event.target.up('td').select('button').first().readAttribute('data-referer-to')).first().setValue(JSON.stringify(data));

        saveSelectedSession(null, id); //remove from session by id

        element.remove();
        return false;
    });



    $('html-body').on('change', 'input[name=notificationType]', function (event) { //notification type was changed - change fields visible
        event.stop();
        if (event.target.value) {
            $$('input[name=notificationType]').each(function (e) {
                if ($(e.value)) {
                    $(e.value).setStyle({
                        display: 'none'
                    });
                }
            });

            if ($(event.target.value)) {
                $(event.target.value).setStyle({
                    display: 'table-row'
                });

                if (event.target.value === 'users_type') {
                    activeButtonId = 'usersSelect';
                    $('all_users_container').setStyle({
                        display: 'table-row'
                    });
                } else if (event.target.value === 'products_type') {
                    activeButtonId = 'productsSelect';
                    $('all_users_container').hide();
                } else {
                    $('all_users_container').hide();
                }
            }
        }
        return true;
    });

    $('all_users').on('change', function (event) {
        event.stop();
        if (this.checked) {
            $('users_type').hide();
        } else {
            $('users_type').setStyle({
                display: 'table-row'
            });
        }
        return true;
    });


    triggerEvent($$('input[name=notificationType][checked]')[0], 'change'); //trigger event change for notification type


});

/**
 * 
 * @param object items - json
 * @param boolean old - if populated in edit action
 * @returns void
 */
var showSelected = function (items, old) {
    var button = $$('button#' + activeButtonId).first(), itemsIds = function () {
        try {
            return JSON.parse($$('input' + button.readAttribute('data-referer-to')).first().getValue());
        } catch (e) {
            return [];
        }
    }();

    itemsIds = itemsIds.unique();

    if (button) {

        button.insert({
            after: '<ul>' + (function () {
                var html = '';
                for (var i in items) {

                    if (typeof items[i].id !== 'undefined' && (old ? true : itemsIds.indexOf(items[i].id) < 0)) {
                        itemsIds.push(items[i].id);
                        html += '<li data-id="' + items[i].id + '">' + items[i].name + ' <a href="#removeItem"><i class="fa fa-times"></i></a></li>\n';
                    }
                }
                return html;
            }()) + '</ul>'
        });

        if (itemsIds.length) {
            $$('input' + button.readAttribute('data-referer-to')).first().setValue(JSON.stringify(itemsIds.unique()));
        }
    }
};

var saveSelectedSession = function (items, removeId) {
    new Ajax.Request(saveSessionUrl, {
        method: 'post',
        parameters: {
            items: JSON.stringify(items),
            removeId: removeId,
            type: activeButtonId
        },
        evalScripts: true,
        onFailure: null,
        onComplete: null,
        onSuccess: function (transport) {
            try {
                if (transport.responseText.isJSON()) {

                    var response = transport.responseText.evalJSON();

                    return true;
                }
            } catch (e) {
                alert('ERROR!');
            }
        }.bind(this)
    });
};

function triggerEvent(element, eventName) {
    // safari, webkit, gecko
    if (document.createEvent)
    {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent(eventName, true, true);

        return element.dispatchEvent(evt);
    }

    // Internet Explorer
    if (element.fireEvent) {
        return element.fireEvent('on' + eventName);
    }
}

// Extend Array prototype with unique method
Array.prototype.unique = function () {
    var unique = [];
    for (var i = 0; i < this.length; i++) {
        if (unique.indexOf(this[i]) == -1) {
            unique.push(this[i]);
        }
    }
    return unique;
};
