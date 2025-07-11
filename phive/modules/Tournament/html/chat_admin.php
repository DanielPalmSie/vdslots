<?php
require_once __DIR__ . '/../../../admin.php';
?>
<script>
 function mpChatBlock(me, uid, eid, days){
   days = typeof days == 'undefined' ? 0 : days;
   mpAction({action: 'mp-chat-block', uid: uid, eid: eid, days: days}, function(res){
     me.html('Blocked Successfully');
   }, 'normal');
 }

 function mpChatDelete(me, uid, eid, messageId){
     console.log(me,uid,eid,messageId);
     mpAction({action: 'mp-chat-delete', uid: uid, eid: eid, messageId: messageId}, function(res){
         me.html('Removed Successfully');
     }, 'normal');
 }

 function addBlockBtn(){
   $(".mp-chat-item").each(function(){
     var me = $(this);
     if(me.find(".mp-block-btn").length == 0){
       var uid = me.attr('userid');
       var eid = me.attr('eid');
       var messageId = me.attr('messageid');
       var blockTempBtn = $('<td class="mp-block-btn">Chat Block 7 Days</td>');
       blockTempBtn.click(function(){ mpChatBlock($(this), uid, eid, 7); });
       me.append(blockTempBtn);
       var blockBtn = $('<td class="mp-block-btn">Chat Block Permanently</td>');
       blockBtn.click(function(){ mpChatBlock($(this), uid); });
       me.append(blockBtn);
       <?php if(p('user.chat.delete')): ?>
       var deleteBtn = $('<td class="mp-delete-btn">Delete the message</td>');
       deleteBtn.click(function(){ mpChatDelete($(this), uid, eid, messageId); });
       me.append(deleteBtn);
       <?php endif; ?>
       //TODO use eid here to go to a script local to the tournament master, it will then use the entry id to redirect to the correct slave
       var adminLink = $('<td><a href="/admin/userprofile/?username='+uid+'" target="_blank" rel="noopener noreferrer">Go to Admin</a></td>');
       me.append(adminLink);
     }
   });
 }

 function scrollChatAdmin(){
   var d = $('#mp-chat-msgs');
   d.scrollTop(d.prop("scrollHeight"));
 }

</script>
<style>
 .mp-chat-msgs{
   background: #ddd !important;
   display: block;
   height: 700px;
   overflow-y: scroll;
 }

 .mp-chat-msg{
   width: 350px !important;
 }

 .mp-block-btn,
 .mp-delete-btn {
   width: 150px !important;
   cursor: pointer;
   background-color: #faa;
 }

 .mp-chat-item td{
   color: #000 !important;
   border: solid 1px #000;
   padding: 2px;
 }

 .mp-chat-msgs table{
   border-spacing: 0;
   border-collapse: collapse;
 }
 
</style>
<?php
echo phive('BoxHandler')->getRawBoxHtml('TournamentLobbyBox', 'drawChatAdmin');
