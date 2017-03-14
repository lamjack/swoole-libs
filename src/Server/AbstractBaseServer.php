<?php
/**
 * AbstractBaseServer.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    jack <linjue@wilead.com>
 * @copyright 2007-2017/3/13 WIZ TECHNOLOGY
 * @link      https://wizmacau.com
 * @link      https://lamjack.github.io
 * @link      https://github.com/lamjack
 * @version
 */
namespace Swoole\Server;

use Swoole\Protocol\ProtocolInterface;

/**
 * Class AbstractBaseServer
 * @package Swoole\Server
 */
abstract class AbstractBaseServer implements ServerInterface
{
    protected $processName;

    /**
     * @var ProtocolInterface
     */
    protected $protocol;

    /**
     * @param string $processName
     */
    public function setProcessName($processName)
    {
        $this->processName = $processName;
    }

    /**
     * {@inheritdoc}
     */
    public function setProtocol(ProtocolInterface $protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * 获取进程名称
     *
     * @return string
     */
    public function getProcessName()
    {
        if (empty($this->processName)) {
            global $argv;
            return "php {$argv[0]}";
        } else {
            return $this->processName;
        }
    }
}