<?php
/**
 * WebSocket.php
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

abstract class WebSocket extends Http
{
    /**
     * {@inheritdoc}
     */
    public function onConnect($server, $clientId, $fromId)
    {

    }
}