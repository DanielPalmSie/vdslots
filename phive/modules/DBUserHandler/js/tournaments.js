var mpLeaderboardMarginBottom = 236;
var mpActionsUrl = '/phive/modules/Tournament/xhr/actions.php';
var mpWsLoaded = false;
var onMpWs = false;
var mpWsFuncs = [];
var canRebuy = false;
var fiDebug = false;
var finFunc;
var retRegRebuy = {};
var rebuyIntervalId;
var fiCalls = {};
var fiMap = {};
var gameFi = {};
var fi = { /* postMessages state variables used to communicate with the games */
    nb: 0,
 };
var mpFinished = false;
var leaderHeight = 58;
var mpSystemMessages = [];
var oNanoScroller = { scroll: 'bottom', alwaysVisible: true };
var myInfoLastLeader = null;
var leaderboardEntries = [];
var mpStartedId = false;
var spinLeft = null;

function mpWs(){
  if(hasWs() && !mpWsLoaded){
    mpWsLoaded = true;

      // For debugging purposes, can be used to send websocket calls from the server to imitate extend calls from the game.
      // Can be used to simulate having the game locally where it is not possible to load the game for real.
    doWs(mpUrls.extend, function(e) {
      fiCalls[e.data].call();
    });

      // A socket for forcing the finish popup.
      doWs(mpUrls.limit, function(e) {
          var res = JSON.parse(e.data);
          if(fiDebug)
              console.log({type: 'mpFinished', 'json': res});
          if(res.gohome == 'yes')
              finishSpinning();
          mpContentMsgs(res);
      });

      // Finished calculating notification.
      doWs(mpUrls.calculated, function(e) {
          var res = JSON.parse(e.data);
          mpContentMsgs(res);
      });

      // Updates of one's own info.
      doWs(
          mpUrls.my_info,
          function(e){
              var res = JSON.parse(e.data);
              //console.log('my_info', res);
              myInfoLastLeader = res;
              //updateMyInfoLastLeader();

              // Should not run here.
              //updateLeaderboard(res);

              // It's some other player responsible for our position update so we display it immediately
              if(mpUserId != res.user_id){
                  //handleMpWsPosUpdate(res);
                  updateLeaderboard(res, 'my info, someone else');
              }else{
                  // Are we done with the game round or not?
                  // If not we defer display of position or score to avoid spoiling.
                  //finishedSpinning() ? handleMpWsPosUpdate(res) : mpWsFuncs.push(function(){ handleMpWsPosUpdate(res); });
                  finishedSpinning() ? updateLeaderboard(res, 'my info, me') : mpWsFuncs.push(function(){ updateLeaderboard(res, 'my info, me'); });
              }
          }
      );

      // Leaderboard updates, staggered to avoid too manu updates / second.
      wsQf('mpleaderboard', wsMpInterval, function(res){
        updateLeaderboard(res);
          //handleMpWsRes(res);
      });

      // Main tournament socket and leaderboard updates.
      doWs(
          mpUrls.main,
          function(e) {
              var res = JSON.parse(e.data);
              if(res.status == 'finished' && res.eid == mpEid){
                  canRebuy = false;
                  if(parseInt(res.rebuy_times) > 0){
                      var rebFunc = function(){
                          rebuyStart('prMpRebuy', 'mp-rebuy-start', res.tid, res.eid); // Rebuy possible.
                      };
                      finishedSpinning() ? rebFunc.call() : mpWsFuncs.push(rebFunc);
                      canRebuy = true;
                  }
              }

              switch(res.wstag){
                  case 'update_msg':
                  case 'umsg':
                      // We have a user generated chat message.
                      handleChatMsg(res);
                      return;
                      break;
                  case 'smsg':
                      // We have a system generated message (freespin / bonus notification for instance).
                      handleChatMsg(res);
                      return;
                      break;
              }

              //console.log('main_bos', res);
              //updateLeaderboard(res);
              // If change position == true we need to figure out client side if our own position is below visible.
              // If it is we need to update somehow.

              if(mpUserId != res.user_id){
                  // If update does not pertain to the viewing player we display it immediately as it can't spoil anything.
                  //updateLeaderboard(res);
                  //wsQ('mpleaderboard', res);
                  wsQ('mpleaderboard', res);
              }else{
                  // If update belongs to the viewing player we need to defer if we haven't finished spinning yet,
                  // otherwise we display immediately.
                  if(finishedSpinning()){
                    //handleMpWsRes(res);
                    updateLeaderboard(res, 'board, my info, finished spinning');
                  } else {
                    mpWsFuncs.push(function(){
                      //handleMpWsRes(res);
                      updateLeaderboard(res, 'board, my info, did not finish spinning');
                    });
                  }
              }
          },
          function(){
              finishSpinning();
              execWsFuncs();
          });

    //$("#mp-leaderboard-refresh").hide();
  }
}


/**
 * Get the height from the leaders board based on half the height of the play-box (where the game is shown).
 * How: get the height from the play box  and divide by 2 (leaders board and message board should have each 50% height)
 * and take of the leader board margin
 * If the chat is not visible it will try to take all the space available
 * @return int
 */
function getLeaderBoardHeight(){
  if($(".mp-chat-msgs").is(':visible')) {
      return Math.floor(($('#play-box').height() - mpLeaderboardMarginBottom)/2);
  }
  return Math.floor(($('#play-box').height() - mpLeaderboardMarginBottom));
}

/**
 * Get the amount of players that can fit into the leaders board depending on the current height of the #play-box
 * @return int
 */
function getLeaderBoardBoxCount(){
  return Math.floor(getLeaderBoardHeight()/leaderHeight);
}

/**
 * Update the leader board (and chatbox) height when the browser is resized so the amount of players (and chat messages)
 * that fit can be re-calculated
 * @return void
 */
function updateLeaderBoardHeight(){
  var heightCorrection = (((getLeaderBoardHeight()/leaderHeight) - getLeaderBoardBoxCount()) * leaderHeight);
  $("#leaderboard-tbl").height( (getLeaderBoardHeight() - heightCorrection) );
  $(".mp-chat-msgs").height( (getLeaderBoardHeight() + heightCorrection) );
}

