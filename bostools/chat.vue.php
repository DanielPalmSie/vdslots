<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>::BOS::DUMMIES</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css">
    <script defer src="https://use.fontawesome.com/releases/v5.1.0/js/all.js"></script>

    <style type="text/css">
body, html {
  font-family: sans-serif;
  font-weight: 100;
/*background: #7b4397;   fallback for old browsers */ 

/*background: -webkit-linear-gradient(to right, #dc2430, #7b4397);  /* Chrome 10-25, Safari 5.1-6 */*/


/*background: linear-gradient(to right, #dc2430, #7b4397);  W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
}

.headline {
  text-align: center;
  font-weight: 100;
  color: black;
  font-size: 2em;
}
.chat-area {
/*   border: 1px solid #ccc; */
  background: white;
  height: 50vh;
  padding: 1em;
  overflow: auto;
  max-width: 350px;
  margin: 0 auto 2em auto;
  box-shadow: 2px 2px 5px 2px rgba(0, 0, 0, 0.3)
}
.message {
  width: 45%;
  border-radius: 10px;
  padding: .5em;
/*   margin-bottom: .5em; */
  font-size: .8em;
}
.message-out {
  background: #407FFF;
  color: white;
  margin-left: 50%;
}
.system-out {
  color: black;
  background-color: #5fba7d;
  margin-left: 50%;
  font-style: italic;
}
.message-in {
  background: #F1F0F0;
  color: black;
}

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
  
    <h1 class="headline">BATTLE OF SLOTS Chat Box</h1>
  
        <main id="app">
          <section ref="chatArea" class="section chat-area" v-chat-scroll>
            <p v-for="message in messages" class="message" :class="{ 'message-out': message.author === 'you', 'message-in': message.author === 'bob', 'system-out': message.author==='system'}">
              {{ message.body }}
            </p>
          </section>

          <section class="section columns is-mobile chat-inputs">
            <div class="column ">
              <div class="box">
                <h3 class="subtitle">Send Messages to tournament chatbox</h3>
                <form @submit.prevent="sendMessage()" id="person1-form">
                  <div class="field">
                    <label class="label" for="person1-input1">Tournament</label>
                    <input class="input" v-model="tournament" id="person1-input1" type="text" placeholder="Tournament Id" >
                  </div>

                  <div class="field">
                    <label class="label" for="person1-input2">User</label>
                    <input class="input" v-model="user" id="person1-input2" type="text" placeholder="User Id">
                  </div>
                    
                  <div class="field">
                    <label class="label" for="person1-input3">Message</label>
                    <input class="input" v-model="message" id="person1-input3" type="text" placeholder="Type your message">
                  </div>
                  
                  <div class="field">
                    <div class="control">
                      <button class="button is-link" type="submit">Send</button>                    
                    </div>
                  </div>
                </form>
              </div>
            </div>


            <div class="column ">
              <div class="box">
                <p class="subtitle">Conversation between users</p>
                <form @submit.prevent="startConversation()" id="person1-form">
                <div class="field">
                  <label class="label" for="person1-input1">Tournament</label>
                  <input class="input" v-model="tournament" id="person1-input1" type="text" placeholder="Tournament Id" >
                </div>

                <div class="field">
                  <label class="label" for="person1-input2">Users</label>
                  <input class="input" v-model="users" id="person3-input2" type="text" placeholder="User Id">
                </div>
                
                <div class="field">
                  <label class="label" for="person1-input2">Messages</label>
                  <input  class="input" v-model="n_messages" id="person3-input3" type="text" placeholder="User Id">
                </div>
                  
                <div class="field">
                  <div class="control">
                    <button class="button is-link" type="submit">Send</button>
                  </div>
                </div>
                    
                </form>
              </div>
            </div>

          

            <div class="column ">
              <div class="box">
                <p class="subtitle">Change Tournament Box</p>
                <form @submit.prevent="fetchMessages()" id="tournament-form">
                  <div class="field">
                    <label class="label" for="tournament-input1">Tournament</label>
                    <input class="input" v-model="tournament" id="tournament-input1" type="text" placeholder="Tournament Id" >
                  </div>
                  <div class="field">
                    <div class="control">
                      <button class="button is-link" type="submit">Change</button>                    
                    </div>
                  </div>
                </form>                
              </div>
              <div class="box">
                <p class="subtitle">Send System message</p>
                <form @submit.prevent="sendSystemMessages()" id="tournament-form">
                  <div class="field">
                    <label class="label" for="tournament-input1">Tournament</label>
                    <input class="input" v-model="tournament" id="tournament-input1" type="text" placeholder="Tournament Id" >
                  </div>
                  <div class="field">
                    <label class="label" for="tournament-input2">Number of messages</label>
                    <input class="input" v-model="nrOfMessages" id="tournament-input2" type="text" placeholder="Number of messages" >
                  </div>
                  <div class="field">
                    <div class="control">
                      <button class="button is-link" type="submit">Send</button>                    
                    </div>
                  </div>
                </form>                
              </div>
            </div>
          </section>

          <a id="link" href="/bostools/index.php" class="button">Back to Boss Dummies</a>
        </main>
    

    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.3/vue.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/vue-chat-scroll@1.3.3/dist/vue-chat-scroll.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js"></script>
    <script type="text/javascript">
        new Vue({
          el: '#app',
          data: {
            tournament: <?= $_GET['tournament'] ?? 123456?>,
            message: '',
            user: '',
            users: 1,
            n_messages: 5,
            nrOfMessages: 2,
            messages: []
          },
          mounted: function (){
            this.fetchMessages();
          },
          methods: {
            startConversation(){
              const self = this;
              for(let i = 0; i< this.n_messages; i++){                
                axios.post('/bostools/Models/BoSChatTest.php', {
                  users: this.users, messages: this.n_messages, tid: this.tournament, 'action': 'startConversation'
                }).then(function(response){           
                  console.log(response);       
                  let j = 0;
                  response.data.forEach( function(k,v){
                    j =  Math.random() >= 0.5;
                    self.messages.push( {'body' : k.msg, 'author' : ( j %2 ? 'you' : 'bob')} );
                  } );
                });
              }
            },
            sendMessage() {
              if (!this.tournament) {
                return
              }
              const self = this;
              axios.post('/bostools/Models/BoSChatTest.php', {
                msg: this.message, author: this.user, tid: this.tournament, 'action' : 'sendMessage'
              }).then(function(response){
                console.log(response);
                 let i =  Math.random() >= 0.5;
                 self.messages.push( {'body' : response.data.msg, 'author' : ( i ? 'you' : 'bob')} );
              });
              // Vue.nextTick(() => {
              //   let messageDisplay = this.$refs.chatArea
              //   messageDisplay.scrollTop = messageDisplay.scrollHeight
              // })
            },
            fetchMessages() {
              const self = this;            
              this.clearAllMessages();
              axios.get('/bostools/Models/BoSChatTest.php?fetchall=true&tid='+this.tournament).then( function (response) {
                if (response.data == null) {
                  return;
                }
                response.data.forEach( function(k,v){
                  if (k.msg.en != undefined) {
                    // statement
                    self.messages.push( {'body' : k.msg.en, 'author' : 'system'} );
                  }else{
                    self.messages.push( {'body' : k.msg, 'author' : ( v%2 ? 'you' : 'bob')} );           
                  }
                } );
              });
            },
            sendSystemMessages() {
              if (!this.tournament) {
                return
              }
              const self = this;
              axios.post('/bostools/Models/BoSChatTest.php', {
                tid: this.tournament, nrOfMessages: this.nrOfMessages, 'action' : 'sendSystemMessages'
              }).then(function(response){
                console.log(response);
                response.data.forEach( function(k,v){
                  self.messages.push( {'body' : k.msg.en, 'author' : 'system'} );
                } );
              });
            },
            clearAllMessages() {
              this.messages = []
            }
          }
        })
    </script>
</body>
</html>
