<div id="edit-email-form">
    <form action="" method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <label for="subject">
            Subject
            
        </label>
        <input type="text" id='subject' name="subject" value="{{$mail['subject']}}" class="input"/>
        <br>
        <br>

        <label for="replacers">
            Replacers
        </label>
        <input type="text" id='replacers' name="replacers" value="{{$mail['replacers']}}" class="input"/>
        <br>
        <br>

        @if(empty($mail))
            <input type="hidden" name="new_mail_trigger" value="new_mail_trigger"/>
        @endif

        <input id="mail_trigger" type="hidden" name="mail_trigger" value="{{$mail_trigger}}"/>

        {{ phive('InputHandler')->printTextArea("large", "content", "content", $mail['content'], "900px", "800px") }}

        <br>
        <input id="save_email" type="submit" name="save" value="Save email"/>

    </form>
</div>

