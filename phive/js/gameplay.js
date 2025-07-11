
//also wait for the spin end before displaying scoreboard in tournaments.
parent.fi = {"frBonus": false, "spinning": false, "gameRound": false, "bonus": false};
if(typeof parent.fiCalls === 'undefined'){
  parent.fiCalls = {
    gameRoundStarted: function(){},
    gameRoundEnded: function(){},
    balanceChanged: function(){},
    bonusGameEnded: function(){},
    freeSpinEnded: function(){},
    bonusGameStarted: function(){},
    freeSpinStarted: function(){},
    spinStarted: function(){},
    spinEnded: function(){}
  };
}

var gameEvents = {
  "gameReady": function(){
    console.log('game ready');
  },
  "freeSpinStarted": function(){
    parent.fiCalls.freeSpinStarted.call();
  },
  "spinStarted": function(modes){
    parent.fiCalls.spinStarted.call();
  },
  "freeSpinEnded": function(){
    parent.fiCalls.freeSpinEnded.call();
    //setTimeout(function(){
    //}, 200);
  },
  "spinEnded": function(){
    parent.fiCalls.spinEnded.call();
  },
  "bigWinStarted": function(){},
  "bonusGameStarted": function(){
    parent.fiCalls.bonusGameStarted.call();
  },
  "bigWinEnded": function(){},
  "bonusGameEnded": function(){
    parent.fiCalls.bonusGameEnded.call();
  },
  "gameReady": function(){},
  "gameRoundStarted": function(){
    parent.fiCalls.gameRoundStarted.call();
  },
  "gameRoundEnded": function(){
    parent.fiCalls.gameRoundEnded.call();
  },
  "balanceChanged": function(){
    parent.fiCalls.balanceChanged.call();    
  }
};

//TODO this is probably not going to work, functions need to be called from the parent by way of document.getElementById('mbox-iframe-play-box').contentWindow.xxxx();
var gameActions = {
  "reloadbalance": function(){},
  "setVolumeLevel": function(){},
  "getBalanceInCoins": function(){},
  "getBetInCoins": function(){},
  "getWinInCoins": function(){},
  "getBalanceInCurrency": function(){},
  "getBetInCurrency": function(){},
  "getWinInCurrency": function(){},
  "getPlayerCurrency": function(){},
  "getAvailableCoins": function(){},
  "getSelectedCoinValue": function(){}
};

//parent.fiActions = gameActions;
