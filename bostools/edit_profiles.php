<?php 
    // require_once __DIR__ . '/Models/BoSTestTournament.php';
$profiles = [];

$database = new SQLite3(__DIR__ .'/Form/profiles.sqlite');
$query = 'CREATE TABLE IF NOT EXISTS tournaments_profiles (profile varchar(255),field_name varchar(255), value varchar(255), type varchar(255),   PRIMARY KEY (profile, field_name) )';

$result = $database->exec($query);
if (!$result){
    echo '<pre style="color:red;">'; var_dump($database->lastErrorMsg()); echo "</pre>"; die;
}

// FORM SUBMIT PROCESSING
if (!empty($_POST)) {
    $formData = $_POST;
    // echo '<pre>'; var_dump($formData); echo "</pre>"; die;
    if ( isset($_POST['form-type']) && $_POST['form-type'] == 'new-field' ) { // NEW FIELD
        unset($formData['form-type']);
        $names = array_keys($formData);
        $values = array_values($formData);
        $query = "INSERT INTO tournaments_profiles (".implode(',',$names).") VALUES ('".implode("', '", $values)."')";
        if (!$database->exec($query)){
            echo '<pre style="color:red;">'; var_dump($formData); var_dump($query);var_dump($database->lastErrorMsg()); echo "</pre>"; die;
        }
    }

    if ( isset($_POST['form-type']) && $_POST['form-type'] == 'edit-field' ) { // EDIT FIELD
        unset($formData['form-type']);
        $updateBulk = ""; $first = true;
        foreach ($formData as $key => $value) { // update fields
            $updateBulk  .=  $first ? "'$key' = '$value'": " ,'$key' = '$value'";
            $first = false;
        }

        $query = "UPDATE tournaments_profiles SET ".$updateBulk." WHERE profile = '".$formData["profile"]."' AND field_name = '".$formData["field_name"]."' ";
        if (!$database->exec($query)){
            echo '<pre style="color:red;">'; var_dump($query);var_dump($database->lastErrorMsg()); echo "</pre>"; die;
        }
    }

    if ( isset($_POST['form-type']) && $_POST['form-type'] == 'delete-field' ) { // NEW FIELD
        unset($formData['form-type']);
        $query = "DELETE FROM tournaments_profiles WHERE profile = '".$formData["profile"]."' AND field_name = '".$formData["field_name"]."' LIMIT 1;";
        if (!$database->exec($query)){
            echo '<pre>'; var_dump($formData); var_dump($query);var_dump($database->lastErrorMsg()); echo "</pre>"; die;
        }
    }

    if (isset($_POST['form-type']) && $_POST['form-type'] == 'new-profile') {
        unset($formData['form-type']);
        $defaultTemplate = [
          'category' => 'freeroll',
          'start_format' => 'mtt',
          'win_format' => 'tht',
          'play_format' => 'xspin',
          'cost' => '100',
          'pot_cost' => '0',
          'xspin_info' => '10',
          'min_players' => '2',
          'max_players' => '100',
          'mtt_show_hours_before' =>  '3',
          'duration_minutes' => '30',
          'mtt_start_time' => '11:00:00',
          'mtt_start_date' => '2018-09-10',
          'mtt_reg_duration_minutes' => '10',
          'mtt_late_reg_duration_minutes' => '10',
          'mtt_recur_type' => '',
          'mtt_recur_days' => '',
          'recur_end_date' => '2037-12-31 10:00:00',
          'recur' => '',
          'guaranteed_prize_amount' => '0',
          'prize_type' => 'win-fixed',
          'created_at' => '0000-00-00 00:00:00',
          'max_bet' => '10',
          'min_bet' => '10',
          'house_fee' => '100',
          'get_race' => '1',
          'get_loyalty' => '0',
          'get_trophy' => '1',
          'rebuy_times' => '2',
          'rebuy_cost' => '100',
          'award_ladder_tag' => '',// 'sng-sburst-2-people',
          'duration_rebuy_minutes' => '0',
          'reg_wager_lim' => 0,
          'reg_dep_lim' => 0,
          'reg_lim_period' => 0,
          'turnover_threshold' => 0,
          'ladder_tag' => 'default',
          'included_countries' => '',
          'excluded_countries' => '',
          'prize_calc_wait_minutes' => 0,
          'free_pot_cost' => 0,
          'total_cost' => 90000,
          'rebuy_house_fee' => 0,
          'spin_m' => 1,
          'pwd' => '',
          'number_of_jokers'    => 1,
          'bounty_award_id'     => 0,
          'bet_levels'          => '',
          'desktop_or_mobile'   => 'both'

        ];
        $names = ['profile','field_name', 'value', 'type'];
        $query = "INSERT INTO tournaments_profiles (".implode(',',$names).") VALUES ";
        $first = true; 
        foreach ($defaultTemplate as $key => $value) {
            $values = [$formData['profile'], $key, $value, gettype($value)];
            $query .= $first ? "('".implode("', '", $values)."')" : ", ('".implode("', '", $values)."')";
            $first = false;
        }

        if (!$database->exec($query)){
            echo '<pre >'; var_dump($formData); var_dump($query);var_dump($database->lastErrorMsg()); echo "</pre>"; die;
        }
    }

}

