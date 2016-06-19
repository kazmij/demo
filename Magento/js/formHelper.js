/**
 * Hide elements with class hide-element and remove 'required-entry' class
 */
document.observe("dom:loaded", function () {
    if ($$('.hide-element')) {
        $$('.hide-element').each(function (el) {
            if (el.hasClassName('required-entry')) {
                el.removeClassName('required-entry').addClassName('required-entry-was');
            }
            if (!(el.getAttribute('type') === 'checkbox' || el.getAttribute('type') === 'radio')) {
                //el.setValue(''); //reset value
            }
            el.up('tr').hide();
        });
    }

    $$('#edit_form input.disable-edit, #edit_form textarea.disable-edit, #edit_form select.disable-edit').each(function (e) {
        e.writeAttribute('disabled', true);
        if (e.tagName === 'SELECT') {
            e.writeAttribute('disabled', true);
        }
        if (e.readAttribute('type') === 'checkbox' || e.readAttribute('type') === 'radio') {
            e.writeAttribute('disabled', true);
        }
    });

    // Can edit with status, can attach documents
    if ($('can_edit_with_status') && $('can_attach_documents_wth_status')) {
        $('can_edit_with_status').on('change', function (ev, el) {
            if (parseInt(el.getValue()) === 0) {
                $('can_attach_documents_wth_status').select('option').each(function (el) {
                    console.log(el.value);
                    if (parseInt(el.value) === 0) {
                        el.writeAttribute('selected', true);
                    } else {
                        el.writeAttribute('selected', false);
                    }
                });
                $('can_attach_documents_wth_status').writeAttribute('disabled', true);
            } else {
                $('can_attach_documents_wth_status').writeAttribute('disabled', false);
            }
        });

        triggerEvent($('can_edit_with_status'), 'change');
    }

});

var disableFrom = function (id) {

    if ($(id)) {
        $$('#' + id + ' input, #' + id + ' textarea, #' + id + ' select').each(function (e) {
            if (String(e.readAttribute('name')) !== 'form_key' && (String(e.readAttribute('type')) !== 'hidden')) {
                e.writeAttribute('disabled', true);
            }
        });
    }

};

var disableUploader = function () {
    var elements = $$(' .uploader');
    if ($$('.notification-global').length) {
        $$('.notification-global')[0].remove();
    }
    if (elements.length) {
        elements.each(function (e) {
            e.up('.entry-edit').remove();
        });
    }

};

var disableForSettlement = function (leaveAjaxForm) {
    document.observe("dom:loaded", function () {

        if ($$('.addElementForm, .removeElementForm').length) {
            $$('.addElementForm, .removeElementForm').each(function (e) {
                e.remove();
            });
        }

        if (typeof leaveAjaxForm === 'undefined') {
            if ($('ajax_form')) {
                $('ajax_form').remove();
            }
        }

        if ($$('.content-header').length) {
            $$('.content-header')[$$('.content-header').length - 1].remove();
        }

        if ($$('.uploader').length) {
            $$('.uploader').each(function (e) {
                e.up('.entry-edit').remove();
            })
        }

        if ($$('.notification-global').length) {
            $$('.notification-global')[0].remove();
        }
    });
}

/**
 * Trigger event in prototype
 * @param object element
 * @param string eventName
 * @returns event
 */
function triggerEvent(element, eventName) {
    // safari, webkit, gecko
    if (document.createEvent) {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent(eventName, true, true);

        return element.dispatchEvent(evt);
    }

    // Internet Explorer
    if (element.fireEvent) {
        return element.fireEvent('on' + eventName);
    }
}

/**
 * Field show/hide dependencies method, Array with dependeces as json required as param
 * @param object json
 * @returns void
 */
