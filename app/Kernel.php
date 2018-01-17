<?php

class kernel
{
    public function run($argv)
    {
        if (PHP_SAPI != 'cli')
        {
            exit('非法请求');
        }
        if (empty($argv) || $argv[0] != 'index.php')
        {
            exit("非法请求");
        }

        if (empty($argv[1]))
        {
            exit("缺少参数");
        }

        $params = $argv[1];
        $suffix = strrpos($params, '/');
        $method = substr($params, $suffix + 1);
        $srcFile = substr($params, 0,$suffix);

        $file = ROOT."/src/Controller/".$srcFile.'.php';
        if (!is_file($file))
        {
            exit("{$file}文件不存在");
        }

        $srcFile = "Controller\\".str_replace("/", "\\", $srcFile);
        $ctrl = new $srcFile();

        if (false === method_exists($ctrl,$method))
        {
            exit("{$method}方法不存在");
        }

        $methodReflection = new ReflectionMethod($ctrl, $method);
        if (false === $methodReflection->isPublic())
        {
            exit("{$method}方法必须为公共方法");
        }

        unset($argv[0], $argv[1]);

        if (!empty($argv))
        {
            $ctrl->$method(array_values($argv));
        }
        else
        {
            $ctrl->$method();
        }
        
    }
}