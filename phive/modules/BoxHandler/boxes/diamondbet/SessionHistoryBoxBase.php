<?php

require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class SessionHistoryBoxBase extends DiamondBox
{
    /** @var null|DBUser $user */
    protected $user = null;

    /** @var null|Paginator $paginator */
    private $paginator;

    /** @var SQL $db */
    private $db;

    /**
     * @param $user
     */
    public function init($user)
    {
        $this->user = cu($user);
        $this->paginator = phive('Paginator');
        $this->db = phive('SQL')->sh($this->user->getId());
    }

    public function printHTML()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "g-a-history.css");

        $start_date = $this->getFilterDate($_GET['start_date'], '-1 day');
        $end_date = $this->getFilterDate($_GET['end_date']);
        ?>
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->getJQueryUIVersion() ?>jquery-ui.min.css">
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->getJQueryUIVersion() ?>jquery-ui.theme.min.css?v3">
        <script type="text/javascript">
            $(document).ready(function () {
                $('input.g-a-date').datepicker({
                    showButtonPanel: false,
                    dateFormat: 'yy-mm-dd'
                });
            });
        </script>


        <div class="simple-box pad-stuff-ten login-history">
            <h3 class="g-a-title"><?= t('session-history') ?></h3>
            <table class="zebra-tbl">
                <colgroup>
                    <col width="200">
                    <col width="360">
                    <col width="100">
                </colgroup>
                <tbody>
                <tr>
                    <td colspan="3" class="login-history--form-wrapper">
                        <form class="g-a-filter-form" action="" method="get" autocomplete="off">
                        <?php if (phive()->isMobile()): ?>
                            <div class="g-a-filter-label__block">
                                <div class="g-a-filter-label g-a-filter-label--mobile">
                                    <label><?php et('from') ?></label>
                                    <input class="g-a-date" type="text" name="start_date" value="<?php echo $start_date ?>" ></input>
                                </div>
                                <div class="g-a-filter-label g-a-filter-label--mobile">
                                    <label><?php et('to') ?></label>
                                    <input class="g-a-date" type="text" name="end_date" value="<?php echo $end_date ?>" ></input>
                                </div>
                                <div class="g-a-filter-label g-a-filter-label--mobile login-history-mobile-search-button">
                                    <button id="btn-search" class="g-a-btn g-a-btn-search g-a-btn-search--mobile icon icon-vs-search"></button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="g-a-filter-label">
                                <label><?php et('from') ?></label>
                                <input class="g-a-date" type="text" name="start_date" value="<?php echo $start_date ?>" ></input>
                            </div>
                            <div class="g-a-filter-label">
                                <label><?php et('to') ?></label>
                                <input class="g-a-date" type="text" name="end_date" value="<?php echo $end_date ?>" ></input>
                            </div>
                            <div class="g-a-filter-label">
                                <button id="btn-search" class="g-a-btn g-a-btn-search icon icon-vs-search"></button>
                            </div>
                        <?php endif ?>
                        </form>
                    </td>
                </tr>
                <tr class="zebra-header">
                    <td><?=t('session-history.started_at')?></td>
                    <td><?=t('session-history.device')?></td>
                    <td><?=t('session-history.ip')?></td>
                </tr>
                <? foreach ($this->getUserSessions($start_date, $end_date) as $index => $session): ?>
                    <tr class="<?= $index % 2 == 0 ? 'even' : 'odd' ?>">
                        <td><?= $session['created_at'] ?></td>
                        <td><?= et('session.equipment.'.$session['equipment']) ?></td>
                        <td><?= $session['ip'] ?></td>
                    </tr>
                <? endforeach; ?>
                </tbody>
            </table>
            <br>
            <?php $this->paginator->render('', "&start_date=" . $start_date . "&end_date=" . $end_date) ?>
        </div>
        <?php
    }

    /**
     * @param string $start_date
     * @param string $end_date
     * @param int|null $current_page
     *
     * @return array
     */
    public function getUserSessions(string $start_date, string $end_date, ?int $current_page = null, ?int $page_size = 11): array
    {
        $user_id = $this->user->getId();
        $entries_count = $this->db->getValue("SELECT count(*) FROM users_sessions WHERE user_id = {$user_id} AND created_at BETWEEN '{$start_date}' AND '{$end_date}'");
        $this->paginator->setPages($entries_count, '', $page_size, 10, $current_page);
        // If the offset is missing it will simply be converted to 0 by (int) so we start at the beginning,
        // perhaps not the wanted behaviour but probably better than broken SQL which displays nothing.
        $limit = $this->db->getLimit($page_size, (int)$this->paginator->db_offset);

        return $this->db->loadArray("
            SELECT created_at, equipment, ip FROM users_sessions
            WHERE user_id = {$user_id}
            AND created_at BETWEEN '{$start_date}' AND '{$end_date}'
            ORDER BY created_at DESC
            $limit
        ");
    }

    /**
     * @param string|null $date
     * @param string|null $modifier
     *
     * @return string
     */
    public function getFilterDate(?string $date, ?string $modifier = '+1 day'): string
    {
        return phive()->validateDate($date) ? phive()->fDate($date) : phive()->modDate(null, $modifier);
    }

    /**
     * @return Paginator
     */
    public function getPaginator(): Paginator
    {
        return $this->paginator;
    }
}
