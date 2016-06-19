document.observe("dom:loaded", function () {

    $$('input, select, textarea').each(function (el) {
        el.on('change', function (ev) {
            ev.stop();
            Validation.validate(el);
        });
    });
});

Validation.add('validate-hostname', 'Please enter a valid hostname. For example: example.com, domain.com', function (v) {
    return Validation.get('IsEmpty').test(v) || /^((?:(?!www\.|\.).)+\.[a-zA-Z0-9.]+)$/i.test(v)
});

Validation.add('validate-nip', 'Please enter a valid NIP', function (v) {
    var steps = [6, 5, 7, 2, 3, 4, 5, 6, 7], checkNip = false, intSum = 0, nip = v.replace(/^\s+ \s+$/g, "").replace(/[^0-9]+/, ""), rest = null, controlNumber = null;

    if (nip.length !== 10) {
        checkNip = false;
    } else {
        for (var i = 0; i < steps.length; i++) {
            intSum += parseInt(steps[i]) * parseInt(nip.charAt(i));
        }

        rest = intSum % 11;

        controlNumber = (rest === 10) ? 0 : rest;

        if (controlNumber === parseInt(nip.charAt(9))) {
            checkNip = true;
        } else {
            checkNip = false;
        }
    }

    return Validation.get('IsEmpty').test(v) || checkNip;
});

Validation.add('validate-postcode', 'Please enter a valid postcode. For example: 08-567', function (v) {
    return Validation.get('IsEmpty').test(v) || /^([0-9]{2})\-([0-9]{3})$/i.test(v);
});

Validation.add('validate-bank-account', 'Please enter a valid bank account number', function (nrb) {
    var check = false, nrbStart = nrb;
    nrb = nrb.replace(/[^0-9]+/g, '');
    var Wagi = new Array(1, 10, 3, 30, 9, 90, 27, 76, 81, 34, 49, 5, 50, 15, 53, 45, 62, 38, 89, 17, 73, 51, 25, 56, 75, 71, 31, 19, 93, 57);
    if (nrb.length === 26) {
        nrb = nrb + "2521";
        nrb = nrb.substr(2) + nrb.substr(0, 2);
        var Z = 0;
        for (var i = 0; i < 30; i++) {
            Z += nrb[29 - i] * Wagi[i];
        }
        if (Z % 97 == 1) {
            check = true;
        } else {
            check = false;
        }
    } else {
        check = false;
    }
    return Validation.get('IsEmpty').test(nrbStart) || check;
});

Validation.add('validate-greater-than-field', 'Please enter the value greather than', function (v, el) {
    var classes = $(el).readAttribute('class').split(' '), field = null, test = null;
    for (var i = 0; i < classes.length; i++) {
        test = /validate\-greater\-than\-field-[A-Za-z0-9\-_]{2,}/ig.test(classes[i]);
        if (test) {
            field = classes[i].split('validate-greater-than-field-');
            field = field[1];
            break;
        }
    }
    //console.log($(field)); 
    if ($(field)) {
        if (parseFloat(v) <= parseFloat($(field).getValue())) {
            this.error += ' "' + ($(field).up('tr').select('td.label').first().select('label').first().innerHTML) + '" = ' + $(field).getValue();
            return false;
        } else {
            return true;
        }

    } else {
        return false;
    }

});

Validation.add('validate-lower-than-field', 'Please enter the value lower than', function (v, el) {
    var classes = $(el).readAttribute('class').split(' '), field = null, test = null;
    for (var i = 0; i < classes.length; i++) {
        test = /validate\-lower\-than\-field-[A-Za-z0-9\-_]{2,}/ig.test(classes[i]);
        if (test) {
            field = classes[i].split('validate-lower-than-field-');
            field = field[1];
            break;
        }
    }

    if ($(field)) {
        if (parseFloat(v) >= parseFloat($(field).getValue())) {
            this.error += ' "' + ($(field).up('tr').select('td.label').first().select('label').first().innerHTML) + '" = ' + $(field).getValue();
            return false;
        } else {
            return true;
        }

    } else {
        return false;
    }

});

