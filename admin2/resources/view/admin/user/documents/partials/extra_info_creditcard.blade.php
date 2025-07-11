@if ($document['tag'] == 'creditcardpic')
    <div id="cardinfo_{{$document['id']}}">
        Exp. year: <?php echo $document['card_data']['exp_year'] ?>, Exp. month: <?php echo $document['card_data']['exp_month'] ?>, 3D: <?php echo $document['card_data']['three_d'] ?>
    </div>


@endif

