<?php
/**
 * ServerInterface.php
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
 * Interface ServerInterface
 * @package Swoole\Server
 */
interface ServerInterface
{
    /**
     * @param $setting
     * @return mixed
     */
    public function run($setting);

    /**
     * @param $clientId
     * @param $data
     * @return mixed
     */
    public function send($clientId, $data);

    /**
     * @param $clientId
     * @return mixed
     */
    public function close($clientId);

    /**
     * @return mixed
     */
    public function shutdown();

    /**
     * Set protocol
     *
     * @param ProtocolInterface $protocol
     */
    public function setProtocol(ProtocolInterface $protocol);
}