// TODO can this be removed, it does not seem to have any function other than testing? /Henrik
function addSystemMess(i){
    var k = ((typeof i === 'undefined') ? 1 : i);
    for (var a = 0; a < k; a++) {
        var mess = 'hello' + '-' + a + '-' + Math.random().toString(36).substring(7);
        //console.log(mess);
        mpSystemMessages.unshift(mess);
    }
}

function rotateSystemMessage() {
  var ct = $("#mp-system-container span").data("term") || 0;
  //console.log('mpSystemMessages',ct,mpSystemMessages);
  $("#mp-system-container span")
  .data( "term", ((ct === mpSystemMessages.length -1 || ct === mpSystemMessages.length) ? 0 : ct ) )
      .text('')
      .text(mpSystemMessages[ct])
      .fadeIn(300).delay(2500)
      .fadeOut(200, function(){
          //console.log('mpSystemMessages', ct,mpSystemMessages);
          // remove the shown message (which is the first in the array) from the array
          mpSystemMessages.shift();
          // for testing
          // if(mpSystemMessages.length === 0){
          //     addSystemMess(5);
          // }
          if(mpSystemMessages.length > 0){
              rotateSystemMessage();
          }
  });
}

function handleMpWsPosUpdate(res){
    //console.log('my info start');
    //console.log(res);
    //console.log('my info end');
    // We have a spins and score update
    if(res.spins_left){
        $("#cur-mp-score-"+res.uid).html(res.score);
        $("#cur-mp-spins-"+res.uid).html(res.spins_left);
    }else
        $("#cur-mp-position-"+res.uid).html(res.pos); // Only position update, can happen as a result of spins by other players
}

function updateLeaderboard(res, descr){

  //console.log('before',res, descr,leaderboardEntries);
  var removedLeader = {};
  var leaderBoardBoxCount = getLeaderBoardBoxCount();
  var entryFound = false;

  if(!empty(res)){

    if(res.hasOwnProperty('total')){
      $("#mp-total-prize-top").html(res.total);
    }

    if(res.hasOwnProperty('user_id')){

      for ( var key in leaderboardEntries ) {

        if ( !leaderboardEntries.hasOwnProperty(key) ) {
            continue;
        }

        var leader = {
          user_id: parseInt(res['user_id']),
          pos: parseInt(res['pos']),
          dname: (empty(res['alias']) ? res['dname'] : res['alias']),
          spins_left: parseInt(!empty(res['spins_left']) ? res['spins_left'] : 0),
          win_amount: parseInt(!empty(res['win_amount']) ? res['win_amount'] : 0),
          arrow: ''
        };

        leaderboardEntries[key]['user_id'] = parseInt(leaderboardEntries[key]['user_id']);
        //console.log('user_id', leaderboardEntries[key]['user_id'], leader.user_id);
        if(leaderboardEntries[key]['user_id'] === leader['user_id']){
          // we need to re-order the received ws leader to a new position
          entryFound = true;

          // this leader need to be re-positioned so cutout from the entries
          removedLeader = leaderboardEntries.splice(key,1);

          // set its new position in the entries array (-1 because array start at 0)
          var newPos = parseInt(leader.pos - 1);

          if(res['change_pos'] === true && leaderboardEntries[newPos]?.['win_amount'] != null){
            //console.log('win_amount-arrow', parseInt(leaderboardEntries[newPos]['win_amount']), leader.win_amount);
            // we check the win_amount of the current leader which is on newPos before we insert the spliced leader into a new location
            if(parseInt(leaderboardEntries[newPos]['win_amount']) > leader.win_amount){
              leaderboardEntries[newPos]['arrow'] = 'green';
              //console.log('win_amount', 'green');
            }

            if(parseInt(leaderboardEntries[newPos]['win_amount']) < leader.win_amount){
              leaderboardEntries[newPos]['arrow'] = 'red';
              //console.log('win_amount', 'red');
            }
          }
          // insert the leader received by ws to the newPos into the entries
          leaderboardEntries.splice(newPos, 0, leader);

          // check if the inserted leader went up or down in the list
          if(removedLeader[0].hasOwnProperty('pos')){
            var pos = parseInt((removedLeader[0]['pos'] - 1));
          } else {
            var pos = parseInt(key);
          }

          if(res['change_pos'] === true){
            //console.log('pos', pos, newPos);
            if(pos >= newPos){
              leaderboardEntries[newPos]['arrow'] = 'green';
              //console.log('arrow-pos', 'green');
            }

            if(pos < newPos){
              leaderboardEntries[newPos]['arrow'] = 'red';
              //console.log('arrow-pos', 'red');
            }
          }
        }
      }
      if(entryFound === false){
        leaderboardEntries.push(res);
      }
    }
  }

  var mpLeaderboard = $("#mp-tab-table");
  var newRow = mpLeaderboard.find('tr').last().clone();

  var rows = '';
  var bLeaderShown = false;

  for (i = 0; i < leaderboardEntries.length; i++) {

    if ( !leaderboardEntries.hasOwnProperty(i) ) {
        continue;
    }

    if(leaderboardEntries[i]['user_id'] != userId && bLeaderShown === false && i >= (leaderBoardBoxCount-1)){
      // we still need our own position to be shown so continue to next entry to see if user_id matches
      continue;
    } else if(leaderboardEntries[i]['user_id'] != userId){
      if(i >= leaderBoardBoxCount){
        continue;
      }
    } else {
       // we found user_id match so its shown
       bLeaderShown = true;
       spinLeft = JSON.parse(leaderboardEntries[i]['spins_left']);
    }

    var arrow = '';
    if(leaderboardEntries[i].hasOwnProperty('arrow') && leaderboardEntries[i]['arrow'] !== ''){
      arrow = statusArrow(leaderboardEntries[i]['arrow'],true);
      //leaderboardEntries[i]['arrow'] = '';
    }

    rows += '<tr id="mpuser-'+ leaderboardEntries[i]['user_id']+'">';
    rows += '<td class="race-position">'+(i+1)+'</td>';
    rows += '<td class="race-fname'+((leaderboardEntries[i]['user_id'] === userId) ? ' red' : '')+'">'+(empty(leaderboardEntries[i]['alias']) ? leaderboardEntries[i]['dname'] : leaderboardEntries[i]['alias'])+'</td>';
    rows += '<td class="race-left"><span class="value">' + (!empty(leaderboardEntries[i]['spins_left']) ? leaderboardEntries[i]['spins_left'] : 0) + '</span>'+'<span class="text">' + $('#txtSpins').text() + '</span></td>';
    rows += '<td class="race-amount"><span class="value">' + (!empty(leaderboardEntries[i]['win_amount']) ? leaderboardEntries[i]['win_amount'] : 0) + '</span>'+'<span class="text">' + $('#txtScore').text() + '</span></td>';
    rows += '<td class="race-arrow">' + arrow + '</td>';
    rows += '</tr>';

  }

  mpLeaderboard.html(rows);

}