var fieldDependencies = function (json) {
    if (typeof json === 'object') {
        /**
         * Shwo or hide filed
         * @param Array show - show elements array
         * @param Array hide - hide elements array
         * @param boolean reverse - if reverse event
         * @returns void
         */
        var showHide = function (show, hide, reverse) {
            if (show) {
                for (var i = 0; i < show.length; i++) {
                    if ($(show[i])) {
                        if (reverse) { //if reverse event: value was xxx but now is other
                            $(show[i]).addClassName('hide-element').up('tr').hide(); //hide element and his row
                            if ($(show[i]).hasClassName('required-entry')) {
                                $(show[i]).removeClassName('required-entry').addClassName('required-entry-was'); //remove required but add that was required to backup required
                            }
                            if (!($(show[i]).getAttribute('type') === 'checkbox' || $(show[i]).getAttribute('type') === 'radio')) {
                                $(show[i]).setValue('');
                            }
                        } else {
                            $(show[i]).removeClassName('hide-element').up('tr').show();
                            if ($(show[i]).hasClassName('required-entry-was')) {
                                $(show[i]).removeClassName('required-entry-was').addClassName('required-entry');
                            }
                        }
                    }
                }
            }
            if (hide) {
                for (var i = 0; i < hide.length; i++) {
                    if ($(hide[i])) {
                        if (reverse) {
                            $(hide[i]).removeClassName('hide-element').up('tr').show();
                            if ($(hide[i]).hasClassName('required-entry-was')) {
                                $(hide[i]).removeClassName('required-entry-was').addClassName('required-entry');
                            }
                        } else {
                            $(hide[i]).addClassName('hide-element').up('tr').hide();
                            if ($(hide[i]).hasClassName('required-entry')) {
                                $(hide[i]).removeClassName('required-entry').addClassName('required-entry-was');
                            }
                            if (!($(hide[i]).getAttribute('type') === 'checkbox' || $(hide[i]).getAttribute('type') === 'radio')) {
                                $(hide[i]).setValue('');
                            }
                        }
                    }
                }
            }
        };
        var show = {}, hide = {}, el = {};
        for (var key in json) {
            var val;
            el[key] = $(key);
            if (el[key]) {
                val = null;
                //if val in json exist
                if (typeof json[key]['value'] !== 'undefined') {
                    val = json[key]['value'];
                }
                //if show in json exist
                if (typeof json[key]['show'] !== 'array') {
                    show[key] = json[key]['show'];
                }
                //if hide in json exist
                if (typeof json[key]['hide'] !== 'array') {
                    hide[key] = json[key]['hide'];
                }
                if ((show[key] || hide[key])) { //only if show or hide array exist can contuine
                    el[key].formHelper = {
                        show: show[key],
                        hide: hide[key]
                    };

                    if ((el[key].tagName === 'INPUT' && el[key].getAttribute('type') === 'text') || el[key].tagName === 'TEXTAREA') { // On this types keyup event
                        var keyupTimeout;
                        el[key].on('keyup', function (event) {
                            var showTemp = event.target.formHelper.show;
                            var hideTemp = event.target.formHelper.hide;
                            if (keyupTimeout) {
                                clearTimeout(keyupTimeout);
                                keyupTimeout = null;
                            }
                            keyupTimeout = setTimeout(function () {
                                if (val) { //if json val exist
                                    if (String(event.target.getValue()) === String(val)) {
                                        showHide(showTemp, hideTemp);
                                    } else {
                                        showHide(showTemp, hideTemp, true);
                                    }
                                } else { //if no exist json val
                                    if (String(event.target.getValue())) { //if value of element exist then show and hide
                                        showHide(showTemp, hideTemp);
                                    } else { //else the same in reverse order
                                        showHide(showTemp, hideTemp, true);
                                    }
                                }
                            }, 100);
                        });
                        triggerEvent(el[key], 'keyup'); //trigger event in prototype
                    } else {
                        el[key].on('change', function (event) {
                            var showTemp = event.target.formHelper.show;
                            var hideTemp = event.target.formHelper.hide;
                            if (val) { //only if not checkbox or radio and val exist
                                if (el[key].getAttribute('type') === 'checkbox' || el[key].getAttribute('type') === 'radio') {
                                    if (String(event.target.getValue()) === String(val) && el[key].checked) {
                                        showHide(showTemp, hideTemp);
                                    } else {
                                        showHide(showTemp, hideTemp, true);
                                    }
                                } else {
                                    if (String(event.target.getValue()) === String(val)) {
                                        showHide(showTemp, hideTemp);
                                    } else {
                                        showHide(showTemp, hideTemp, true);
                                    }
                                }
                            } else {
                                if (el[key].getAttribute('type') === 'checkbox' || el[key].getAttribute('type') === 'radio') { //if checkbox or radio
                                    if (el[key].checked) { //checked event
                                        showHide(showTemp, hideTemp);
                                    } else {
                                        showHide(showTemp, hideTemp, true);
                                    }
                                } else {
                                    if (String(event.target.getValue())) {
                                        showHide(showTemp, hideTemp);
                                    } else {
                                        showHide(showTemp, hideTemp, true);
                                    }
                                }
                            }
                        });
                        triggerEvent(el[key], 'change');
                    }
                }
            }
        }
    } else {
        if (typeof console === 'object') {
            console.log('Field Dependencies: invalid json!!');
        }
    }
};