//  FETCH PROFILE DATA
$profiles = $database->query('SELECT * FROM tournaments_profiles WHERE 1 ORDER BY profile');
// if ($profiles) {
//     while ($profile = $profiles->fetchArray(SQLITE3_ASSOC)) {
//         echo '<pre>'; var_dump($profile); echo "</pre>";       
//     }
//     die;
// }
// FETCH DIFFERENT PROFILES 
$result = $database->query('SELECT DISTINCT(profile) AS profile FROM tournaments_profiles');
$profile_names = [];
while ($profile = $result->fetchArray(SQLITE3_ASSOC)){
    $profile_names[] = $profile; 
}
// echo '<pre>'; var_dump($profile_names); echo "</pre>"; die;
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>::BOS::EDIT PROFILE</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css">
        <script defer src="https://use.fontawesome.com/releases/v5.1.0/js/all.js"></script>
    </head>
    <body>
        <section class="section">
            <div class="container is-fluid">
                <h1 class="title">
                Modify the profiles
                </h1>

                <!-- TOP NAVIGATION BUTTONS -->
                <div class="level">
                    <div class="level-left">                       
                        <div class="level-item">
                            <button class="button is-default" id="back-to-index__button"><i class="fas fa-arrow-left"></i> Back to index</button>
                        </div>
                        <div class="level-item">
                            <button class="button is-default" id="new-field__button"><i class="fas fa-plus-square"></i> Add new field</button>
                        </div>
                    </div>
                    <div class="level-right">
                        <div class="level-item box">
                            <form method="POST"> 
                                <input type="hidden" name="form-type" value="new-profile">
                                 <div class="field has-addons">
                                    <div class="control">
                                        <input class="input" type="text" placeholder="Profile name" name="profile" required >
                                    </div>
                                    <div class="control">
                                        <button class="button is-primary"><i class="fas fa-book"></i>  Add new profile</button>
                                    </div>
                                </div>       
                            </form>
                        </div>
                    </div>
                </div>
                <div id="profile-selector" class="level">
                    <div class="level-left">
                        <?php foreach ($profile_names as $key => $profile): ?>
                        <div class="level-item">
                            <div class="control">
                               <button class="button is-primary profile-selector__button" id="select-profile-<?= trim($profile['profile']); ?>"><?= $profile['profile']; ?></button>
                             </div>
                        </div>
                        <?php endforeach ?>
                    </div>
                </div>


                <!-- PROFILES TABLE -->
            <div id="profiles-table">                
                <?php if (!empty($profiles) && $profiles->numColumns() > 0): ?>
                <?php $tmpProfile = null; $newBox == true; $first = true; ?>
                <?php while ($profile = $profiles->fetchArray(SQLITE3_ASSOC)): ?> 
                    <?php if ($profile['profile'] != $tmpProfile): ?>
                <?php 
                    $tmpProfile = $profile['profile']; 
                    $newBox = true; 
                    if ($newBox && !$first): ?>
                        </tbody>
                    </table>
                </div>
                <?php endif ?>
                <div class="box profile-container is-hidden" id="profile-table-<?= $profile['profile']?>">
                    <span class="tag is-info" style="margin-bottom: 25px;"><?= $profile['profile']; ?></span>
                    <table class="table is-bordered is-narrow is-fullwidth">
                        <thead>
                            <tr>
                                <th>Profile</th>
                                <th>Field Name</th>
                                <th>Value</th>
                                <th>Form input Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php                         
                        endif; 
                    ?>
                        <tr id="profiles-table__item-<?= str_replace(' ', '_', $profile['field_name']); ?>">

                            <?php foreach ($profile as $key => $value): ?>
                                <td><?= $value ?></td>
                            <?php endforeach; ?>
                                <td>
                                    <button class="button edit-profile__button" data-fields="<?= htmlspecialchars(json_encode($profile)) ?>"  ><i class="fa fa-edit"></i></button>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="form-type" value="delete-field">
                                        <input type="hidden" name="profile" value="<?= $profile['profile']?>">
                                        <input type="hidden" name="field_name" value="<?= $profile['field_name']?>">
                                        <button class="button remove-profile__button" ><i class="fa fa-trash" style="color: red; "></i></button>
                                    </form>
                                </td>
                        </tr>
                    
                    <?php if ($newBox) { $newBox = false; $first = false; }  ?>
                        

                <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
                <!-- NEW FIELD MODAL -->
                <div class="modal" id="modal">
                    <div class="modal-background"></div>
                    <div class="modal-content box">
                        <h1 id="modal-title" class="title">New field</h1>
                        <!-- PROFILE form -->
                        <form id="modal-form" method="POST">
                            <input id="modal-form__form-type" type="hidden" name="form-type">
                            <div class="field">
                                <label class="label">Profile</label>
                                <div class="control">
                                    <div class="select is-primary">
                                        <select id="modal-form__profile" name="profile">
                                             <?php foreach ($profile_names as $key => $name): ?>
                                            <option value="<?= $name['profile'] ?>"><?=  ucwords($name['profile'])   ?></option>                                                
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Field Name</label>
                                <div class="control">
                                    <input id="modal-form__name" class="input" type="text" placeholder="Field name" name="field_name" >
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Value</label>
                                <div class="control">
                                    <input id="modal-form__value" class="input" type="text" placeholder="value" name="value" data-select-options="[]">
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Field type</label>
                                <div class="control">
                                    <div class="select is-primary">
                                        <select id="modal-form__type" name="type">                                            
                                            <option value="string">String</option>
                                            <option value="number">Number</option>
                                            <option value="select" id="new-field__field-select">Select</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="select-table" style="margin-bottom: 25px; display: none;">

                                <table class="table is-bordered is-narrow">
                                    <thead>
                                        <tr>
                                            <th>Display Name</th>
                                            <th>Value</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>     
                                    <tbody id="select-table-body">
                                        <tr id="select-table-body__inputs">
                                            <th>
                                                <input type="text" placeholder="Display name" id="new-field__field-select--name">
                                            </th>
                                            <th>
                                                <input type="text" placeholder="value" id="new-field__field-select--value">
                                            </th>
                                            <th> <button class="button is-small" id="new-field__field-select--add" ><i class="fa fa-plus"></i></button></th>
                                        </tr>
                                    </tbody>   
                                </table>                                
                            </div>                            

                            <div class="control">
                                <button id="modal-save" class="button is-primary">Save</button>
                            </div>
                        </form>
                        
                    </div>
                    <button class="modal-close is-large" id="modal__close" aria-label="close"></button>
                </div>
            </div>
        </section>
        <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous">
        </script>
        <script type="text/javascript" src="lib/vsLib.js"></script>
        <script type="text/javascript">
            $(document).ready( () => {
                let profilesModal = modal("#modal");
                // Page listeners
                $("#new-field__button").click( e => { // new field 
                    e.preventDefault();
                    const data = {
                        title: "New Field", 
                        type: 'new-field'
                    };
                    
                    profilesModal.openModal(data);
                });

                $("#profiles-table").on("click", ".edit-profile__button", function (e) { // edit field 
                    e.preventDefault();
                    const data = {
                        title: "Edit Profile", 
                        type: 'edit-field', 
                        fields: $(this).data('fields')
                    };
                    profilesModal.openModal(data);
                });

                $('#back-to-index__button').click(e => {
                    e.preventDefault();
                    window.location.href = 'index.php';
                });

                $("#profile-selector").on("click", ".profile-selector__button", function (e) { // load the profile selected
                    e.preventDefault();
                    const profile = $(this).html();
                    var selected = $(this).attr('id');
                    $('.profile-selector__button').removeClass(('is-outlined is-success'));
                    $(this).addClass('is-outlined is-success');
                    $('.profile-container').addClass('is-hidden');
                    $('#profile-table-'+profile).removeClass('is-hidden');
                    window.history.replaceState({ id: selected}, "TEMPLATES", "edit_profiles.php?selected="+selected) ;
                });
                var selected = '<?= $_GET['selected']?>';
                if (selected) {
                    $('#' + selected).click();
                } else {
                    $('.profile-selector__button').first().click(); // show the first profile available
                }

            });
        </script>
    </body>
</html>
