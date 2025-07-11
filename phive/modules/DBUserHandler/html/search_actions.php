<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$start_date     = empty($_REQUEST['sdate']) ? date('Y-m-d') : $_REQUEST['sdate'];
$end_date 	= empty($_REQUEST['edate']) ? date('Y-m-d', strtotime('tomorrow')) : $_REQUEST['edate'];
$username	= empty($_REQUEST['username']) ? "" : $_REQUEST['username'];


if(!empty($_REQUEST['submit'])){
  $sql = "SELECT * FROM actions WHERE 1 ";

  if(!empty($start_date))
    $sql .= " AND DATE(created_at) >= '$start_date' ";
  
  if(!empty($end_date))
    $sql .= " AND DATE(created_at) <= '$end_date' ";
  
  if(!empty($username)){
    $target = phive('UserHandler')->getUserByUsername($username);
    if(!empty($target))
      $sql .= " AND target = '{$target->getId()}' ";
  }

  if(!empty($_REQUEST['tag']))
    $sql .= " AND tag LIKE '%{$_REQUEST['tag']}%' ";
  else if(!empty($_REQUEST['tags'])){
    $in = phive('SQL')->makeIn($_REQUEST['tags']);
    $sql .= " AND tag IN($in)";
  }

  if(!empty($_REQUEST['descr']))
    $sql .= " AND descr LIKE '%{$_REQUEST['descr']}%' ";

  $sql .= " ORDER BY created_at DESC LIMIT 0,100";
  $actions = phive('SQL')->loadArray($sql);  
}

?>
<script>
 function setTags(tags, field){
   if(typeof field == 'undefined')
     field == 'tags';
   $("#"+field).val(tags);
 }
</script>
<div class="pad10">
  <table>
    <tr>
      <td>
        <p>
          The <strong>Tag</strong> and <strong>Part of description</strong> fields are partials,<br/>
          putting <strong>last_</strong> in the Tag field will for instance match all actions tagged with last_login.
        </p>
        <?php drawStartEndJs() ?>
        <form action="" method="get">
          <table>
            <?php drawStartEndHtml() ?>
            <tr>
              <td>
                <label>Username:</label>
              </td>
              <td>
                <?php dbInput('username', $username) ?><br />
              </td>
            </tr>
            <tr>
              <td>
                <label>Tag:</label>
              </td>
              <td>
                <?php dbInput('tag', $_REQUEST['tag']) ?><br />
              </td>
            </tr>
            <tr>
              <td>
                <label>Tags:</label>
              </td>
              <td>
                <?php dbInput('tags', $_REQUEST['tags']) ?><br />
              </td>
            </tr>
            <tr>
              <td>
                <label>Part of description:</label>
              </td>
              <td>
                <?php dbInput('descr', $_REQUEST['descr']) ?><br />
              </td>
            </tr>
          </table>
          <br/>
          <?php dbSubmit('Submit') ?>
        </form>
      </td>
      <td>
        <p>
          Populate Tags with:
        </p>
        <div class="pointer">
          <strong onclick="setTags('credit_card,verified-pic,verified-account', 'tag')">
            All verified pics
          </strong>
          <br/>
          <strong onclick="setTags('credit_card', 'tag')">
            Only verified card pics
          </strong>
          <br/>
          <strong onclick="setTags('approved-withdrawal', 'tag')">
            Approved withdrawals
          </strong>
        </div>
      </td>
    </tr>
  </table>
  <br/>  
<table class="stats_table">
  <tr class="stats_header">
    <th>Actor</th>
    <th>Target</th>
    <th>Created At</th>
    <th>Description</th>
    <th>Tag</th>
  </tr>
  <?php $i = 0; foreach($actions as $a):
    $auname = ud($a['actor'])['username'];
    $tuname = ud($a['target'])['username'];
  ?>
  <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill">
    <td>
      <a href="/admin/userprofile/?username=<?php echo $auname ?>"><?php echo $auname ?></a>
    </td>
    <td>
      <a href="/admin/userprofile/?username=<?php echo $tuname ?>"><?php echo $tuname ?></a>
    </td>
    <td>
      <?php echo $a['created_at'] ?>
    </td>
    <td>
      <?php echo $a['descr'] ?>
    </td>
    <td>
      <?php echo $a['tag'] ?>
    </td>
  </tr>
  <?php $i++; endforeach; ?>
</table>
</div>