Validation.add('validate-cardid', 'Please enter a valid ID card', function (v) {
    var card_id = v, checkCardId = true;

    if (!/[A-Z]{3}[0-9]{6}/.test(card_id) || card_id.length !== 9) {
        checkCardId = false;
    }
    else
    {
        var card_id = card_id.toUpperCase();
        var letterValues = [
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z'];
        function getLetterValue(letter)
        {
            for (j = 0; j < letterValues.length; j++)
                if (letter == letterValues[j])
                    return j;
            return -1;
        }


        for (var i = 0; i < 3; i++)
            if (getLetterValue(card_id[i]) < 10)
                checkCardId = false;

        for (var i = 3; i < 9; i++)
            if (getLetterValue(card_id[i]) < 0 || getLetterValue(card_id[i]) > 9)
                checkCardId = false;


        var sum = 7 * getLetterValue(card_id[0]) +
                3 * getLetterValue(card_id[1]) +
                1 * getLetterValue(card_id[2]) +
                7 * getLetterValue(card_id[4]) +
                3 * getLetterValue(card_id[5]) +
                1 * getLetterValue(card_id[6]) +
                7 * getLetterValue(card_id[7]) +
                3 * getLetterValue(card_id[8]);
        sum %= 10;
        if (sum != getLetterValue(card_id[3]))
            checkCardId = false;

    }

    return Validation.get('IsEmpty').test(v) || checkCardId;
});

Validation.add('validate-pesel', 'Please enter a valid PESEL', function (v) {
    var pesel = v.replace(/^\s+ \s+$/g, "").replace(/[^0-9]+/, ""), sex, month, year, birthday, sum = 0, checkPesel = true,
            scales = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];

    if (!/[0-9]{11}/.test(pesel) || pesel.length !== 11 || pesel === '00000000000') {
        checkPesel = false;
    } else {

        var sexNumber = pesel.substr(9, 1);

        if (sexNumber % 2 === 0) {
            sex = 1;
        } else {
            sex = 2;
        }

        if (parseInt(pesel.charAt(2) + pesel.charAt(3)) >= 81 && parseInt(pesel.charAt(2) + pesel.charAt(3)) <= 92) {
            month = parseInt(pesel.charAt(2) + pesel.charAt(3)) - 80;
            year = parseInt(pesel.charAt(0) + pesel.charAt(1)) + 1800;
        } else if (parseInt(pesel.charAt(2) + pesel.charAt(3)) >= 1 && parseInt(pesel.charAt(2) + pesel.charAt(3)) <= 12) {
            month = parseInt(pesel.charAt(2) + pesel.charAt(3));
            year = parseInt(pesel.charAt(0) + pesel.charAt(1)) + 1900;
        } else if (parseInt(pesel.charAt(2) + pesel.charAt(3)) >= 21 && parseInt(pesel.charAt(2) + pesel.charAt(3)) <= 32) {
            month = parseInt(pesel.charAt(2) + pesel.charAt(3)) - 20;
            year = parseInt(pesel.charAt(0) + pesel.charAt(1)) + 2000;
        } else {
            checkPesel = false;
        }

        if (checkPesel) {
            try {
                birthday = new Date(year, month, parseInt(pesel.charAt(4) + pesel.charAt(5)));
            } catch (e) {
                checkPesel = false;
            }

            if (checkPesel) {
                for (var i = 0; i < 10; i++) {
                    sum += scales[i] * parseInt(pesel.charAt(i));
                }

                var result = 10 - (sum % 10);
                result = parseInt(result === 10 ? 0 : result);

                if (result === parseInt(pesel.charAt(10))) {
                    checkPesel = true;
                } else {
                    checkPesel = false;
                }
            }
        }
    }

    return Validation.get('IsEmpty').test(v) || checkPesel;
});

Validation.add('validate-invoice-pattern', 'Please enter a valid invoice pattern. For example: %number%/%month%/%year%/KEX', function (v) {
    return Validation.get('IsEmpty').test(v) || /^.*(%number%|%year%|%month%).*(%number%|%year%|%month%).*(%number%|%year%|%month%).*$/i.test(v);
});