function refreshLeaderBoard(curPlayTid){

  ajaxGetBoxHtml({func: 'playBoxLeaderBoardTop', tid: curPlayTid}, cur_lang, 'TournamentLobbyBox', function(ret){
    $('#mp-topinfo-holder').html(ret);
  });

  ajaxGetBoxHtml({func: 'playBoxLeaderBoard', tid: curPlayTid, leaders: Math.floor(getLeaderBoardHeight()/leaderHeight)}, cur_lang, 'TournamentLobbyBox', function(ret){
    $('#leaderboard-content').html(ret);
      if (typeof resizePlayBox !== "undefined") {
          resizePlayBox();
      }
    $('#mp-nano').nanoScroller(oNanoScroller);

    mpWs();
    //mpContentMsgs();
  });
}


function selectCss(el){
  el.parent().removeClass('gradient-dark').addClass('gradient-default');
}

function unselectCss(els){
  els.parent().removeClass('gradient-default').addClass('gradient-dark');
}

//tournament-format-btns
function mpGetSelected(parent){
  var str = $("."+parent).find('.gradient-default').find('div').attr('id');
  if(str)
    return getSuffix(str);
  return 'all';
}

function submitAlias(){
  $.post(mpActionsUrl, {alias: $("#alias").val(), lang: cur_lang, action: 'set-alias'}, function(ret){
    $('#error').html(ret.msg);
    if(ret.status == 'ok'){
      $('#error').removeClass('error').addClass('ok');
      setTimeout(function(){ mboxClose('mp-reg-start'); }, 2000);
    }
  }, 'json');
}

function mpAction(options, func, type){
  if(typeof type === 'undefined')
    type = 'json';
  options.lang = cur_lang;
  var doFunc = function(ret){
    if(typeof func != 'undefined')
      func.call(this, ret);
    minuteCdown();
  };
  if(type == 'json')
    $.get(mpActionsUrl, options, doFunc, 'json');
  else
    $.get(mpActionsUrl, options, doFunc);
}

function updateLobby(tid){
  var iframeId = '#mbox-iframe-mp-lobby-box';
  var iframe = $(iframeId);
  if(iframe.length < 1)
    return;
  iframeContents = iframe.contents();
  ajaxGetBoxHtml({func: 'printHtmlContent', tid: tid}, cur_lang, 'TournamentLobbyBox', function(ret){
    $("#tournament-lobby-wrapper", iframeContents).html(ret);
  });
  $(iframeId)[0].contentWindow.minuteCdown();
}

function mpRegClose(boxId){
    tournamentInfo(curTid, '#mbox-iframe-mp-box');
    updateLobby(curTid);
    mboxClose('mp-start');
    mboxClose(boxId);
}

function mpStartGoTo(url){
    if ((typeof top.extSessHandler !== 'undefined') && (typeof top.mpUserId === 'undefined') && (top.cur_country === 'ES')) {
        if ($('#new_window').is(":checked")) {
            top.gameCloseRedirection("goToBlank(\'" + url + "\')");
        }
        else{
            top.gameCloseRedirection("goTo(\'" + url + "\')");
        }
    }
    else{
        if ($('#new_window').is(":checked"))
            goToBlank(url);
        else {
            goTo(url);
        }
    }
    mboxClose('mp-start');
}

function mpReloadBalance(){
    mboxClose('mp-rebuy-start');
    if(fiDebug)
        console.log('reloading balance with gameFi');
    if(!empty(gameFi.toFrame))
        gameFi.toFrame('reloadBalance', retRegRebuy, function(s){ }, function(e){ });
    else
        gameFi.call('reloadBalance', [], function(s){ }, function(e){ });
}

function joinQueue(tId, useTicket){
    mpAction({
        tid: tId,
        action: 'queue-reg',
        use_ticket: useTicket,
        pwd: $('#pwd').val(),
    }, function(qRes){
        if(qRes.status != 'queued'){
            mboxClose('mbox-msg', function(){
                mboxMsg(qRes.msg, true);
            });
        } else {
            // We haven't already received the started ws call, ie we're not the last registrant.
            if(mpStartedId != tId){
                var qCnt = parseInt(qRes.q_cnt);
                mboxClose('mbox-msg', function(){
                    extBoxAjax(
                        'get_raw_html',
                        'mbox-msg',
                        {module: 'Tournament', file: 'queue_cnt', cnt: qCnt},
                        {cls: 'mbox-deposit', width: '400px'}
                    );
                    // No point in counting down if we're already in the top spot.
                    if(qCnt > 1){
                        doWs(qRes.ws_cnt_url, function(e){
                            // console.log([e.data]);
                            var wsRes = JSON.parse(e.data);

                            if(wsRes.msg == 'dec_q_cnt'){
                                // Counting down.
                                qCnt = qCnt == 1 ? 1 : qCnt - 1;
                                $('#qcnt').html(qCnt);
                            }
                        });
                    }
                });
            }
        }
    });
}

