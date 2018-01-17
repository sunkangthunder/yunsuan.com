<?php
namespace Controller\Task;

use Controller\Controller;
use Model\Db;

/**
 * 运算启动次数相关的程序
 *
 * @author sunkang
 * Class Launch
 * @package Controller
 */
class Launch extends Controller
{
    /**
     * 版本维度
     *
     * @var string
     */
    protected $tableVersion;

    /**
     * 渠道维度
     *
     * @var string
     */
    protected $tableChannel;

    /**
     * 天维度
     *
     * @var string
     */
    protected $tableDay;

    /**
     * 渠道版本维度
     *
     * @var string
     */
    protected $tableChannelVersion;

    /**
     * 周(全部)维度
     *
     * @var string
     */
    protected $weekAll;

    /**
     * 周版本维度
     *
     * @var string
     */
    protected $weekVersion;

    /**
     * 周渠道维度
     *
     * @var string
     */
    protected $weekChannel;

    /**
     * 周版本渠道维度
     *
     * @var string
     */
    protected $weekChannelVersion;

    /**
     * 数据库链接
     *
     * @var null
     */
    protected $db = null;

    /**
     *
     */
    const MERGE_CHANNEL_VALUE = "jf";


    public function Index($params)
    {
        $project = $params['project'];
        if (empty($project))
        {
            exit("项目名称不能为空");
        }
        $allowProjects = $this->load->config('project');
        if (!in_array($project, $allowProjects))
        {
            exit("项目不存在");
        }

        $this->tableVersion         = 'data_version_daily_'.$project;
        $this->tableChannel         = 'data_channel_daily_'.$project;
        $this->tableDay             = 'data_daily_'.$project;
        $this->tableChannelVersion  = 'data_channel_version_daily_'.$project;
        $this->weekAll              = 'data_weekly_'.$project;
        $this->weekVersion          = 'data_version_weekly_'.$project;
        $this->weekChannel          = 'data_channel_weekly_'.$project;
        $this->weekChannelVersion   = 'data_channel_version_weekly_'.$project;

        $appVersion     = $params['appVersion'];
        $channel        = $params['channel'];
        $historyChannel = $channel;
        $mainchannel    = $params['mainchannel'];

        if (!empty($mainchannel))
        {
            $channel        = $mainchannel;
            $historyChannel = $mainchannel;
        } else
        {
            if (stripos($channel, 'm-') === 0)
            {
                $channel = self::MERGE_CHANNEL_VALUE;
                $historyChannel = self::MERGE_CHANNEL_VALUE;
            }
        }

        /**
         * historyChannel 暂时用户channel替代
         */

        $uid  = $params['uid'];
        $tail = $this->getTail($uid);

        if (empty($params['launches']))
        {
            return false;
        }

        $this->db = Db::getInstance("mbang_{$project}");
        $launches = $params['launches'];
        foreach ($launches as $k => $launch)
        {
            $date = $launch['date'];
            
            $this->db->action(function($db)use($date, $appVersion, $channel, $historyChannel){

                // 运算版本维度的启动次数
                $this->combineOneDayByVersion($date, $appVersion);
                // 运算渠道维度的启动次数
                $this->combineOneDayByChannel($date, $channel);
                // 运算天维度的启动次数
                $this->combineOneDayByAll($date);
                // 运算渠道版本维度的启动次数
                $this->combineOneDayByVersionChannel($date, $appVersion, $channel);
                // 运算历史渠道维度的启动次数
                $this->combineOneDayByHistoryChannel($date, $historyChannel);
                // 运算 历史渠道 版本 维度的启动次数
                $this->combineOneDayByVersionHistoryChannel($date, $appVersion, $historyChannel);
                // 运算周维度的启动次数
                $this->combineWeekall($date);
                // 运算周版本维度的启动次数
                $this->combineWeekVersion($date, $appVersion);
                // 运算周渠道维度的启动次数
                $this->combineWeekChannel($date, $channel);
                // 运算周版本渠道维度的启动次数
                $this->combineWeekVersionChannel($date, $appVersion, $channel);
                // 运算周历史渠道维度的启动次数
                $this->combineWeekByHistoryChannel($date, $historyChannel);
                // 运算周 版本 历史渠道 维度的启动次数
                $this->combineWeekByVersionHistoryChannel($date, $appVersion, $historyChannel);
                
            });
            
        }

    }

    /**
     * 运算版本维度的启动次数
     *
     * @param $date
     * @param $appVersion
     */
    protected function combineOneDayByVersion($date, $appVersion)
    {
        $filterData = [
            'day'     => $date,
            'version' => $appVersion,
        ];
        $appVersionData = $this->db->get($this->tableVersion, ['id','launch_num'], $filterData);
        if (!empty($appVersionData))
        {

            $id = $appVersionData['id'];
            $updateData = [
                'launch_num' => $appVersionData['launch_num'] + 1
            ];
            $result = $this->db->update($this->tableVersion, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'day'      => $date,
                'version'   => $appVersion,
                'launch_num' => 1
            ];

            $this->db->insert($this->tableVersion, $insertData);
        }
    }

