<?php
require_once __DIR__ . '/../../api/PhModule.php';

class Paginator extends PhModule
{
    /**
     * @var int
     */
    public int $total_page_count = 0;

    /**
     * @var int
     */
    private int $total_count = 0;

    function getTotalCount($arr, $limit, $start = 0)
    {
        if ($this->getOffset($limit)) {
            $start = $this->getOffset($limit) + $start;
            $total_count = count($arr);
        } else
            $total_count = count($arr) - $start;
        return $total_count;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total_count;
    }

    public function getOffset($per_page, $current_page = null)
    {
        $page = $this->getPageFromQuery(0, $current_page);

        return $page === 0 ? $page : (($page - 1) * $per_page);
    }

    function getRightArrowNum()
    {
        return (int)min($this->total_page_count - 1, $this->end_page);
    }

    function showRightArrow()
    {
        return $this->cur_page + (int)floor($this->per_sub / 2) < $this->total_page_count;
    }

    function getLeftArrowNum()
    {
        return (int)max(1, $this->start_page - 1);
    }

    function showLeftArrow()
    {
       return $this->cur_page > 1;
    }

    function getPageFromQuery($default_page, $current_page = null)
    {
        if (!is_null($current_page)) {
            return $current_page;
        }

        $cur_page = $default_page;
        if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
            $cur_page = $_GET['page'];
        }

        return $cur_page;
    }

    public function setPages($total_count, $base_url, $per_page, $per_sub = 10, $current_page = null)
    {
        $this->total_count = $total_count;

        if ($total_count <= $per_page)
            return;

        if ($total_count / $per_page < $per_sub)
            $this->per_sub = (int)floor($total_count / $per_page) + 1;
        else
            $this->per_sub = $per_sub;

        $this->pages = array();
        $this->base_url = $base_url;
        $this->per_page = $per_page;

        $this->pages_per_sub = $this->per_sub * $per_page;

        $this->cur_page = $this->getPageFromQuery(1, $current_page);

        $this->start_page = (int)max(1, $this->cur_page - ($this->per_sub / 2));

        $this->total_page_count = (int)ceil($total_count / $per_page) + 1;

        $this->end_page = (int)min($this->start_page + $this->per_sub, $this->total_page_count);

        $this->db_offset = ($this->cur_page - 1) * $per_page;

        $i = $this->start_page;

        do {
            $this->pages[] = array(
                'page_nbr' => $i,
                'offset' => ($i - 1) * $per_page,
                'class' => $i == $this->cur_page ? "current_link" : "page_link"
            );
            $i++;
        } while ($i < $this->end_page);
    }

    public function render($click_func = '', $extraparams = '')
    {
        if (empty($click_func))
            $str = "href=\"$this->base_url?page=%d$extraparams\"";
        else
            $str = "onclick=\"$click_func(%d)\"";
        ?>
        <div class="paginator">
            <?php if ($this->showLeftArrow()): ?>
                <div class="paginator-item">
                    <a <?php echo str_replace('%d', $this->getLeftArrowNum(), $str) ?> class="arrow">
                        &#xAB;
                    </a>
                </div>
            <?php endif ?>
            <div class="paginator-items">
                <?php foreach ($this->pages as $page): ?>
                    <div class="paginator-item">
                        <a <?php echo str_replace('%d', $page['page_nbr'], $str) ?>
                                class="<?php echo $page['class'] ?> ">
                            <?php echo $page['page_nbr'] ?>
                        </a>
                    </div>
                <?php endforeach ?>
            </div>
            <?php if ($this->showRightArrow()): ?>
                <div class="paginator-item">
                    <a <?php echo str_replace('%d', $this->getRightArrowNum(), $str) ?> class="arrow">
                        &#xBB;
                    </a>
                </div>
            <?php endif ?>
        </div>
        <?php
    }

    /**
     * @return int
     */
    public function getTotalPageCount(): int
    {
        return isset($this->total_page_count) ? $this->total_page_count - 1 : 1;
    }
}