Validation.add('validate-regon', 'Please enter a valid REGON', function (v) {
    var regon = v.replace(/[\ \-]/gi, ''), checkRegon, intb, intControlNr, i;
    if (regon.length == 9) {
        var arrSteps = new Array(8, 9, 2, 3, 4, 5, 6, 7), intSum = 0;
        for (i = 0; i < 8; i++) {
            intSum += arrSteps[i] * regon[i];
        }
        intb = intSum % 11;
        intControlNr = (intb == 10) ? 0 : intb;
        if (intControlNr == regon[8]) {
            checkRegon = true;
        }
    }
    else if (regon.length == 14) {
        var arrSteps = new Array(2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8), intSum = 0;
        for (i = 0; i < 13; i++) {
            intSum += arrSteps[i] * regon[i];
        }
        intb = intSum % 11;
        if (intb == regon[13]) {
            checkRegon = true;
        }
    }
    else {
        checkRegon = false;
    }

    return Validation.get('IsEmpty').test(v) || checkRegon;
});

Validation.add('validate-krs', 'Please enter a valid KRS', function (v) {
    var krs = v.replace(/^\s+ \s+$/g, "").replace(/[^0-9]+/, ""), checkKrs;
    if (!/[0-9]{10}/.test(krs) || krs.length !== 10) {
        checkKrs = false;
    } else {
        checkKrs = true;
    }
    return Validation.get('IsEmpty').test(v) || checkKrs;
});

Validation.add('region_vs_postcode', 'Wpisany kod pocztowy nie jest częścią wybranego województwa', function (v) {
    var kodP = $(document.getElementById('postcode')).value.replace('-', '');
    ;

    if (kodP == "") {
        return true;
    }
    var rege = [];
    switch (v)
    {
        case '501':
            rege = [/(5\d\d\d\d)/, /(672\d\d)/];
            break;
        case '502':
            rege = [/(85\d\d\d)/, /(86\d\d\d)/, /(87\d\d\d)/, /(88\d\d\d)/, /(891\d\d)/,
                /(892\d\d)/, /(894\d\d)/, /(895\d\d)/];
            break;
        case '503':
            rege = [/(085\d\d)/, /(20\d\d\d)/, /(21\d\d\d)/, /(22\d\d\d)/, /(23\d\d\d)/,
                /(24\d\d\d)/];
            break;
        case '504':
            rege = [/(65\d\d\d)/, /(66\d\d\d)/, /(671\d\d)/, /(673\d\d)/, /(674\d\d)/,
                /(68\d\d\d)/, /(69\d\d\d)/];
            break;
        case '505':
            rege = [/(263\d\d)/, /(90\d\d\d)/, /(91\d\d\d)/, /(92\d\d\d)/, /(93\d\d\d)/,
                /(94\d\d\d)/, /(95\d\d\d)/, /(961\d\d)/, /(962\d\d)/, /(97\d\d\d)/,
                /(98\d\d\d)/, /(99\d\d\d)/];
            break;
        case '506':
            rege = [/(30\d\d\d)/, /(31\d\d\d)/, /(32\d\d\d)/, /(33\d\d\d)/, /(341\d\d)/,
                /(342\d\d)/, /(344\d\d)/, /(345\d\d)/, /(346\d\d)/, /(347\d\d)/,
                /(38245)/, /(38246)/, /(38247)/, /(383\d\d)/];
            break;
        case '507':
            rege = [/(00\d\d\d)/, /(01\d\d\d)/, /(02\d\d\d)/, /(03\d\d\d)/, /(04\d\d\d)/,
                /(05\d\d\d)/, /(06\d\d\d)/, /(07\d\d\d)/, /(081\d\d)/, /(082\d\d)/,
                /(083\d\d)/, /(084\d\d)/, /(09\d\d\d)/, /(264\d\d)/, /(265\d\d)/,
                /(266\d\d)/, /(267\d\d)/, /(268\d\d)/, /(269\d\d)/, /(271\d\d)/,
                /(273\d\d)/, /(963\d\d)/, /(965\d\d)/];
            break;
        case '508':
            rege = [/(45\d\d\d)/, /(46\d\d\d)/, /(471\d\d)/, /(472\d\d)/, /(473\d\d)/,
                /(48\d\d\d)/, /(49\d\d\d)/];
            break;
        case '509':
            rege = [/(35\d\d\d)/, /(36\d\d\d)/, /(37\d\d\d)/, /(381\d\d)/, /(3820\d)/,
                /(3821\d)/, /(3822\d)/, /(3823\d)/, /(38241)/, /(38242)/,
                /(38243)/, /(38244)/, /(384\d\d)/, /(385\d\d)/, /(386\d\d)/,
                /(387\d\d)/, /(39\d\d\d)/];
            break;
        case '510':
            rege = [/(15\d\d\d)/, /(16\d\d\d)/, /(17\d\d\d)/, /(18\d\d\d)/, /(191\d\d)/,
                /(192\d\d)/];
            break;
        case '511':
            rege = [/(762\d\d)/, /(771\d\d)/, /(772\d\d)/, /(773\d\d)/, /(80\d\d\d)/,
                /(81\d\d\d)/, /(821\d\d)/, /(822\d\d)/, /(824\d\d)/, /(825\d\d)/,
                /(83\d\d\d)/, /(84\d\d\d)/, /(896\d\d)/];
            break;
        case '512':
            rege = [/(343\d\d)/, /(40\d\d\d)/, /(41\d\d\d)/, /(42\d\d\d)/, /(43\d\d\d)/,
                /(44\d\d\d)/, /(474\d\d)/];
            break;
        case '513':
            rege = [/(25\d\d\d)/, /(260\d\d)/, /(261\d\d)/, /(262\d\d)/, /(272\d\d)/,
                /(274\d\d)/, /(275\d\d)/, /(276\d\d)/, /(28\d\d\d)/, /(29\d\d\d)/];
            break;
        case '514':
            rege = [/(10\d\d\d)/, /(11\d\d\d)/, /(12\d\d\d)/, /(13\d\d\d)/, /(14\d\d\d)/,
                /(193\d\d)/, /(194\d\d)/, /(195\d\d)/, /(823\d\d)/];
            break;
        case '515':
            rege = [/(60\d\d\d)/, /(61\d\d\d)/, /(62\d\d\d)/, /(63\d\d\d)/, /(64\d\d\d)/,
                /(774\d\d)/, /(893\d\d)/];
            break;
        case '516':
            rege = [/(70\d\d\d)/, /(71\d\d\d)/, /(72\d\d\d)/, /(73\d\d\d)/, /(74\d\d\d)/,
                /(75\d\d\d)/, /(760\d\d)/, /(761\d\d)/, /(78\d\d\d)/];
            break;
        default:
            break;
    }
    var ret = false;

    rege.forEach(function (element, index) {
        if (element.test(kodP)) {
            ret = true;
        }
    });
    return Validation.get('IsEmpty').test(v) || ret;

});

