<?php
require_once __DIR__.'/MobileTrophyListBox.php';

class MobileMissionListBox extends MobileTrophyListBox{

   function achievementsTopSection(&$user){
    $arr = $this->th->getAchievementStatuses($user, $this->categories, 100);
    $chunks = array_chunk($arr, ceil(count($arr) / 2), true);
    ?>
    <div class="trophies-top simple-box left" style="margin-bottom: 10px;">
      <?php $this->drawOverallGraph(
        $this->th->getOverallProgress($user, $this->categories),
        false
      ) ?>
    </div>
    <br clear="all">
    <?php $this->profileAwardSections($user, $chunks);
  }

  function profileAwardSections($user, $chunks) { ?>
    <div class="trophies-top simple-box left">
     <div class="pad10">
       <h3><?php et('achievements.headline') ?></h3>
       <div>
         <?php $this->awardSection($user, $chunks[0], false) ?>
         <?php $this->awardSection($user, $chunks[1], false) ?>
       </div>
     </div>
    </div>
    <br clear="all"/>
  <?php
  }

  function awardSection(&$user, $a_statuses = array(), $show_headline = true){
    if(empty($a_statuses))
      $a_statuses = $this->th->getAchievementStatuses($user, $this->categories, 100);
    ?>
    <?php if($show_headline): ?>
      <h3><?php et('achievements.headline') ?></h3>
    <?php endif ?>
    <?php foreach($a_statuses as $cat => $info): ?>
      <div class="award-section-line">
        <div>
          <div class="activity-category-description">
            <?php et("trophy.$cat.category") ?>
          </div>
          <div class=" activity-progress-txt"><?php echo $info['user_count'].' / '.$info['tot_count'] ?></div>
        </div>
        <span class="clearfix"></span>
        <div class="progress-bar">
          <div class="progress-bar-trans">
            <div class="progress-bar-fill" data-p="<?php echo $info['user_prog']  ?>" style="width: <?php echo ceil($info['user_count'] / $info['tot_count_number'] * 1000) / 10 ?>%"></div>
          </div>
          <div class="progress-bar-base"></div>
        </div>
      </div>
    <?php endforeach ?>
    <?php
  }

  function printThrophyHtml() {
    return null;
  }
}