function regAndRebuyCommon(options, boxId, url, wsRegResultUrl){
    if(dClick(boxId))
        return false;

    var onNoCash = function(ret){
        $("#mp-cash-balance").html(ret.balance);
        $("#mp-cash-balance-section").addClass('error');
        $("#mp-not-enough-money-msg").show(200);
        $("#mp-reg-start-dep-btn").show(200);
    };

    var onDone = function(ret){
        if(ret.status == 'no-cash'){
            onNoCash(ret);
        } else {
            // This is ugly, it should be refactored to perhaps use extBoxAjax too, if possible.
            var regOkIntvl = setInterval(function(){
                var selectors = {"#mp-reg-start-wrapper": 'mp-reg-start', ".mp-popup-content-wrapper": 'mbox-msg'};
                for(var key in selectors){
                    var val = selectors[key];
                    var closeFunc = !empty(url) ? "top.goTo('" + url + "')" : (boxId == 'mp-rebuy-start' ? 'mpReloadBalance()' : "mpRegClose('" + val + "')");
                    if($(key).length > 0){
                        $(key).html(ret.msg).append(okBtn(closeFunc));
                        clearInterval(regOkIntvl);
                        break;
                    } else {
                        showTournamentRegBox(false, true, function() {
                            $(key).html(ret.msg).append(okBtn(closeFunc));
                            clearInterval(regOkIntvl);
                        });
                        break;
                    }
                }
            }, 1000);
        }
    };

    mpAction(options, function(ret){
        retRegRebuy = ret;
        dClicks[boxId] = false;
        switch(ret.status){
            case 'queue_yes_no':
                if(!empty(wsRegResultUrl)){
                    doWs(wsRegResultUrl, function(e){
                        var wsRegRes = JSON.parse(e.data);
                        // We haven't already received the started ws call, ie we're not the last registrant.
                        if(mpStartedId != ret.t_id){
                            // The reg OK message, etc.
                            retRegRebuy = wsRegRes;
                            // We only show the finish popup if we don't already display the play start box as that box should have
                            // higher priority.
                            onDone(wsRegRes);
                        }
                    });
                }

                // Hide register popup
                mboxClose('mp-reg-start', function(){
                    joinQueue(ret.t_id, options.use_ticket);
                });
                break;

            default:
                onDone(ret);
                break;
        }
    });
}

function rebuyTournament(eid, url){
  $('.rebuy-button').prop('disabled', true); // to prevent double submissions
  clearInterval(rebuyIntervalId);
  regAndRebuyCommon({eid: eid, action: 'tournament-rebuy'}, 'mp-rebuy-start', url);
}

function regTournament(useTicket, wsRegResultUrl){
    if(empty(useTicket))
        useTicket = 'no';

    regAndRebuyCommon({
        tid: curTid,
        action: 'tournament-reg',
        use_ticket: useTicket,
        pwd: $('#pwd').val(),
    }, 'mp-reg-start', undefined, wsRegResultUrl);
}

/**
 * Unregister from bos
 *
 * @returns {boolean}
 */
function unregTournament(regLbl, action){
    action = empty(action) ? 'tournament-unreg' : action;
    if (dClick('tournament-unreg')) {
        return false;
    }
    mpAction({tid: curTid, action: action}, function(ret){
        dClicks['tournament-unreg'] = false;
        $('#mp-unreg-start').find('button').hide();
        $('#mp-unreg-close').show();
        $('#mp-unreg-close').find('button').show();
        $('#mp-unreg-info').hide();
        if (ret != false) {
            $('#mp-unreg-success-msg').show();
            //var mainLobby = $("#mbox-iframe-mp-box");
            //$("#register-btn", mainLobby.contents()).removeClass('btn-cancel-default-l').addClass('gradient-default').html(regLbl);
            tournamentInfo(curTid, "#mbox-iframe-mp-box");
            updateLobby(curTid);
        } else {
            $('#mp-unreg-fail-msg').show();
        }
    });
}


function mpBox(html, id, target, width){
  var options = {
    id: id,
    type: 'html',
    cls: 'mbox-deposit',
    globalStyle: {overflow: 'hidden'},
    overlayOpacity: 0.7,
    content: html
  };

  if(typeof width != 'undefined')
    options.width = width;

  if(target == 'this')
    $.multibox(options);
  else if(target == 'top')
    top.$.multibox(options);
  else
    parent.$.multibox(options);
}

function toLobbyWin(tid){
  toLobby(llink('/mp-lobby/'), undefined, tid);
}

function toLobby(qUrl, target, tid) {

    if (typeof tid != 'undefined') {
        curTid = tid;
    }

    if (whenIe()) return;

    if (typeof tid == 'undefined') {
      tid = curTid;
    }

    if (typeof qUrl == 'undefined') {
      qUrl = '/mp-lobby/';
    }

    var options = {
        url: qUrl + '?tid=' + tid,
        id: 'mp-lobby-box',
        type: 'iframe',
        width: '820px',
        height: '430px',
        cls: 'mbox-deposit',
        globalStyle: {overflow: 'hidden'},
        overlayOpacity: 0.7,
        animateResize: true,
        callb: function (){
            var tournamentLobbyHeight = $("#mbox-iframe-" + this.id).contents().find(".tournament-lobby-wrapper").height();
            $.multibox('resize', this.id, null, tournamentLobbyHeight, false, true);
            setTimeout(function(){
                $.multibox('posMiddle', this.id);
            }, 100);
        }
    };

    if(typeof target == 'undefined') {
        $.multibox(options);
    } else {
        top.$.multibox(options);
    }

    mboxClose('mbox-popup');
}

function mpStartDescr(tid){
  mpAction({tid: tid, action: 'tournament-descr'}, function(msg){
    mpBox(msg, 'mbox-popup', 'this');
    //mboxMsg(msg, true, undefined, 400);
  }, 'raw');
}

function mpHiw(func, bId, target){
  ajaxGetBoxHtml({func: func}, cur_lang, 'TournamentBox', function(ret){
    mpBox(ret, bId, target);
    $(".mp-hiw-check > input").change(function(){
      var setting = func == 'prTypesMpInfo' ? 'mp-hiw-types-understood' : 'mp-hiw-general-understood';
      mpAction({action: 'set-setting', setting: setting}, function(ret){});
    });
  });
}

function mpSelectBox(){
  mboxMsg($("#t-entries").html(), false, undefined, 550);
}

function showMyTournaments(){
  //tid = typeof tid == 'undefined' ? curTid : tid;
  ajaxGetBoxHtml({func: 'prMyMpResults'}, cur_lang, 'TournamentBox', function(ret){
    mpBox(ret, 'mp-my-tournaments');
    mboxClose('mbox-popup');
  });
}

function whenIe(){
  if(bowser.msie || bowser.msedge){
    mpAction({action: 'ie-msg'}, function(msg){
      mboxMsg(msg, true);
    }, 'html');
    return true;
  }
  return false;
}

function showPrizeList(tid){
  mpAction({action: 'print-prize-list', tid: tid}, function(html){
    mpBox(html, 'mp-prize-list', 'top');
  }, 'html');
}