    /**
     * 运算渠道维度的启动次数
     *
     * @param $date
     * @param $channel
     */
    protected function combineOneDayByChannel($date, $channel)
    {
        $filterData = [
            'day'     => $date,
            'channel' => $channel,
        ];
        $appChannelData = $this->db->get($this->tableChannel, ['id', 'launch_num'], $filterData);
        if (!empty($appChannelData))
        {
            $id = $appChannelData['id'];
            $updateData = [
                'launch_num' => $appChannelData['launch_num'] + 1
            ];
            $result = $this->db->update($this->tableChannel, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'day'       => $date,
                'channel'   => $channel,
                'launch_num' => 1
            ];

            $this->db->insert($this->tableChannel, $insertData);
        }
    }

    /**
     * 运算天维度的启动次数
     *
     * @param $date
     */
    protected function combineOneDayByAll($date)
    {
        $filterData = [
            'day'     => $date,
        ];
        $appData = $this->db->get($this->tableDay, ['id', 'launch_num'], $filterData);
        if (!empty($appData))
        {
            $id = $appData['id'];
            $updateData = [
                'launch_num' => $appData['launch_num'] + 1
            ];
            $result = $this->db->update($this->tableDay, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'day'       => $date,
                'launch_num' => 1
            ];
            $this->db->insert($this->tableDay, $insertData);
        }
    }

    /**
     * 运算渠道版本维度的启动次数
     *
     * @param $date
     * @param $appVersion
     * @param $channel
     */
    protected function combineOneDayByVersionChannel($date, $appVersion, $channel)
    {
        $filterData = [
            'day'     => $date,
            'channel' => $channel,
            'version' => $appVersion];
        $appChannelVersionData = $this->db->get($this->tableChannelVersion, ['id', 'launch_num'], $filterData);
        if (!empty($appChannelVersionData))
        {
            $id = $appChannelVersionData['id'];
            $updateData = [
                'launch_num' => $appChannelVersionData['launch_num'] + 1
            ];
            $result = $this->db->update($this->tableChannelVersion, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'day'        => $date,
                'launch_num' => 1,
                'channel'    => $channel,
                'version'    => $appVersion
            ];
            $this->db->insert($this->tableChannelVersion, $insertData);
        }
    }

    /**
     * 运算历史渠道维度的启动次数
     *
     * @param $date
     * @param $historyChannel
     */
    protected function combineOneDayByHistoryChannel($date, $historyChannel)
    {
        $filterData = [
            'day'     => $date,
            'channel' => $historyChannel,
        ];

        $data = $this->db->get($this->tableChannel, ['id','history_launch_num'], $filterData);
        if (!empty($data))
        {
            $id = $data['id'];
            $updateData = [
                'history_launch_num' => $data['history_launch_num'] + 1
            ];
            $result = $this->db->update($this->tableChannel, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'day'        => $date,
                'history_launch_num' => 1,
                'channel'    => $historyChannel,
            ];
            $this->db->insert($this->tableChannel, $insertData);
        }
    }

    /**
     * 运算周维度的启动次数
     *
     * @param $date
     * @param $appVersion
     * @param $historyChannel
     */
    protected function combineOneDayByVersionHistoryChannel($date, $appVersion, $historyChannel)
    {
        $filterData = [
            'day'     => $date,
            'channel' => $historyChannel,
            'version' => $appVersion,
        ];
        $data = $this->db->get($this->tableChannel, ['id','history_launch_num'], $filterData);
        if (!empty($data))
        {
            $id = $data['id'];
            $updateData = [
                'history_launch_num' => $data['history_launch_num'] + 1
            ];
            $result = $this->db->update($this->tableChannelVersion, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'day'        => $date,
                'history_launch_num' => 1,
                'version'    => $appVersion,
                'channel'    => $historyChannel,
            ];
            $this->db->insert($this->tableChannel, $insertData);
        }
    }

    /**
     * 运算周维度的启动次数
     *
     * @param $date
     */
    protected function combineWeekall($date)
    {
        $timeLine = $this->getStartAndEndDate($date);
        $startDate = $timeLine['startDate'];
        $endDate   = $timeLine['endDate'];
        $filterData = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];

