<?php
/**
 * Kernel.php
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

namespace Swoole;

/**
 * Class Kernel
 * @package Swoole
 */
class Kernel
{
    /**
     * @var Kernel
     */
    static private $instance;

    static $charset = 'utf-8';

    /**
     * @var Request
     */
    public $request;

    /**
     * @var Response
     */
    public $response;

    private function __construct()
    {
    }

    /**
     * 获取类实例
     *
     * @return Kernel
     */
    static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Kernel();
        }
        return self::$instance;
    }
}