function showMpBox(qUrl){
  if(whenIe())
    return;

  if(typeof qUrl == 'undefined')
    qUrl = '/tournament/';
  qUrl = llink(qUrl);
  $.multibox({
    url: qUrl,
    id: 'mp-box',
    type: 'iframe',
    width: '1077px',
    height: '585px',
    cls: 'mbox-deposit',
    globalStyle: {overflow: 'hidden'},
    overlayOpacity: 0.7
  });
}

function mpLobbyWs(url){
    if(hasWs()){
        doWs(url, function(e){
            var res = JSON.parse(e.data);
            if(res.type == 'tournament-lobby'){

                switch(res.subtype){
                    case 'main':
                        stagger(function(){
                            mpAction(res, function(ret){
                                $("#mp-lobby-left").html(ret.html.left);
                                $("#mp-lobby-lb > tbody").html(ret.html.leader_board);
                            });
                        }, res.type, 5000);
                        break;
                    case 'chat':
                        break;
                    case 'leaderboard':
                        stagger(function(){
                            mpAction(res, function(ret){
                                $("#mp-lobby-lb > tbody").html(ret.html.leader_board);
                            });
                        }, res.type, 5000);
                        break;
                }
            } else if (res.type == 'tournament-row') {
                var tournament_data = res.tournament;
                var tournament_id = res.tid;
                var tr = $("#tr-" + tournament_id);
                var ts_options = mpTsOptions();
                var received_null = false;

                for (const column in tournament_data) {
                    if (tournament_data[column] == null) {
                        received_null = true;
                        break;
                    }
                }

                if (!isJurisdictionCompatible(tournament_data, cur_country, cur_province)) {
                    return;
                }

                if (received_null) {
                    ts_options['str_search'] = $('#search').val();
                    listTs(ts_options, undefined, true);
                    return;
                }
                // Fetch values from the DOM,
                var start_status_content = $("#td-start-status-" + tournament_id).text(),
                    tournament_name_content = $("#td-tournament-name-" + tournament_id).text(),
                    game_name_content = $("#td-game-name-" + tournament_id).text(),
                    reg_status_content = $("#td-reg-status-" + tournament_id).text(),
                    pad_lock_content = $("#td-icon-status-" + tournament_id).html();

                var previous_status = tr.attr("data-status") || "";

                // value from ws or html
                var start_status = start_status_content,
                    tournament_name = tournament_data.tournament_name,
                    game_name = tournament_data.game_name,
                    category = tournament_data.category,
                    get_buy_in = tournament_data.get_buy_in,
                    enrolled_user = tournament_data.enrolled_user,
                    reg_status = getStatusesLocalizedString('mp.' + tournament_data.status) ?? reg_status_content,
                    pad_lock = tournament_data.pad_lock ?? pad_lock_content ?? '';


                if (previous_status !== tournament_data.status) {
                    if (['late.registration', 'cancelled', 'finished'].includes(tournament_data.status)) {
                        start_status = getStatusesLocalizedString('mp.' + tournament_data.status) ?? start_status_content;
                    } else {
                        start_status = prettyTime(tournament_data);
                    }
                }


                // static value that can be from ws or html
                var active_tournament_name = tournament_name ?? tournament_name_content,
                    active_game_name = game_name ?? game_name_content;

                // Ensure data is compatible with filters
                if (!isCompatibleWithFilters({
                    tr: tr,
                    tournament: tournament_data,
                    tournament_name: active_tournament_name,
                    game_name: active_game_name,
                    tournament_id: tournament_id
                    })) {
                    return;
                }


                if ($('#tournament-list tbody').length === 0) {
                    $('#tournament-list').append('<tbody></tbody>');
                }

                // time of creation of tournament
                if (tournament_id && tr.length === 0 && tournament_name) {
                    var tbody = $('#tournament-list tbody');
                    var first_row = tbody.find("tr").first();
                    var stripe = 'odd';
                    if (first_row.hasClass('odd')) {
                        stripe = 'even';
                    }

                    // Generate the HTML template
                    var tournament_cols = `
                            <td class="txt-align-left" id="td-start-status-${tournament_id}">${start_status}</td>
                            <td class="txt-align-left" id="td-tournament-name-${tournament_id}">${tournament_name}</td>
                            <td class="txt-align-left" id="td-game-name-${tournament_id}">${game_name}</td>
                            <td id="td-category-${tournament_id}">${category}</td>
                            <td id="td-get-buy-in-${tournament_id}">${get_buy_in}</td>
                            <td id="td-reg-status-${tournament_id}">${reg_status}</td>
                            <td id="td-enrolled-user-${tournament_id}">${enrolled_user}</td>
                            <td id="td-icon-status-${tournament_id}">${pad_lock}</td>
                           `;

                    var tournament_row = `
                             <tr id="tr-${tournament_id}"
                                  class="${stripe} ${getRowColor(res.tournament)}"
                                  onclick="tournamentRowClickHandler('${tournament_id}')"
                                  data-status="${tournament_data.status}">
                                          ${tournament_cols}
                              </tr>`;

                    tbody.prepend(tournament_row);
                    addDoubleClicktoRows(tournament_id);
                    return;
                }

                if (tournament_data.status) {
                    tr.attr('data-status', tournament_data.status);
                }

                //Update only changed values instead of re-rendering the entire row
                updateElementIfChanged("#td-start-status-" + tournament_id, start_status, true);
                updateElementIfChanged("#td-enrolled-user-" + tournament_id, enrolled_user);
                updateElementIfChanged("#td-reg-status-" + tournament_id, reg_status);

                minuteCdown();

                if (typeof curTid !== 'undefined' && Number(curTid) !== Number(tournament_id)) {
                    return;
                }

                // In those cases, we have to make an ajax call because it is user specific
                if (previous_status != tournament_data.status && ['registration.open', 'late.registration', 'in.progress'].includes(tournament_data.status)) {
                    tournamentInfo(tournament_id);
                } else {
                    if (['finished', 'cancelled'].includes(tournament_data.status) && tournament_data.action_button_alias) {
                        var action_button_alias = getStatusesLocalizedString(tournament_data.action_button_alias)
                        $('#register-btn')
                            .html(action_button_alias)
                            .removeClass()
                            .addClass('yellow-right pointer')
                            .removeAttr('onclick');
                    }
                    //select 'start' in tournament info box
                    $('#tournament-info > table > tbody > tr:nth-child(1) > td:nth-child(2)').html(start_status)
                }

            } else {
                //TODO fix this without having to do an ajax call, use the WS info and update the correct area immediately instead
                stagger(function () {
                    mpAction(res, function (ret) {
                        if (res.type == 'tournament-cancelled-popup') {
                            mboxMsg(ret.html, true, undefined, 400);
                        }
                    });
                }, res.type, 5000);
            }
        });
    }
}

