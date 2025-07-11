<?php
// TODO can this be removed?
exit;

require_once __DIR__.'/../../../../admin.php';
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class GenerateCsvBase extends DiamondBox{
    public function init(){
	if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()){
	    $this->setAttribute("refs", $_POST['refs']);
	    $this->setAttribute("start_date", $_POST['start_date']);
	    $this->setAttribute("end_date", $_POST['end_date']);
	    $this->setAttribute("limit", $_POST['limit']);
	    $this->setAttribute("file_name", $_POST['file_name']);
	}
	
	$this->refs 		= explode(',', $this->getAttribute("refs"));
	$this->start_date 	= $this->getAttribute("start_date");
	$this->end_date 	= $this->getAttribute("end_date");
	$this->file_name 	= $this->getAttribute("file_name");
	$this->limit 		= $this->attributeIsSet('limit') ? $this->getAttribute("limit") : 50;
	
	if(!empty($_GET['save_file']))
	    file_put_contents(getMediaServiceUrl() . '/file_uploads/'.$this->file_name, $_POST['stats']);
    }
    
    public function printHTML(){
	$refs 	= phive('SQL')->makeIn($this->refs);
	phive('SQL')->query('USE Diamondbet');
	$query 	= "
			SELECT player.name AS firstname, player.lastname, player.custId, SUM(activity.rakeCash) + SUM(activity.rakeStt) + SUM(activity.rakeMtt) AS rake
			FROM gds_player player 
			LEFT JOIN gds_player_poker_gaming_activity AS activity ON player.custId = activity.custId
			LEFT JOIN gds_bonus_transactions AS bonuses ON (activity.date = bonuses.date AND activity.custId = bonuses.custId AND bonuses.bonusId != 2942)
			WHERE player.campaign 
				IN($refs)
			AND activity.date <= '{$this->end_date}' 
			AND activity.date >= '{$this->start_date}'
			GROUP BY player.custId ORDER BY rake DESC 
			LIMIT 0, {$this->limit}";
	
	$content = 'firstname,rake'."\r\n";
	$result = 'pin,firstname,lastname,rake'."\r\n";
	
	if(!empty($this->refs)){
	    foreach(phive('SQL')->loadArray($query) as $el){
		$content .= $el['firstname'].','.$el['rake']."\r\n";
		$result .= $el['custId'].','.$el['firstname'].','.$el['lastname'].','.$el['rake']."\r\n";
	    }
	}
	
	phive('SQL')->query('USE dbaffiliates');
	
    ?>
        <strong><?php echo "Start Date: {$this->start_date}, End Date: {$this->end_date}" ?></strong>
        <form method="post" action="?save_file=true">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <textarea name="stats" cols="100" rows="20"><?php echo $content ?></textarea>
            <br/>
            <input type="submit" value="Save"/>
            <br/>
            Result:
            <textarea name="results" cols="100" rows="20"><?php echo $result ?></textarea>
        </form>
    <?php 
    }
	
    function printExtra(){?>
	<p>
	    Ref codes (ex: ref128899_0,ref128899_1):
	    <input type="text" name="refs" value="<?php echo implode(',', $this->refs) ?>" />
	</p>
	<p>
	    Start Date:
	    <input type="text" name="start_date" value="<?php echo $this->start_date ?>" />
	</p>
	<p>
	    End Date:
	    <input type="text" name="end_date" value="<?php echo $this->end_date ?>" />
	</p>
	<p>
	    Limit:
	    <input type="text" name="limit" value="<?php echo $this->limit ?>" />
	</p>
	<p>
	    File Name:
	    <input type="text" name="file_name" value="<?php echo $this->file_name ?>" />
	</p>
	<?php	
    }
}
