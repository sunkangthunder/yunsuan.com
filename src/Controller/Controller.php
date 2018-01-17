<?php
namespace Controller;

use Core\Loader;

class Controller
{

    public function __construct()
    {
        if (!isset($this->load))
        {
            $this->load = new Loader();
        }
    }

    /**
     * 根据uid获取分区
     *
     * @author sunkang <sunkang@km.com>
     * @param $uid
     * @param int $length
     * @return int|string
     */
    public function getTail($uid, $length = 3)
    {
        $sequence = substr(md5($uid), 0, $length);
        $sequence = (hexdec($sequence) + 1) % 1024;
        return $sequence;
    }

    /**
     * 根据时间获取周一周日
     *
     * @param $date
     * @return array
     */
    protected function getStartAndEndDate($date)
    {
        $timeStamp = strtotime($date);
        $n = date('N', $timeStamp);
        $startTimeStamp = $timeStamp - ($n - 1) * 86400;
        $endTimeStamp = $startTimeStamp + 6 *86400;

        return [
            'startDate' => date('Y-m-d', $startTimeStamp),
            'endDate'   => date('Y-m-d', $endTimeStamp)
        ];
    }

    /**
     * 任务调度
     *
     * @param $class
     * @param $method
     * @param array $params
     */
    protected function dispatch($class, $method, $params = [])
    {
        $ctrl = new $class;
        $ctrl->$method($params);
    }

}