function isJurisdictionCompatible(tournament, country, province) {
    var excluded_countries = tournament['excluded_countries'];
    if(excluded_countries && excluded_countries.includes(country)) {
        return false;
    }

    var game_blocked_countries = tournament['game_blocked_countries'];
    if(game_blocked_countries && game_blocked_countries.includes(country)) {
        return false;
    }

    var blocked_provinces = tournament['blocked_provinces'];
    var game_blocked_provinces = tournament['game_blocked_provinces'];

    if(province) {
        if (blocked_provinces && blocked_provinces.includes(province)) {
            return false;
        }
        if (game_blocked_provinces && game_blocked_provinces.includes(province)) {
            return false;
        }
    }

    var included_countries = tournament['included_countries'];
    if(included_countries) {
        return included_countries.includes(country);
    }
    return true;
}

/**
 * Determines if a tournament row is compatible with the selected filters.
 *
 * @param {Object} options - The filter options.
 * @param {jQuery} options.tr - The jQuery object representing the tournament row.
 * @param {Object} options.tournament - The tournament data object.
 * @param {string} options.tournament_name - The name of the tournament.
 * @param {string} options.game_name - The name of the game associated with the tournament.
 * @param {string|number} options.tournament_id - The unique identifier of the tournament (can be a string or number).
 *
 * @returns {boolean} - Returns `true` if the tournament matches the filters, otherwise `false`.
 */
function isCompatibleWithFilters({ tr, tournament, tournament_name, game_name, tournament_id }) {
    var ts_options = mpTsOptions();
    var isCompatible = true;
    var search_val = $('#search').val();
    var search_regexp = new RegExp(search_val, 'i');

    if(search_val !== ''
        && !(game_name && game_name.match(search_regexp) ||
            tournament_name && tournament_name.match(search_regexp))
    ) {
        isCompatible = false;
    }

    if (ts_options['start_format'] !== 'all' &&
        tournament['start_format'] &&
        tournament['start_format'].toLowerCase() !== ts_options['start_format'].toLowerCase()) {
        isCompatible = false;
    }


    if (ts_options['category'] !== 'all' &&
        tournament['category'] &&
        tournament['category'].toLowerCase() !== ts_options['category'].toLowerCase()) {
        isCompatible = false;
    }


    var major_minor_status_map = {
        'upcoming': ['upcoming', 'registration.open'],
        'in-progress': ['late.registration', 'in.progress'],
        'finished': ['finished']
    };

    if (ts_options['status'] !== 'all' &&
        ts_options['status'] in major_minor_status_map &&
        !major_minor_status_map[ts_options['status']].includes(tournament['status'])) {
        isCompatible = false;
    }


    if (ts_options['start_format'] === 'mymps' && tr.length && tournament_name) {
        isCompatible = true;
    }

    // Prevent displaying empty or inconsistent data if key details are missing.
    if (tournament_id && !tournament_name && !game_name) {
        if (tr.length === 0) {
            isCompatible = false;
        }

        // If the element exists but data is outdated, an update might be needed by ajax call (currently disabled).
        // if (tr.length) {
        //     updateTournamentRow(tournament_id);
        //     isCompatible = false;
        // }
    }


    return isCompatible;
}


function getRowColor(tournament){
    var category_color_map = {
        'freeroll': 'freeroll-color',
        'jackpot': 'mp-jackpot-color',
        'added': 'added-color'
    }
    var category = tournament['category'] ? tournament['category'].toLowerCase() : null;
    var color = category ? category_color_map[category] : null;
    if(!color){
        if(tournament['guaranteed_prize_amount'])
            color = 'guaranteed-color';
    }
    if (tournament['category'] === 'Normal' &&
        tournament['award_ladder_tag'] === 'payout' &&
        typeof color === 'undefined') {
        color = 'normal-payout-color'
    }

    return color;
}

function mpStart(url){
  doWs(url, function(e){
      var res = JSON.parse(e.data);

      mpStartedId = res.tournament_id;

      // We get rid of boxes that might be on top of us, we are more important.
      mboxClose('mbox-msg', function(){
          mpBox(res.html, 'mp-start', 'this', '400px');
      });

  });
}

function rebuyCountDown(sec, eid){
    $("#rebuy-countdown").html(sec);
    rebuyIntervalId = setInterval(function(){
        var cur = parseInt($("#rebuy-countdown").html());
        cur--;
        if(empty(cur)){
            mboxClose('mp-rebuy-start');
            canRebuy = false;
            mpAction({action: 'finish-entry', eid: eid}, function(){}, 'html');
            clearInterval(rebuyIntervalId);
        }else
            $("#rebuy-countdown").html(cur);
    }, 1000);
}

function rebuyStart(func, boxId, tid, eid, url){
  ajaxGetBoxHtml({func: func, tid: tid, eid: eid, play_url: url}, cur_lang, 'TournamentBox', function(ret){
    mpBox(ret, boxId, 'this', '400px');
  });
}

function mpFinishedRedirect(eid){
  gotoLang('?tournament_finished=true&eid='+eid);
}

function mpCancelledRedirect(eid){
  gotoLang('?tournament_cancelled=true&eid='+eid);
}

function finishedSpinning(){
  if(typeof fi === 'undefined')
    return true;
  if(fi.frBonus || fi.gameRound || fi.bonus || fi.spinning)
    return false;
  return true;
}

function finishSpinning(){
  fi.gameRound = false;
  fi.spinning = false;
  fi.frBonus = false;
  fi.bonus = false;
}

function execWsFuncs(){
  _.each(mpWsFuncs, function(f){ f.call(); });
  mpWsFuncs = [];
  if(!empty(finFunc)){
      setTimeout(function(){
          finFunc.call();
      }, 3000);
  }
}