        $data = $this->db->get($this->weekAll, ['id','launch_num'], $filterData);
        if (!empty($data))
        {
            $id = $data['id'];
            $updateData = [
                'launch_num' => $data['launch_num'] + 1
            ];
            $result = $this->db->update($this->weekAll, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'start_date' => $startDate,
                'end_date'    => $endDate,
                'launch_num' => 1,
            ];
            $this->db->insert($this->weekAll, $insertData);

        }
    }

    /**
     * 运算周版本维度的启动次数
     *
     * @param $date
     * @param $appVersion
     */
    protected function combineWeekVersion($date, $appVersion)
    {
        $timeLine = $this->getStartAndEndDate($date);
        $startDate = $timeLine['startDate'];
        $endDate   = $timeLine['endDate'];
        $filterData = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'version'    => $appVersion,
        ];
        $data = $this->db->get($this->weekVersion, ['id','launch_num'], $filterData);
        if (!empty($data))
        {
            $id = $data['id'];
            $updateData = [
                'launch_num' => $data['launch_num'] + 1
            ];
            $result = $this->db->update($this->weekVersion, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'start_date' => $startDate,
                'end_date'    => $endDate,
                'launch_num' => 1,
                'version' => $appVersion
            ];
            $this->db->insert($this->weekVersion, $insertData);
        }
    }

    /**
     * 运算周渠道维度的启动次数
     *
     * @param $date
     * @param $channel
     */
    protected function combineWeekChannel($date, $channel)
    {
        $timeLine = $this->getStartAndEndDate($date);
        $startDate = $timeLine['startDate'];
        $endDate   = $timeLine['endDate'];
        $filterData = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'channel'    => $channel,
        ];
        $data = $this->db->get($this->weekChannel, ['id','launch_num'], $filterData);
        if (!empty($data))
        {
            $id = $data['id'];
            $updateData = [
                'launch_num' => $data['launch_num'] + 1
            ];
            $result = $this->db->update($this->weekChannel, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'start_date' => $startDate,
                'end_date'    => $endDate,
                'launch_num' => 1,
                'channel' => $channel
            ];
            $this->db->insert($this->weekChannel, $insertData);
        }
    }

    /**
     * 运算周版本渠道维度的启动次数
     *
     * @param $date
     * @param $appVersion
     * @param $channel
     */
    protected function combineWeekVersionChannel($date, $appVersion, $channel)
    {
        $timeLine = $this->getStartAndEndDate($date);
        $startDate = $timeLine['startDate'];
        $endDate   = $timeLine['endDate'];
        $filterData = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'version'    => $appVersion,
            'channel'    => $channel,
        ];
        $data = $this->db->get($this->weekChannelVersion, ['id','launch_num'], $filterData);
        if (!empty($data))
        {
            $id = $data['id'];
            $updateData = [
                'launch_num' => $data['launch_num'] + 1
            ];
            $result = $this->db->update($this->weekChannelVersion, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'start_date' => $startDate,
                'end_date'    => $endDate,
                'launch_num' => 1,
                'version' => $appVersion,
                'channel' => $channel
            ];
            $this->db->insert($this->weekChannelVersion, $insertData);
        }
    }

    /**
     * 运算周历史渠道维度的启动次数
     *
     * @param $date
     * @param $historyChannel
     */
    protected function combineWeekByHistoryChannel($date, $historyChannel)
    {
        $timeLine = $this->getStartAndEndDate($date);
        $startDate = $timeLine['startDate'];
        $endDate   = $timeLine['endDate'];
        $filterData = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'channel'    => $historyChannel,
        ];
        $data = $this->db->get($this->weekChannel, ['id','history_launch_num'], $filterData);
        if (!empty($data))
        {
            $id = $data['id'];
            $updateData = [
                'history_launch_num' => $data['history_launch_num'] + 1
            ];
            $result = $this->db->update($this->weekChannel, $updateData, ['id' => $id]);
        }
        else
        {
            $insertData = [
                'start_date' => $startDate,
                'end_date'    => $endDate,
                'launch_num' => 1,
                'channel' => $historyChannel
            ];
            $this->db->insert($this->weekChannel, $insertData);
        }
    }

    /**
     * 运算周 版本 历史渠道 维度的启动次数
     *
     * @param $date
     * @param $appVersion
     * @param $historyChannel
     */
    protected function combineWeekByVersionHistoryChannel($date, $appVersion, $historyChannel)
    {
        $timeLine = $this->getStartAndEndDate($date);
        $startDate = $timeLine['startDate'];
        $endDate   = $timeLine['endDate'];
        $filterData = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'channel'    => $historyChannel,
            'version'    => $appVersion
        ];
        $data = $this->db->get($this->weekChannelVersion, ['id','history_launch_num'], $filterData);
        if (!empty($data))
        {
            $id = $data['id'];
            $updateData = [
                'history_launch_num' => $data['history_launch_num'] + 1
            ];
            $result = $this->db->update($this->weekChannelVersion, $updateData, ['id' => $id]);

        }
        else
        {
            $insertData = [
                'start_date' => $startDate,
                'end_date'    => $endDate,
                'launch_num' => 1,
                'version'    => $appVersion,
                'channel'    => $historyChannel
            ];
            $this->db->insert($this->weekChannelVersion, $insertData);
        }
    }
}