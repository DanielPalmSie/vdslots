<div class="card" id="bonus_codes">
    <div class="card-header">
        <h3 class="card-title">Bonus codes connected to this alias</h3>
    </div>
    <div class="card-body">

        <form id="connectbanners" action="" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <select multiple="multiple" size="10" name="duallistbox_bonuscodes[]">
                @foreach($bonus_codes as $bonus_code)
                    <option value="{{$bonus_code['bonus_code']}}" @if(!empty($bonus_code['selected'])) selected="selected" @endif>{{$bonus_code['bonus_code']}}</option>
                @endforeach
            </select>
            <input type="hidden" name='alias' value="{{$_GET['alias']}}" />
            @if(\App\Repositories\ImageAliasRepository::pageHasText($pages, $_GET['pagealias']))
            <input type="hidden" name="has_text" value="true"/>
            @endif
            <br>
            <button type="submit" class="btn btn-default btn-block">Submit data</button>
        </form>

    </div>
</div>