Validation.getDateFromFormat = function (format, value) {

    var dateSettings = new Array();
    var match = format.match(/(D|M|Y|d|m|y)+.*(D|M|Y|d|m|y)+.*(D|M|Y|d|m|y)+/i);
    var date = false;
    if (match.length) {
        match.forEach(function (el, index, arr) {
            if (/(D|d)+/i.test(el)) {
                if (dateSettings.indexOf('day') < 0) {
                    dateSettings.push('day');
                }
            }
            if (/(M|m)+/i.test(el)) {
                if (dateSettings.indexOf('month') < 0) {
                    dateSettings.push('month');
                }
            }
            if (/(Y|y)+/i.test(el)) {
                if (dateSettings.indexOf('year') < 0) {
                    dateSettings.push('year');
                }
            }
        });
    }

    if (dateSettings.length) {
        var space = '(\\\|\\/|\\-|_|\\.|\\040)', numbers = '(\\d{2,4})',
                dateMatch = new RegExp('^' + numbers + space + numbers + space + numbers + '$', 'i'),
                match = value.match(dateMatch), tmp = new Array(), spaceRegexp;
        if (match && match.length) {
            match = match.slice(1, 6);
            spaceRegexp = new RegExp(space, 'i');
            match.forEach(function (el) {
                if (!spaceRegexp.test(el)) {
                    tmp.push(el);
                }
            });
            match = tmp;
            if (match.length === dateSettings.length) {
                var yearIndex = dateSettings.indexOf('year');
                var monthIndex = dateSettings.indexOf('month');
                var dayIndex = dateSettings.indexOf('day');
                if (yearIndex > -1 && monthIndex > -1 && dayIndex > -1) {
                    date = new Date(match[yearIndex], match[monthIndex] - 1, match[dayIndex]);
                }
            }
        }
    }
    return new Date(date);
}