/******************************************************************************/
/********************FIELD VISIBILITY FOR APPLICATIONS************************/
var fieldVisibility = function (json) {
    if (typeof json === 'object') {
        var tag, typeEl, type, el, keyupTimeout, target, obj, val,
                showOrHide = function (el) {
                    tag = el.tagName;
                    val = el.getValue();

                    for (var key in el.fields) {
                        if ($(key)) {
                            for (var i in el.fields[key]) {
                                obj = el.fields[key][i];
                                for (var k in obj) {
                                    if ($(k)) {
                                        while ($(k).hasClassName('required-entry')) {
                                            $(k)
                                                    .removeClassName('required-entry')
                                        }
                                        $(k).hide()
                                                .up('tr')
                                                .hide();
                                        typeEl = $(k).readAttribute('type');

                                        if (typeEl === 'checkbox' || typeEl === 'radio') {
                                            $(k).checked = false;
                                        } else {
                                            if ($(k).tagName !== 'SELECT') {
                                                if (!$(k).readAttribute('data-value')) {
                                                    $(k).writeAttribute('data-value', $(k).getValue());
                                                }
                                                $(k).setValue('');
                                            }
                                        }

                                        if ($(k).up('tr').select('label span').length) {
                                            $(k).up('tr').select('label span')[0].remove();
                                        }


                                    }
                                }
                            }
                        }
                    }

                    for (var key in el.fields) {
                        if ($(key)) {
                            type = $(key).getAttribute('type');
                            for (var i in el.fields[key]) {
                                if (String($(key).getValue()) === String(i) && (type === 'radio' || type === 'checkbox' ? $(key).checked : true)) {
                                    obj = el.fields[key][i];
                                    for (var k in obj) {
                                        if ($(k)) {
                                            if (obj[k]) {
                                                $(k)
                                                        .show()
                                                        .up('tr')
                                                        .show();
                                                if (!$(k).hasClassName('required-entry-was')) {
                                                    $(k).addClassName('required-entry');
                                                    $(k).up('tr').select('label')[0].insert(' <span class="required">*</span>');
                                                }
                                                if ($(k).readAttribute('data-value')) {
                                                    $(k).setValue($(k).readAttribute('data-value'));
                                                } else {
                                                    $(k).setValue($(k).readAttribute('value'));
                                                }
                                            }

                                        }
                                    }
                                }
                            }
                        }
                    }
                };

        for (var key in json) {
            if ($(key)) {
                el = $(key);
                tag = el.tagName;
                type = el.getAttribute('type');
                if ((tag === 'INPUT' && type === 'text') || type === 'TEXTAREA') {
                    el.on('keyup', function (e) {
                        target = e.target;
                        target.fields = json;
                        if (keyupTimeout) {
                            clearTimeout(keyupTimeout);
                            keyupTimeout = null;
                        }
                        keyupTimeout = setTimeout(function () {
                            showOrHide(target);
                        }, 150);
                    });
                    triggerEvent(el, 'keyup');
                } else {
                    el.on('change', function (e) {
                        target = e.target;
                        target.fields = json;
                        showOrHide(target);
                    });
                    triggerEvent(el, 'change');
                }
            }
        }
    } else {
        if (typeof console === 'object') {
            console.log('fieldVisibility: invalid json!!');
        }
    }
};

