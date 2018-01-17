<?php
namespace Core;

class Loader
{
    /**
     * 加载对象的全局树
     *
     * @var
     */
    protected $tree;

    /**
     * 获取配置信息
     *
     * @param $key
     * @return mixed
     */
    public function config($key)
    {
        $configs = include_once ROOT.'/app/config/config.php';
        return $configs[$key];
    }

    /**
     * 加载资源库
     *
     * @param $name
     * @return mixed
     */
    public function library($name)
    {
        if (empty($this->tree['Library'][$name]))
        {
            $class = "\\Library\\{$name}";
            $this->tree['Library'][$name] = new $class();
        }

        return $this->tree['Library'][$name];
    }
}