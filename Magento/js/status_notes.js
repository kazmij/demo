document.observe("dom:loaded", function () {

    //hide attribute field
    if ($('loan_amount_launched')) {
        $('loan_amount_launched').removeClassName('required-entry').up('tr').hide();
    }

    if ($('loan_amount_proposed')) {
        $('loan_amount_proposed').removeClassName('required-entry').up('tr').hide();
    }


    var statusElem = document.getElementById('status_code');
    if (statusElem != null) {
        Event.observe($('status_code'), 'change', function (event, e) {
            var element = Event.element(event);

            if (typeof statusToShowLoanField !== 'undefined' && $F(element) && String(statusToShowLoanField) === String($F(element))) {
                $('loan_amount_launched').addClassName('required-entry').up('tr').show();
                if ($('loan_amount_proposed').getValue() && !$('loan_amount_launched').getValue()) {
                    $('loan_amount_launched').setValue($('loan_amount_proposed').getValue());
                } else if (typeof applicationLoanAmount !== 'undefined' && applicationLoanAmount && !$('loan_amount_launched').getValue()) {

                    $('loan_amount_launched').setValue(applicationLoanAmount);
                }

            } else {
                $('loan_amount_launched').removeClassName('required-entry').up('tr').hide();
            }

            if (typeof statusToShowLoanProposedField !== 'undefined' && $F(element) && String(statusToShowLoanProposedField) === String($F(element))) {
                $('loan_amount_proposed').addClassName('required-entry').up('tr').show();
            } else {
                $('loan_amount_proposed').removeClassName('required-entry').up('tr').hide();
            }

            var notes = (function () {
                try {
                    return JSON.parse(element.readAttribute('data-status-notes').replace(/'/ig, '"'));
                } catch (e) {
                    return null;
                }
            })();
            if (typeof notes === 'object') {
                if (typeof notes[element.value] !== 'undefined') {
                    $('note').setValue(notes[element.value]);
                }
            }
        });
    }


    $('html-body').on('click', 'button.save', function (event) {
        event.stop();
        if (typeof can_change_status_to_document_completed === 'boolean' && can_change_status_to_document_completed === true) {
            if ($$('input[name=uploaded_files]').length) {
                if ($$('input[name=uploaded_files]')[0].getValue()) {
                    var form = $$('input[name=uploaded_files]').first().up('form');
                    var validator = new Validation(form);
                    if (validator.validate()) {
                        var saveStatus = confirm(Translator.translate('Do you want to change application status to Compleed Documents ?'));
                        if (saveStatus) {
                            form.insert({
                                top: '<input type="hidden" class="statusDocumentsCompleted" name="statusDocumentsCompleted" value="1"/>'
                            });
                            editForm = new varienForm('edit_form', '');
                            editForm.submit();
                            return false;
                        }
                    }
                }
            }
        }
    });
});

// overwrite submit method to save before status if is set in subform
varienForm.prototype.submit = function (url) {
    if (typeof varienGlobalEvents != undefined) {
        varienGlobalEvents.fireEvent('formSubmit', this.formId);
    }
    this.errorSections = $H({});
    this.canShowError = true;
    this.submitUrl = url;
    if (this.validator && this.validator.validate()) {
        if (this.validationUrl) {
            this._validate();
        }
        else {
            if ($('status_code').getValue() && ajaxForm) {
                var obj = this;
                ajaxMonogoForm.prototype.callback = function(){
                    obj.submit();
                };
                ajaxForm.submit();
            } else {
                this._submit();
            }
        }
        return true;
    }
    return false;
};