Validation.add('validate-date', 'Wpisana data jest niepoprawna', function (v, el) {
    var format = el.readAttribute('data-format'), result = true;
    if (!format) {
        var date = new Date(v);
        return date;
    }
    return Validation.get('IsEmpty').test(v) || Validation.getDateFromFormat(format, v);
});

Validation.add('validate-date-future', 'Wpisana data musi być w przyszłości', function (v, el) {
    var format = el.readAttribute('data-format'), result = false;
    if (format) {
        var date = Validation.getDateFromFormat(format, v);
        if (date) {
            var now = new Date();
            now.setHours(0);
            now.setMinutes(0);
            now.setSeconds(0);
            now.setMilliseconds(0);
            result = date.getTime() > now.getTime();
        } else {
            this.error = 'Wpisana data jest niepoprawna';
            result = false;
        }
    }

    return Validation.get('IsEmpty').test(v) || result;
});

Validation.add('validate-date-past', 'Wpisana data musi być w przeszłości', function (v, el) {
    var format = el.readAttribute('data-format'), result = false;
    if (format) {
        var date = Validation.getDateFromFormat(format, v);
        if (date) {
            var now = new Date();
            now.setHours(0);
            now.setMinutes(0);
            now.setSeconds(0);
            now.setMilliseconds(0);
            result = date.getTime() < now.getTime();
        } else {
            this.error = 'Wpisana data jest niepoprawna';
            result = false;
        }
    }

    return Validation.get('IsEmpty').test(v) || result;
});

Validation.add('validate-date-range', 'Wpisana data jest niepoprawna', function (v, el) {
    try {
        var format = el.readAttribute('data-format'), result = false;
        if (format) {
            var date = Validation.getDateFromFormat(format, v);
            if (date) {
                var classes = el.readAttribute('class').split(' '), field = null, test = null;
                for (var i = 0; i < classes.length; i++) {
                    test = /validate\-date\-range\-(greater|lower)\-than\-field\-[A-Za-z0-9\-_]{2,}/ig.test(classes[i]);
                    if (test) {
                        field = classes[i].split(/validate\-date\-range\-(greater|lower)\-than\-field\-/ig);
                        field = field;

                        if (field.length === 3) {

                            if ($(field[2])) {
                                var newClass;
                                newClass = 'validate-date-range-' + (field[1] === 'greater' ? 'lower' : 'greater') + '-than-field-' + el.readAttribute('id');
                                if (!$(field[2]).hasClassName('validate-date-range')) {
                                    $(field[2]).addClassName('validate-date-range');
                                }
                                if (!$(field[2]).hasClassName(newClass)) {
                                    $(field[2]).addClassName(newClass);
                                    Validation.validate($(field[2]));
                                }

                                var secondDate = Validation.getDateFromFormat($(field[2]).readAttribute('data-format'), $(field[2]).getValue());
                                if (secondDate) {
                                    if (field[1] === 'greater') {
                                        result = date.getTime() > secondDate.getTime();
                                        if (!result) {
                                            this.error = 'Data musi być większa od daty z pola "' + $(field[2]).up('tr').select('label')[0].innerHTML + '"';
                                        }
                                    } else if (field[1] === 'lower') {
                                        result = date.getTime() < secondDate.getTime();
                                        if (!result) {
                                            this.error = 'Data musi być mniejsza od daty z pola "' + $(field[2]).up('tr').select('label')[0].innerHTML + '"';
                                        }
                                    }

                                } else {
                                    result = true;
                                }
                            } else {
                                this.error = 'Pole o ID "' + field[2] + '" nie istnieje!';
                                result = false;
                            }
                        }
                        break;
                    }
                }

            } else {
                result = false;
            }
        }
        return Validation.get('IsEmpty').test(v) || result;
    } catch (e) {
        return false;
    }
});

//Validation.add('validate-have-loan', '', function (v, el) {
//    new Ajax.Request('/admin/pesel/validate', {
//        parameters: {
//            pesel: v
//        },
//        evalScripts: true,
//        onFailure: null,
//        onComplete: null,
//        onSuccess: function (transport) {
//            console.log('transport');
//        }.bind(this)
//    });
//
//    return Validation.get('IsEmpty').test(v) || result;
//});

