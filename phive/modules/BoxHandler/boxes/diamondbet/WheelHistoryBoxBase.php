<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__ . '/../../../DBUserHandler/JpWheel.php';

class WheelHistoryBoxBase extends DiamondBox
{

    public function init()
    {
        $this->cu = cuPl();
        $this->wh = new JpWheel();   
        $this->th = phive('Trophy');
        $this->userid = $this->getId();
        $this->p = phive('Paginator');

    }

    function printCSS()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "wheel.css");
    }
    
    function initWheel()
    {
        if(empty($this->cu)){
            $this->printLoggedOut();
            exit;
        }else
            return;
    }

    function printHTML()
    {
        $this->initWheel();
        loadJs("/phive/js/wheel.js"); 
        $this->printWheelHistory();
    }


    function printWheelHistory()
    {
        $page       = empty($_GET['page']) ? 1 : (int)$_GET['page'];
        $start_date = phive()->validateDate($_GET['start_date']) ? phive()->fDate($_GET['start_date']) : phive()->modDate(null, '-1 month');
        $end_date   = phive()->validateDate($_GET['end_date']) ? phive()->fDate($_GET['end_date']) : phive()->hisNow();
        $wheelCount = $this->wh->getWheelCount($this->cu->userId, $start_date, $end_date);
        $wheelspin  = $this->wh->getWheelHistory($this->cu->userId, $page, $start_date, $end_date);
        $this->p->setPages($wheelCount, '', 15);
        $params = [
            'wheelspin'  => $wheelspin,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'page'       => $page,
        ];
        $this->printWheelHistoryHTML($params);
    }


    function printWheelHistoryHTML($params)
    {
        extract($params);
        $this->printCSS();
        ?>
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->getJQueryUIVersion() ?>jquery-ui.min.css">
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->getJQueryUIVersion() ?>jquery-ui.theme.min.css?v3">
        <script type="text/javascript">
            $(document).ready(function () {
                $('input.w-a-date').datepicker({
                    showButtonPanel: false,
                    dateFormat: 'yy-mm-dd'
                });
            });
        </script>
        <div class="general-account-holder">
            <div class="simple-box pad-stuff-ten">
                <h3><?php et("wheel.wheelhistory") ?></h3>
                <form class="w-h-filter-form" id="form" method="get" autocomplete="off">
                    <div class="w-a-filter-label">
                        <label><?php et('from') ?></label>
                        <input class="w-a-date" type="text" name="start_date" value="<?php echo $start_date ?>"></input>
                    </div>
                    <div class="w-a-filter-label">
                        <label><?php et('to') ?></label>
                        <input class="w-a-date" type="text" name="end_date" value="<?php echo $end_date ?>"></input>
                    </div>
                    <div class="w-a-filter-label">
                        <button id="btn-search" class="w-a-btn w-a-btn-search"></button>
                    </div>
                </form>
                <table class="zebra-tbl" width="100%">
                    <tr class="zebra-header">
                        <td width="25%"><?php echo et('wheel.spintime') ?></td>
                        <td width="75%"><?php echo et('wheel.prizewon') ?></td>
                    </tr>
                    <?php
                    foreach ($wheelspin as $index => $row):
                        $awardId   = $row['win_award_id'];
                        $awardDesc = $this->th->getAward($awardId)['description'];
                        ?>
                        <tr class="<?php echo $index % 2 == 0 ? 'even' : 'odd' ?>">
                            <td><?php echo phive()->lcDate($row['created_at']) . ' ' . t('cur.timezone') ?></td>
                            <td><?php echo rep($awardDesc) ?></td>
                        </tr>
                    <?php endforeach ?>

                </table>
                <?php $this->p->render('', "&start_date=$start_date&end_date=$end_date") ?>
            </div>
        </div>
    <?php }
    

    function printExtra()
    {

        
    }
    
    
    function printLoggedOut()
    {
        exit;
    }


}