function handleIncomingMessage(msg) {
    var event;
    try {
      event = JSON.parse(msg.data);
    } catch (e) {
      // Oh well, but whatever
      return;
    }
    if(fiDebug)
        console.log('tkevent: '+event.eventid);
    var func = fiCalls[fiMap[event.eventid]];
    if(!empty(func))
        func.call();
}

function setupFiEvents(){
    if(g.operator == 'Thunderkick'){
        fiMap = {
            "roundstarted": "gameRoundStarted",
            "roundended": "gameRoundEnded",
            "spinstarted": "spinStarted",
            "spinended": "spinEnded",
            "featurewon": "bonusGameStarted",
            "featureexited": "bonusGameEnded"};
        window.addEventListener("message", handleIncomingMessage);
        gameFi = {toFrame: function(action, ret, onSuccess, onError){
            //console.log('rebuy');
            //console.log(ret);
            //console.log(params);
            //change bet level: {"eventid":"changebet","data":{currentbet: 0.20}}
            var params = {"eventid":"changebalance","data":{balance: parseFloat(nfCents(ret.entry_balance))}};
            if(action == 'reloadBalance')
                $('#mbox-iframe-play-box')[0].contentWindow.postMessage(params,"*");
        }};
    }
}

function mpContentMsgs(res){
  if($("#mbox-popup").length > 0){
      return;
  }
  showPopup(res.msg, res.gohome == 'yes' ? true : false, res.game_ref, res.tournament, res.source, res.eid);
}

function clFi(ev, debug){
  if(debug)
      console.log(ev, {finished: finishedSpinning(), fi: fi});
}

function mpFiSetup(debug){
  tid = curPlayTid;
  debug = typeof debug == 'undefined' ? false : debug;
  fiDebug = debug;
  fiCalls = {
    freeSpinStarted: function(){
      fi.frBonus = true;
      fi.nb++;
      clFi('frb-start', debug);
      mpAction({tid: tid, action: 'fi-state', locAlias: 'mp.freespin.system.msg'});
    },
    gameRoundStarted: function(){
      fi.gameRound = true;
      clFi('ground-start', debug);
    },
    gameRoundEnded: function(){
        fi.gameRound = false;
        clFi('ground-end', debug);
        mpExecFin(function(){
            if(fiDebug)
                console.log({'finfunc': fi});
            if(fi.frBonus || fi.bonus || fi.nb > 0)
                return;
            execWsFuncs();
        });
    },
    balanceChanged: function(){
      //execWsFuncs();
    },
    bonusGameEnded: function(){
      decNb();
      fi.bonus = false;
      clFi('bonus-end', debug);
      setTimeout(function(){
        execWsFuncs();
      }, 2000);
    },
    freeSpinEnded: function(){
      fi.frBonus = false;
      decNb();
      clFi('frb-end', debug);
      execWsFuncs();
    },
    spinStarted: function(){
      fi.spinning = true;
      clFi('spin-start', debug);
    },
    spinEnded: function(){
      fi.spinning = false;
      clFi('spin-end', debug);
        mpExecFin(function(){
            if(fi.frBonus || fi.bonus || fi.gameRound || fi.nb > 0)
                return;
            execWsFuncs();
        });
    },
    bonusGameStarted: function(){
      fi.nb++;
      fi.bonus = true;
      clFi('bonus-start', debug);
      mpAction({tid: tid, action: 'fi-state', locAlias: 'mp.bonus.system.msg'});
    }
  };
}

function decNb(){
  fi.nb--;
  if(fi.nb < 0)
    fi.nb = 0;
}

/**
 * Check if tournament entry is ended
 * @param tEntryId
 * @param calback
 */
function isEnded(tEntryId, calback){
    mpAction({
        tEntryId: tEntryId,
        action: 'spin-end-status'
    }, function(qRes){
        calback(qRes.status);
    });
}

var shownFinished = false;
// Called when tournament is finished to display the final score popup with link to lobby.
function mpFinishedAjax(eid, bkg){
    if(fiDebug)
        console.log({eid: eid});
    if(!empty(finFunc))
        return;
    finFunc = function(){
        if(shownFinished)
            return;
        shownFinished = true;
        getSpinEndResultBoxInterval(eid, bkg);
    }

    // We wait a couple of seconds to let any potential rebuy logic / sockets to execute first.
    setTimeout(function(){
        finFunc.call();
    }, 2000);
}

/**
 * Run interval to check last spin end result.
 * Generally waiting cron to run and update result.
 * @param eid
 * @param bkg
 */
function getSpinEndResultBoxInterval(eid, bkg)
{
    var id = setInterval(function(){
        if(fiDebug) {
            console.log({'type': 'endLoop', 'fi': fi});
        }
        if(finishedSpinning()){
            isEnded(eid, function(status) {
                if (status) {
                    ajaxGetBoxHtml({func: 'prMpFinished', eid: eid}, cur_lang, 'TournamentBox', function(ret){
                        execWsFuncs();
                        if(canRebuy !== true) {
                            setTimeout(function(){
                                mpFinishedBox(ret, bkg);
                            }, 2000);
                        }
                        clearInterval(id);
                    });
                }
            });
        }
    }, 1000);
}
function mpFinishedBox(content, bkg){
  var options = {
    id: "mbox-popup",
    type: 'html',
    showClose: false
  };

  options.content = content;
  options.width  = '449px';
  options.height = '267px';
  options.bkg    = bkg;
  $.multibox(options);
}

function tournamentInfo(tid, iframeId){
  var iframeContents;
  if(typeof iframeId != 'undefined')
    iframeContents = $(iframeId).contents();
  $("tr[id^='tr-']", iframeContents).removeClass('tournament-list-selected');
  $('#tr-' + tid, iframeContents).addClass('tournament-list-selected');
  ajaxGetBoxHtml({func: 'getTournamentInfo', tid: tid}, cur_lang, 'TournamentBox', function(ret){
    $("#top-right-info", iframeContents).html(ret);
    if(!empty(iframeContents))
      $(iframeId)[0].contentWindow.minuteCdown();
    else
      minuteCdown();
  });
}

function backToMainLobby(){
  parent.$.multibox('close', 'mp-lobby-box');
  top.showMpBox();
}

