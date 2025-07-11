<?php
require_once __DIR__ . '/../../admin.php';
require_once __DIR__ . '/../../../diamondbet/html/display.php';
$backups = phive('SQL')->loadArray("SELECT * FROM backup_log");
?>
<div style="padding: 10px;">
  <strong>Backup Log</strong>
  <br>
  <br>
  Backups are located in two places, in the local folder and the remote folder. Local IP is the IP number of the machine containing the local folder. Remote IP is the IP of the machine containing the remote folder. Backups are taken once per day from the replication server which typically is the remote IP number.
  <br>
  <br>
  <table id="stats-table" class="stats_table">
    <thead>
      <tr class="stats_header">
        <th><?php echo 'Local IP' ?></th>
        <th><?php echo 'Local Folder' ?></th>
        <th><?php echo 'Remote IP' ?></th>
        <th><?php echo 'Remote Folder' ?></th>
        <th><?php echo 'File Name' ?></th>
        <th><?php echo 'File Size (MB)' ?></th>
        <th><?php echo 'Created At' ?></th>
      </tr>
    </thead>
    <?php foreach($backups as $b): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
        <td><?php echo $b['local_ip'] ?></td>
        <td><?php echo $b['local_folder'] ?></td>
        <td><?php echo $b['remote_ip'] ?></td>
        <td><?php echo $b['remote_folder'] ?></td>
        <td><?php echo $b['file_name'] ?></td>
        <td><?php echo $b['file_size'] ?></td>
        <td><?php echo $b['created_at'] ?></td>
      </tr>
    <?php endforeach ?>
  </table>
</div>
