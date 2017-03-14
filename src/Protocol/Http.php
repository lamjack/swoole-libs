<?php
/**
 * Http.php
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

abstract class Http extends Web
{
    public function onStart($server)
    {
        // TODO: Implement onStart() method.
    }

    public function onConnect($server, $clientId, $fromId)
    {
        // TODO: Implement onConnect() method.
    }

    function onReceive($server, $clientId, $fromId, $data)
    {
        // TODO: Implement onReceive() method.
    }

    function onClose($server, $clientId, $fromId)
    {
        // TODO: Implement onClose() method.
    }

    function onShutdown($server)
    {
        // TODO: Implement onShutdown() method.
    }

}