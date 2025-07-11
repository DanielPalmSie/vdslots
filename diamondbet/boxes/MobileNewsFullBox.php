<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/NewsFullBoxBase.php';
class MobileNewsFullBox extends NewsFullBoxBase {

    public function printHTML() {
        parent::printHTML();
        ?>
        <script>
            $(document).ready(function() {
            });
        </script>
        <?php
    }

    public function printArticle() {
        $stamp = strtotime($this->news->getTimeCreated());
    ?>
        <p>
            <table style="width: 100%">
                <tr>
                    <td>
                        <h3 class="big_headline big_headline_mobile"> <?php echo rep($this->news->getHeadline()) ?> </h3>
                    </td>
                    <td style="width: 90px" valign="top">
                        <?php $this->drawArticleInfo($this->news, $stamp) ?>
                    </td>
                </tr>

            </table>
        </p>
        <p class="author">
            <?php if ($this->can_edit): ?>
            <a href="<?php echo llink("/news/editnews/".$this->news->getId()); ?>/"><?php et("newsfull.edit"); ?></a>
            <?php endif ?>
            <?php if ($this->can_delete): ?>
            <a href="/news/deletenews/<?php echo $this->news->getId(); ?>/delete" onclick="return confirm_delete()">
                <?php et("newsfull.delete"); ?>
            </a>
            <?php endif ?>
        </p>
        <p>
            <?php echo $this->news->getParsedContent() ?>
        </p>
    <?php
    }

    function drawArticleInfo($news, $stamp, $cls = "article_info") { ?>
        <div class="<?php echo $cls ?>">
            <span class="header-big">
                <?php echo ucfirst(strftime("%b", $stamp)) .' '. strftime("%d", $stamp) .' '. strftime("%G", $stamp) ?>

                <?php
                $status = $news->getStatus();
                if ($status): ?>
                    <span class="bigNewsStatus" style="color:<?php echo $status[1]; ?>; display:none;"><?php echo $status[0]; ?></span>
                <?php endif ?>
            </span>
        </div>
        <?php
    }
}
