<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';


class AdminBoxBase extends DiamondBox {

  public function init () {

    if ( isset( $_POST[ 'save_settings' ] ) && $_POST[ 'box_id' ] == $this->getId() ) {
      //$this->setAttribute("", $_POST['']);
      $this->setAttribute( "title", $_POST[ 'title' ] );
      $this->setAttribute( "path", $_POST[ 'path' ] );

    }
  }

  public function getHeadline () { return $this->getAttribute( "title" ); }

  public function printHTML () {

    $title = $this->getAttribute( 'title' );
    $path = $this->getAttribute( 'path' );

    ?>
    <div class="db_content">
      <?php if ( $path ): ?>
        <?php require __DIR__ . '/../../../../../' . $path ?>
      <?php else: ?>
        Attribute "path" is missing.
      <?php endif; ?>
    </div>

    <?php
  }

  public function printCustomSettings () {

    ?>
    <form method="post" action="<?= phive( "Pager" )->getPath() ?>">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <input type="hidden" name="box_id" value="<?= $this->getId() ?>"/>
      <p>
        <label for="title">Title of page</label>
        <input type="text" name="title" value="<?php echo $this->getAttribute( 'title' ); ?>" id="title"/>
      </p>
      <p>
        <label for="path">Path to file (from root)</label>
        <input type="text" name="path" value="<?php echo $this->getAttribute( 'path' ); ?>" id="path"/>

        <input type="submit" name="save_settings" value="Save and close" id="save_settings"/>
    </form>
  <?php }
}


?>
