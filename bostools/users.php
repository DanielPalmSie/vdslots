<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/Models/BoSTestTournament.php';    
require_once __DIR__ . '/Models/BoSTester.php';  

$tournament = new BoSTestTournament($_GET['tournament']);
$players =  $tournament->getLeaderboard();
$mpUrls = [
  'my_info' => phive('UserHandler')->wsUrl('set', phive('Tournament')->getMpInfoKey($_GET['tournament'])),
  'extend' => phive('UserHandler')->wsUrl('set', 'mpextendtest') , 
  'limit' => phive('UserHandler')->wsUrl('set', 'mplimit'.$_GET['tournament']),
  'calculated' => phive('UserHandler')->wsUrl('set', 'mpcalculated'.$_GET['tournament']),
  'main' => phive('UserHandler')->wsUrl('set', 'mp'.$_GET['tournament'], false, false)
 
];
// echo '<pre>'; var_dump($players); echo "</pre>"; die;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>::BOS:: Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css">

    <style type="text/css">
        #link{
          display: block;
          margin-top: 25px;
          font-size: large;
        }
        .button {
          font: bold 11px Arial;
          text-decoration: none;
          background-color: #EEEEEE;
          color: #333333;
          padding: 2px 6px 2px 6px;
          border-top: 1px solid #CCCCCC;
          border-right: 1px solid #333333;
          border-bottom: 1px solid #333333;
          border-left: 1px solid #CCCCCC;
        }
    </style>
</head>
<body>
    <div class="section">
      <h1 class="title">Leaderboard: <?= $tournament->getTournament()['id'] . " " . $tournament->getTournament()['tournament_name'];  ?></h1>
      <p class="subtitle is-red">Simulated Play only available for Netent games</p>
        <div id="app" class="container is-fluid">            
          <table class="table is-bordered is-striped is-narrow is-hoverable is-fullwidth">
            <thead>
              <tr>
                <th><abbr title="Position">Pos</abbr></th>
                <th><abbr title="User Id">Id</abbr></th>
                <th><abbr title="Username">User</abbr></th>
                <th><abbr title="Alias">Alias</abbr></th>
                <th><abbr title="Spins Left">Spins Left</abbr></th>
                <th><abbr title="Cash Balance">Cash</abbr></th>
                <th><abbr title="Total win">Win</abbr></th>
                <th><abbr title="Actions">Actions</abbr></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(player, index) in players">
                <td >{{index + 1}}</td>
                <td>{{player.id}}</td>
                <td>{{player.username}}</td>
                <td>{{player.alias}}</td>
                <td>{{player.spins_left}}</td>
                <td>{{player.cash_balance}} {{player.currency}}</td>
                <td>{{player.total_win}} {{player.currency}}</td>
                <td>
                  <form  @submit.prevent="playTournament(player.id)">
                    <button class="button is-link" >Play</button>
                  </form>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="level">
            <div class="level-left">
              <div class="level-item">
                <form @submit.prevent="playAllUsers()">
                  <button class="button is-link">Play all users 1 spin</button>
                </form>                
              </div>
              <div class="level-item">
                <form @submit.prevent="playAllSpins()">
                  <button class="button is-link">Play All spins</button>
                </form>                
              </div>
            </div>
            <div class="level-right">
            </div>
          </div>

          <div class="box">
            <div class="content">
              <h1>WebSockets URLS for this tournament</h1>
              <ul>
                <?php foreach ($mpUrls as $key => $mpUrl): ?>
                  <li><?= $key . " :: " .$mpUrl; ?></li>
                <?php endforeach ?>
              </ul>
            </div>
          </div>

            <a id="link" href="/bostools/index.php" class="button">Back to Boss Dummies</a>
        </div>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js"></script>
    <script type="text/javascript">
        new Vue({
          el: '#app',
          data: {
            tournament: <?= $_GET['tournament'] ?>,
            players: []
          },
          mounted: function () {
            const self = this; //to have parent context inside callback
            this.fetchLeaderboard();
            setInterval(function(){
                 self.fetchLeaderboard();
            }, 5000);
          },
          methods: {
            fetchLeaderboard(){
              const self = this;  
              axios.get('/bostools/Models/BoSUserTest.php?action=fetchLeaderboard&tid='+this.tournament).then(function(response){
                if (response.data == null) {
                  return;
                }
                console.log(response.data);
                self.players = response.data;
              });
            },
            playTournament(user_id){
              const self = this;
              axios.post('/bostools/Models/BoSUserTest.php', {action: 'playTournament', tid :this.tournament, 'user_id': user_id }).then(function(response){
                if (response.data == null) {
                  return;
                }
                // console.log(response.data);
                self.players = response.data;
              });
            },
            playAllUsers(){
              const self = this;
              axios.post('/bostools/Models/BoSUserTest.php', {action: 'playAllUsers', tid :this.tournament}).then(function(response){
                if (response.data == null) {
                  return;
                }
                // console.log(response.data);
                self.players = response.data;
              });
            },
            playAllSpins(){
              const self = this;
              axios.post('/bostools/Models/BoSUserTest.php', {action: 'playAllSpins', tid :this.tournament}).then(function(response){
                if (response.data == null) {
                  return;
                }
                // console.log(response.data);
                self.players = response.data;
              });              
            }
          }
        });
    </script>
</body>
</html>
