<?php
namespace Deploy\Worker;

use Deploy\Site\DeployConfig;
use Deploy\Site\Site;
use Exception;


class DeployScript
{
    /**
     * 编译脚本(字符串格式), 返回编译后的命令数组
     * @param $script
     */
    public static function complie($script, $siteId)
    {
        $s = new \Symfony\Component\Process\Process('sdf');
        $s->run();

        $realScript = self::twigVar($script, $siteId);
        $commandList = self::commandInit();

        $lines = explode("\n", $realScript);

        //去除空行
        $scriptLines = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $scriptLines[] = $line;
        }
        $startPattern = '/^@(after|before):(remote|local|handle)$/i';
        $len = count($scriptLines);
        for ($psl=0; $psl<$len;) {
            while ($psl < $len && $scriptLines[$psl][0] <> '@') {
                if (strlen(trim($scriptLines[$psl])) <> 0) {
                    throw new Exception('不属于任何执行步骤的命令: ' . $scriptLines[$psl]);
                }
                $psl++;
            }
            if ($psl >= $len) break;
            if (preg_match($startPattern, $scriptLines[$psl], $matchs)) {
                $order   = $matchs[1];
                $position = $matchs[2];
                $psl++;
                while (($psl < $len)  && ($scriptLines[$psl][0] <> '@')) {
                    $commandList[$order][$position][] = $scriptLines[$psl];
                    $psl++;
                }
            } else {
                throw new Exception('complie error at : ' . $scriptLines[$psl]);
            }
        }

        return $commandList;
    }

    private static function commandInit()
    {
        return array(
            'after' => array(
                'remote' => array(),
                'local'  => array(),
                'handle' => array(),
            ),
            'before' => array(
                'remote' => array(),
                'local'  => array(),
                'handle' => array(),
            )
        );
    }

    private static function twigVar($script, $varList)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_String(), array('strict_variables' => true));
        return $twig->render($script, $varList);
    }

    public static function varList(Site $site, DeployConfig $config)
    {
        $varList = array_merge($site->getAttributes(), $config->getAttributes());
        unset($varList['id']);
        unset($varList['name']);
        unset($varList['pull_key']);
        unset($varList['pull_key_passphrase']);
        unset($varList['deploy_key']);
        unset($varList['deploy_key_passphrase']);
        unset($varList['created_at']);
        unset($varList['updated_at']);

        return $varList;
    }
}
