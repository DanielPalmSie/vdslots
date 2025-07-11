$(function () {
    licFuncs.initNationalityPopup();
})

licFuncs.initNationalityPopup = function () {
    $(document).on('change', '#nationality-select', function () {
        licFuncs.nationalityPopupHandler().validateOnChange($('#nationality-select'));
    });

    $(document).on('change', '#place-of-birth-input', function () {
        licFuncs.nationalityPopupHandler().validateOnChange($('#place-of-birth-input'));
    });
}

licFuncs.showNationalityAndPOBPopup = function () {
    var nationalityPopupHandler = licFuncs.nationalityPopupHandler();
    nationalityPopupHandler.showNationalityPopup();
}


licFuncs.nationalityPopupHandler = function (){
    return {
        getCommonPopupParams: function (popup) {
            return {
                module: 'Licensed',
                file: popup,
                boxtitle: 'msg.title',
                closebtn: 'no',
                top_left_icon: true
            };
        },

        getCommonPopupExtraOptions: function (width) {
            return isMobile() ? {width: '100%'} : {width: width};
        },

        showNationalityPopup: function () {
            var params = this.getCommonPopupParams('add_nationality_popup'),
                extraOptions = this.getCommonPopupExtraOptions(500);
            extBoxAjax('get_html_popup', 'nationality-popup-box' || 'mbox-msg', params, extraOptions);
        },

        validateOnChange: function (element) {
            var id = element.attr('id');
            var val = element.val();

            switch (id) {
                case 'nationality-select':
                    this.showhideerror('.nationality-error', val);
                    return val;
                case 'place-of-birth-input':
                    this.showhideerror('.place-of-birth-error', val);
                    return val;
            }
        },

        showhideerror: function (elementid, show){
            var selector = $('.nationality-main-popup');

            if(!show){
                selector.find(elementid).removeClass('hidden');
            } else {
                selector.find(elementid).addClass('hidden');
            }
        },

        sendSelected: function () {
            //verification before saving
            var nationality = this.validateOnChange($('#nationality-select'));
            var placeOfBirth = this.validateOnChange($('#place-of-birth-input'));

            if(nationality != '' && placeOfBirth != ''){
                saveAccCommon('save_nationality_pob', {nationality: nationality, pob: placeOfBirth}, function (res) {

                    if (res.success) {
                        mboxClose('nationality-popup-box', function () {
                            mboxMsg(res.msg, true, function(){ execNextPopup(); }, 400);
                        });
                        return;
                    }

                    mboxMsg(res.msg, true, function () {
                        jsReloadBase();
                    }, 400);
                });
            }

        },
    }
}
