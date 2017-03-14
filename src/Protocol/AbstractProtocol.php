<?php
/**
 * AbstractProtocol.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    jack <linjue@wilead.com>
 * @copyright 2007-2017/3/14 WIZ TECHNOLOGY
 * @link      https://wizmacau.com
 * @link      https://lamjack.github.io
 * @link      https://github.com/lamjack
 * @version
 */

namespace Swoole\Protocol;

use Psr\Log\LoggerInterface;

/**
 * Class AbstractProtocol
 * @package Swoole\Protocol
 */
abstract class AbstractProtocol implements ProtocolInterface
{
    /**
     * @var \swoole_server
     */
    protected $server;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @param \swoole_server $server
     */
    public function setServer(\swoole_server $server)
    {
        $this->server = $server;
    }

    /**
     * @param LoggerInterface $log
     */
    public function setLog(LoggerInterface $log)
    {
        $this->log = $log;
    }

    /**
     * 输出调试信息
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, $context = [])
    {
        if (null !== $this->log)
            $this->log->debug($message, $context);
    }

    /**
     * 输出错误信息
     *
     * @param string $message
     * @param array $context
     */
    public function error($message, $context = [])
    {
        if (null !== $this->log)
            $this->log->error($message, $context);
    }
}