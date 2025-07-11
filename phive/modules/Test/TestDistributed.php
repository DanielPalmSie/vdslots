<?php


class TestDistributed extends TestPhive
{

    /** @var Publisher $publisher */
    public $publisher;

    public function __construct($module)
    {
        $this->publisher = phive('Site/Publisher');

        parent::__construct($module);
    }

    public function singlePublish($iterations = 1)
    {
        $start = microtime(true);
        for ($i = 0; $i <= $iterations; $i++) {
            $this->publisher->single('booster-test', 'Site/Queued', 'testQueue', ["msg{$i}", 1]);
        }
        echo "One by one publish. ". (microtime(true) - $start) ."\n";
    }

    public function bulkPublish($iterations = 100)
    {
        $start = microtime(true);
        $args = [];
        for ($i = 0; $i <= $iterations; $i++) {
            $args[] = ["msg{$i}", 10];
        }
        $this->publisher->bulk('booster-test', 'Site/Queued', 'testQueue', $args);
        echo "Bulk publish. ". (microtime(true) - $start) ."\n";
    }
}