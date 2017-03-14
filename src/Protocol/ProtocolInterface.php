<?php
/**
 * ProtocolInterface.php
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

namespace Swoole\Protocol;

/**
 * 协议接口
 *
 * Interface ProtocolInterface
 * @package Swoole\Protocol
 */
interface ProtocolInterface
{
    /**
     * Server启动在主进程的主线程回调此函数
     *
     * @param \swoole_server $server swoole_server对象
     */
    public function onStart($server);

    /**
     * 有新的连接进入时，在worker进程中回调
     *
     * @param \swoole_server $server swoole_server对象
     * @param int $clientId 连接的文件描述符，发送数据/关闭连接时需要此参数
     * @param int $fromId 来自那个Reactor线程
     */
    public function onConnect($server, $clientId, $fromId);

    /**
     * 接收到数据时回调此函数，发生在worker进程中
     *
     * @param \swoole_server $server swoole_server对象
     * @param int $clientId 连接的文件描述符，发送数据/关闭连接时需要此参数
     * @param int $fromId 来自那个Reactor线程
     * @param mixed $data 收到的数据内容，可能是文本或者二进制内容
     */
    public function onReceive($server, $clientId, $fromId, $data);

    /**
     * TCP客户端连接关闭后，在worker进程中回调此函数
     *
     * @param \swoole_server $server swoole_server对象
     * @param int $clientId 连接的文件描述符，发送数据/关闭连接时需要此参数
     * @param int $fromId 来自那个Reactor线程
     */
    public function onClose($server, $clientId, $fromId);

    /**
     * 此事件在Server结束时发生
     *
     * @param \swoole_server $server
     */
    public function onShutdown($server);
}