var scrollerState = 'down';
function mpAddMsgToChat(msg, func, uid){
  var msg = $(msg);
  var userId = msg.attr('userid');
  if(empty(userId))
    msg.find('.mp-player').addClass('system');
  else if(userId === uid)
    msg.find('.mp-player').addClass('me');
  $("#mp-nano-content").append(msg);

    // because its a ajax call we must reset nano boxes without passing in an object otherwise the scrollbar will screw-up!!
    if(scrollerState === 'down' || (scrollerState === 'up' && userId === uid)){
        $('.nano').nanoScroller();
        scrollerState = 'down';
    }

    $('.nano').nanoScroller().on("update", function(event, values){
        if(values.direction === 'up'){
            //$('.nano').nanoScroller({stop: true, alwaysVisible: true});
            scrollerState = 'up';
        } else {
            //$('.nano').nanoScroller(oNanoScroller);
            scrollerState = 'down';
        }
  });

    // now we scroll to the last message passing in a object with the params
    if(scrollerState === 'down'){
        $('.nano').nanoScroller(oNanoScroller);
    }

  if(!empty(func))
    func.call();
}

function mpSendChatMsg(msg, tid){
  mpAction({action: 'chat-msg', msg: msg, tid: tid}, function(res){

  }, 'normal');
}

function showTournamentRegBox(checkForTicket, isSuccess = false, callback){
  $.multibox('remove', 'mp-reg-start');
  parent.$.multibox('remove', 'mp-reg-start');
  if(typeof checkForTicket == 'undefined')
    checkForTicket = 'yes';

  showTournamentRegUnRegBox('prMpReg', 'mp-reg-start', checkForTicket, isSuccess, callback);
}

function showTournamentUnqueueBox(tplId){
    showTournamentRegUnRegBox('prMpUnQueue', 'mp-unreg-start');
}

function showTournamentUnRegBox(){
  showTournamentRegUnRegBox('prMpUnReg', 'mp-unreg-start');
}

function showTournamentRegUnRegBox(func, boxId, checkForTicket, isSuccess = false, callback){
  if(typeof checkForTicket == 'undefined')
    checkForTicket = 'yes';

    ajaxGetBoxHtml({func: func, tid: curTid, check_for_ticket: checkForTicket, is_success: isSuccess, callback},
        cur_lang,
        'TournamentBox',
        function (ret) {
            mpBox(ret, boxId, 'parent', '400px');
            if (typeof callback === "function") {
                callback();
            }
        });
}

function mpExecFin(finRoundFunc){
    finRoundFunc.call();
}

function startAliasPicking(){
  ajaxGetBoxHtml({func: 'prPickAlias', show_reg: 'no'}, cur_lang, 'TournamentBox', function(ret){
    mpBox(ret, 'mp-reg-start', 'parent', '400px');
  });
}

/**
 * Returns the localized strings from local storage based on alias
 * @param {string} alias
 * @return string|null
 */
function getStatusesLocalizedString(alias) {
    var localized_strings = JSON.parse(localStorage.getItem('tournament-statuses-localized-strings-' + cur_lang));
    if(!localized_strings) {
        return null;
    }
    return localized_strings[alias] || null;
}

/**
 * Return either the start time countdowns
 * FE equivalent to the BE Tournament::prettyTime()
 * @param {object} t Tournament object coming from WS
 * @return string|'SNG' Countdowns if MTT or 'SNG' if SNG
 */
function prettyTime(t){
     if(hasStarted(t)){
        var diff_mins = timeInterval(null, new Date(), new Date(t['start_time']))['mins'];
        return wrapCdown(diff_mins, 'cup') + ' ' + getStatusesLocalizedString('minutes.ago');
    }else{
        if(t['start_format'] == 'mtt'){
            var st   = new Date(t['mtt_start'] + ' GMT+0000');
            var diff_time = timeInterval(null, new Date(), st);
            if(diff_time['hours'] > 0) {
                return formatDateForTournament(st);
            }
            return getStatusesLocalizedString('in') + wrapCdown(diff_time['mins']) + getStatusesLocalizedString('min.minute');
        }else
            return 'SNG';
    }
}

/**
 * Format date in the right format used in BoS
 * @param {Date} date
 * @return string Formatted date
 */
function formatDateForTournament(date) {
    var date_formatter = new Intl.DateTimeFormat('us-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hourCycle: 'h24'
    })
    var parts = date_formatter.formatToParts(date);

    parts = parts.reduce(function(o, cur) {
        if(['month', 'day', 'hour', 'minute'].includes(cur.type)) {
            return Object.assign(o, {[cur.type]: cur.value});
        }
        return o;
    },{});

    return`${parts['month']} ${parts['day']} ${parts['hour']}:${parts['minute']}`;
}

/**
 * Check if tournament has started
 * @param {object} t
 * @return boolean
 */
function hasStarted(t) {
    if (t['start_time'] == '0000-00-00 00:00:00')
        return false;
    return (new Date(t['start_time'])) <= (new Date());
}

/**
 * Wrap countdown
 * @param {string} str
 * @param {string} updown either 'cdown' or 'cup'
 * @return string
 */
function wrapCdown(str, updown = 'cdown'){
    return ' <span class="minute-' + updown + '">' + str + '</span> ';
}

/**
 * Updates an element's content based on whether the new value is HTML or plain text.
 * This function compares the current content of the element with the new value and
 * updates the content only if it has changed, to optimize performance.
 *
 * If the value is HTML (either by `Html` flag or detecting HTML tags), it updates the element's
 * HTML content using `.html()`. If the value is plain text, it updates the content using `.text()`.
 *
 * @param {string} selector - The jQuery selector for the target element.
 * @param {string} newValue - The new value to update the element with. This can be either plain text or HTML.
 * @param {boolean} Html - A flag indicating whether to treat `newValue` as HTML. Default is `false`.
 */
function updateElementIfChanged(selector, newValue, Html = false) {
    if (!newValue) return;
    var element = $(selector);
    if (!element.length) return;
    var isHtml = false;

    if (Html) {
        isHtml = /<[^>]+>/.test(newValue);
    }

    if (isHtml) {
        if (element.html().trim() !== newValue.trim()) {
            element.html(newValue);
        }
    } else {
        if (element.text().trim() !== newValue.toString().trim()) {
            element.text(newValue);
        }
    }
}