/***************************FIELDSET DEPENDENCY*********************************/
/*******************************************************************************/
var fieldsetDependency = function (json) {
    if (typeof json === 'object') {
        var el, k, parent, type, buttons,
                hideElements = function (el) {
                    el.select('input, textarea, select').each(function (e) {
                        while (e.hasClassName('required-entry')) {
                            e.removeClassName('required-entry');
                        }
                        type = e.readAttribute('type');
                        e.addClassName('required-entry-was');
                        if (type === 'checkbox' || type === 'radio') {
                            e.checked = false;
                        } else {
                            e.setValue('');
                        }
                    });
                },
                canHide = function (el) {
                    var canHide = true;
                    el.select('input, textarea, select').each(function (e) {
                        type = e.readAttribute('type');
                        if (type === 'checkbox' || type === 'radio' || e.tagName === 'SELECT') {
                            if (e.tagName === 'SELECT') {
                                if (e.getValue() && String(e.getValue()) !== String(0)) {
                                    canHide = false;
                                    return;
                                }
                            } else {
                                if (e.checked) {
                                    canHide = false;
                                    return;
                                }
                            }
                        } else {
                            if (e.getValue()) {
                                canHide = false;
                                return;
                            }
                        }
                    });
                    return canHide;
                },
                normalizeNumbers = function (sectionName) {
                    $$('[data-fieldset-section=' + sectionName + '][data-show-section=1]').each(function (e, i) {
                        var header = e.select('.entry-edit-head h4')[0], headerText = header.innerHTML;
                        headerText = headerText.trim().replace(/\d+$/i, i + 1);
                        header.innerHTML = headerText;
                    });
                };
        $('html-body').on('click', 'button.add.addElementForm', function (event) {
            Event.stop(event);
            var section = event.target.up('[data-fieldset-section]');
            if (section) {
                var sectionName = section.readAttribute('data-fieldset-section');
                var groupSections = $$('[data-fieldset-section=' + sectionName + '][data-show-section=0]');
                if (groupSections.length) {
                    var nextSection = groupSections[0];
                    nextSection.writeAttribute('data-show-section', 1);
                    nextSection.show();
                    normalizeNumbers(sectionName);
                    nextSection.select('input, textarea, select').each(function (e) {
                        if (e.hasClassName('required-entry-was')) {
                            e.removeClassName('required-entry-was').addClassName('required-entry');
                        }
                    });
                    var position = nextSection.cumulativeOffset();
                    window.scrollTo(0, position[1] - 70);
                } else {
                    event.target.writeAttribute('disabled', true);
                }
            }
            return false;
        });

        $('html-body').on('click', 'button.remove.removeElementForm', function (event) {
            Event.stop(event);
            var section = event.target.up('[data-fieldset-section]');
            if (section) {
                section.writeAttribute('data-show-section', 0);
                section.hide();
                hideElements(section);
                normalizeNumbers(section.readAttribute('data-fieldset-section'));
                $$('[data-fieldset-section=' + section.readAttribute('data-fieldset-section') + ']')[0].select('button.add.addElementForm')[0].writeAttribute('disabled', null);
                var latest = $$('[data-fieldset-section=' + section.readAttribute('data-fieldset-section') + '][data-show-section=1]');
                if (latest.length) {
                    latest[latest.length - 1].scrollTo();
                    var position = latest[latest.length - 1].cumulativeOffset();
                    window.scrollTo(0, position[1] - 70);
                }
            }
            return false;
        });

        for (var key in json) {
            k = 0;
            for (var i = 0; i < json[key].length; i++) {
                if ($$('.fieldset-grouped.fieldset-group-' + json[key][i]).length) {
                    el = $$('.fieldset-grouped.fieldset-group-' + json[key][i])[0];
                    parent = el.up('div');
                    parent.writeAttribute('data-fieldset-section', key);
                    parent.writeAttribute('data-show-section', 0);
                    var id = parent.select('.fieldset-grouped')[0].readAttribute('id');
                    id = id.replace('group_fields', '');
                    parent.writeAttribute('data-group-id', id);
                    if (k > 0) {
                        if (canHide(parent)) {
                            parent.hide();
                            hideElements(parent);
                        }
                        if (parent.select('.form-buttons').length) {
                            buttons = parent.select('.form-buttons')[0];
                            buttons.insert({
                                top: '<button type="button" class="remove removeElementForm">' + (Translator.translate('Remove element')) + ' <i class="fa fa-minus-square"></i></button>'
                            });
                        }
                    } else {
                        parent.writeAttribute('data-show-section', 1);
                        if (parent.select('.form-buttons').length) {
                            buttons = parent.select('.form-buttons')[0];
                            if ($$('[data-fieldset-section=' + parent.readAttribute('data-fieldset-section') + ']').length) {
                                buttons.insert({
                                    top: '<button type="button" class="add addElementForm">' + (Translator.translate('Add new element')) + ' <i class="fa fa-plus-circle"></i></button>'
                                });
                            }
                        }
                    }
                    k++;
                }
            }
        }
    } else {
        if (typeof console === 'object') {
            console.log('fieldsetDependency: invalid json!!');
        }
    }
};
