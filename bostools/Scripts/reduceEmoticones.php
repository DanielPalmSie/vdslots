<?php 
    $emojis = [":grinning:",":grin:",":joy:",":smiley:",":smile:",":sweat_smile:",":laughing:",":innocent:",":smiling_imp:",":wink:",":blush:",":yum:",":relieved:",":heart_eyes:",":sunglasses:",":smirk:",":neutral_face:",":expressionless:",":unamused:",":sweat:",":pensive:",":confused:",":confounded:",":kissing:",":kissing_heart:",":kissing_smiling_eyes:",":kissing_closed_eyes:",":stuck_out_tongue:",":stuck_out_tongue_winking_eye:",":stuck_out_tongue_closed_eyes:",":disappointed:",":worried:",":angry:",":rage:",":cry:",":persevere:",":triumph:",":disappointed_relieved:",":frowning:",":anguished:",":fearful:",":weary:",":sleepy:",":tired_face:",":grimacing:",":sob:",":open_mouth:",":hushed:",":cold_sweat:",":scream:",":astonished:",":flushed:",":sleeping:",":dizzy_face:",":no_mouth:",":mask:"];

    echo '<pre>'; var_dump(array_reduce($emojis, function( $carry, $item ){ 
        if ($carry[$item] == null) {
            $carry[] = $item;
        }
        return $carry;
    })); echo "</pre>"; die;

?>
