<div class="card" id="bonus_codes">
    <div class="card-header">
        <h3 class="card-title">Bonus codes connected to this email</h3>
    </div>
    <div class="card-body">

        <form id="connectbanners" action="{{ $app['url_generator']->generate('bannertags') }}" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <select multiple="multiple" size="10" name="duallistbox_bonuscodes[]">
                @foreach($bonus_codes as $bonus_code)
                    <option value="{{$bonus_code['bonus_code']}}" @if(!empty($bonus_code['selected'])) selected="selected" @endif>{{$bonus_code['bonus_code']}}</option>
                @endforeach
            </select>
            <input type="hidden" name='email_alias' value="{{$_GET['email_alias']}}" />
            <input type="hidden" name='pagealias' value="{{$_GET['pagealias']}}" />
            <br>
            <button type="submit" class="btn btn-default btn-block">Submit data</button>
        </form>

    </div>
</div>

