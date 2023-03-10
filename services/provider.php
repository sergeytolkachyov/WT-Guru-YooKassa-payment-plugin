<?php

/**
 *  @package   WT Gurupayment Yookassa
 *  @copyright Copyright Sergey Tolkachyov
 *  @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Gurupayment\Wtguruyookassa\Extension\Wtguruyookassa;

return new class () implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$dispatcher = $container->get(DispatcherInterface::class);
                $plugin     = new Wtguruyookassa(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('gurupayment', 'Wtguruyookassa')
                );

                return $plugin;
			}
		);
	}
};