var ajaxMonogoForm = new Class.create();
var ajaxForm;

// Clone varienForm prototype and overvride needed methods
ajaxMonogoForm.prototype = Object.clone(varienForm.prototype);

ajaxMonogoForm.prototype.submit = function (url) {
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
            this._submit();
        }
        return true;
    }
    return false;
};

ajaxMonogoForm.prototype._submit = function () {
    var $form = $(this.formId);
    if (this.submitUrl) {
        $form.action = this.submitUrl;
    }
    new Ajax.Request($form.action, {
        loaderArea: $form.parentNode,
        parameters: $form.serialize(true),
        evalScripts: true,
        onFailure: null,
        onComplete: null,
        onSuccess: function (transport) {
            try {
                var responseText = transport.responseText.replace(/>\s+</g, '><');
                var messagesContainer = $($form.parentNode).select('ul.messages');
                if (messagesContainer.length) {
                    messagesContainer[0].remove();
                }

                if (transport.responseText.isJSON()) {

                    var response = transport.responseText.evalJSON();

                    if (!response.success) {
                        $form.parentNode.insert({
                            top: '<ul class="messages"><li class="error-msg">' + response.error + '</li></ul>'
                        });
                    }

                    if (response.success) {
                        $form.parentNode.insert({
                            top: '<ul class="messages"><li class="success-msg">' + response.msg + '</li></ul>'
                        });
                    }

                    setTimeout(function () {
                        messagesContainer = $($form.parentNode).select('ul.messages');
                        messagesContainer[0].hide();
                    }, 1500);

                    if (response.htmlToUpdateId && response.updateHtml) {
                        if (typeof $(response.htmlToUpdateId) === 'object') {
                            $(response.htmlToUpdateId).replace(response.updateHtml);

                            setTimeout(function () {
                                $(response.htmlToUpdateId).parentNode.scrollTo();
                            }, 2000);
                        }
                    }

                    if (typeof response.additionalData === 'object') {
                        if (typeof setAdditionalData === 'function') {
                            setAdditionalData(response.additionalData);
                        }
                    }



                    $form.reset();

                    if(typeof ajaxMonogoForm.prototype.callback === 'function'){
                        ajaxMonogoForm.prototype.callback();
                    }

                    if (response.ajaxExpired && response.ajaxRedirect) {
                        setLocation(response.ajaxRedirect);
                    }
                } else {
                    $form.parentNode.update(responseText);
                }
            } catch (e) {
                console.log(e);
                alert('ERROR!');
            }
        }.bind(this)
    });
};

document.observe("dom:loaded", function () {
    ajaxForm = new ajaxMonogoForm('ajax_form');
});



