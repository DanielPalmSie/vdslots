accSave('exclude', {}, function(ret){
    if(typeof ret['msg'] !== 'undefined'  && ret['msg'] != '') {
        mboxMsg(ret['msg'], true, function(){ goTo('/?signout=true'); }, 500, undefined, true)
    } else {
        goTo('/?signout=true');
    }
});