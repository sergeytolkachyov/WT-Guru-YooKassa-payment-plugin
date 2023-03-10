<?php

/**
 * @package     WT Guru YooKassa
 *
 * @copyright   (C) 2023 Sergey Tolkachyov, <https://web-tolk.ru>
 * @license     GNU General Public License version 2 or later
 */

namespace Joomla\Plugin\Gurupayment\Wtguruyookassa\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Layout\FileLayout;
use YooKassa\Client;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Yookassa payment plugin for iGuru component
 *
 * @see https://yookassa.ru/
 * @see https://guru.ijoomla.com/features-guru
 * @since  1.0.0
 */
final class Wtguruyookassa extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.7.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  4.0.0
	 */
	protected $app;

	function onSendPayment(&$post)
	{
		if ($post['processor'] != 'wtguruyookassa') {
			return false;
		}

		$lang = Factory::getApplication()->getLanguage();
		$extension = 'plg_gurupayment_wtguruyookassa';
		$base_dir = JPATH_ADMINISTRATOR;
		$language_tag = '';
		$lang->load($extension, $base_dir, $language_tag, true);

		$params = json_decode($post['params'], true);
		$user = Factory::getApplication()->getIdentity();
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$sql = 'SELECT userid, amount, amount_paid, courses, promocodeid FROM #__guru_order WHERE id = '.intval($post['order_id']);
		$db->setQuery($sql);
		$order_details = $db->loadAssocList();

		$items = [];
		$order_amount = 0;
		foreach($post['products'] as $i => $item) {
			if ($i < 0) {
				continue;
			}

			$promo_amount = 0;
			if ($order_details['0']['promocodeid'] > 0) {
				$sql = 'SELECT discount, typediscount FROM #__guru_promos WHERE id ='.$order_details['0']['promocodeid'];
				$db->setQuery($sql);
				$db->execute();
				$promo = $db->loadAssocList();

				if ($promo) {
					if ($promo['0']['typediscount'] == 0) {
						$promo_amount = $promo['0']['discount'] / count($post['products']);
					} else {
						$promo_amount = $item['value'] * $promo['0']['discount'] / 100;
					}
				}
			}

			$amount = $item['value'] - $promo_amount;
			$order_amount = $order_amount + $amount;
			$product = array(
				'description' => $item['name'],
				'quantity' => '1',
				'amount' => array(
					'value' => $amount,
					'currency' => $params['currency']
				),
				'vat_code' => '2',
				'payment_mode' => 'full_payment',
				'payment_subject' => 'commodity',
			);

			$items[] = $product;

		}

		$shop_id = trim($params['shopId']);
		$secret_key = trim($params['secretKey']);
		/**
		 * @var $client \YooKassa\Client
		 */
		$client = new Client();
		$client->setAuth($shop_id, $secret_key);
		$userAgent = $client->getApiClient()->getUserAgent();
		$userAgent->setFramework('Joomla', (new Version())->getShortVersion());
		$userAgent->setCms('Joomla', (new Version())->getShortVersion());
		$wt_plugin_info = simplexml_load_file(JPATH_SITE."/plugins/gurupayment/wtguruyookassa/wtguruyookassa.xml");
		$userAgent->setModule('Joomla iGuru YooKassa', $wt_plugin_info->version);

		$return_url = Route::_(Uri::root().'index.php?option=com_guru&view=guruorders&layout=mycourses');
		try {
			$idempotenceKey = uniqid('', true);
			$response = $client->createPayment(
				array(
					'amount' => array(
						'value' => $order_amount,
						'currency' => $params['currency'],
					),
					'confirmation' => array(
						'type' => 'redirect',
						'locale' => 'ru_RU',
						'return_url' => $return_url,
					),
					'capture' => true,
					'description' => 'Order '.$post['order_id'],
					'metadata' => array(
						'orderNumber' => (int) $post['order_id']
					),
					'receipt' => array(
						'customer' => array(
							'full_name' => $user->name,
							'email' => $user->email
						),
						'items' => $items
					)
				),
				$idempotenceKey
			);

			$payment_id = $response->getId();
			$query = $db->getQuery(true);
			$query->clear();
			$query->insert('#__gurupayment_wtguruyookassa');
			$query->columns(array($db->quoteName('guru_order_id'), $db->quoteName('yookassa_payment_id')));
			$query->values((int) $post['order_id'] . ',' . $db->quote(trim($payment_id)));
			$db->setQuery($query);
			$db->execute();
			//получаем confirmationUrl для дальнейшего редиректа
			$confirmationUrl = $response->getConfirmation()->getConfirmationUrl();
			Factory::getApplication()->redirect($confirmationUrl);
		} catch (\Exception $e) {
			$response = $e;
		}


		$layout = new FileLayout('default', JPATH_ROOT . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, [
				'plugins', 'gurupayment', 'wtguruyookassa','tmpl'
			]));
		$html =  $layout->render([
		    'response' => $response,
		    'post' => $post,
			'user' => $user
		]);


		return $html;
	}

	function onReceivePayment($post)
	{
		$app = Factory::getApplication();
		$processor = $app->getInput()->get('processor','');

		if (empty($processor) || $processor != 'wtguruyookassa') {
			return 0;
		}

		if(!$app->getInput()->Json->getString('type') && $app->getInput()->Json->getString('type') != 'notification'){
			return 0;
		}
		$yookassa_event = $app->getInput()->Json->getString('event');
		$payment_info = $app->getInput()->Json->get('object');

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		if(isset($payment_info['metadata']['orderNumber']) && !empty($payment_info['metadata']['orderNumber'])){
			$order_id = $payment_info['metadata']['orderNumber'];
		} else {
			$query = $db->getQuery(true);
			$query->select($db->quoteName('guru_order_id'))
				->from($db->quoteName('#__gurupayment_wtguruyookassa'))
				->where($db->quoteName('yookassa_payment_id') . ' = '.$db->quote($payment_info['id']));
			$order_id = $db->setQuery($query)->loadResult();
		}

		if($payment_info['payment_method']['type'] == 'bank_card'){
			$card_digit = $payment_info['payment_method']['card']['first6'].'***'.$payment_info['payment_method']['card']['last4'];
			$card_type = $payment_info['payment_method']['card']['card_type'];
			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__guru_order'))
				->set($db->qn('card_digit') . '=' . $db->q($card_digit))
				->set($db->qn('card_type') . '=' . $db->q($card_type))
				->where($db->quoteName('id') . ' = '.$db->quote($order_id));

			$db->setQuery($query)->execute();
		}

		/**
		 * Model GuruByy
		 */
		$post['handle'];
		/**
		 * This plugin params json
		 */
		$post['params'];

		$out = [];
		$out['sid'] = $order_id;
		$out['order_id'] = $order_id;
		$out['processor'] = $processor;

		if($yookassa_event == 'payment.succeeded'){
			$out['pay'] = 'success';
		} elseif($yookassa_event == 'payment.canceled'){
			$out['pay'] = 'fail';
		} else {
			$out['pay'] = 'wait';
		}

		if($payment_info['payment_method']['type'] == 'bank_card'){
			$out['card_type'] = $payment_info['payment_method']['card']['card_type'];
			$out['card_digit'] = $payment_info['payment_method']['card']['first6'].'***'.$payment_info['payment_method']['card']['last4'];
		}

		$query->clear();
		$query->select([
			$db->quoteName('userid'),
			$db->quoteName('amount'),
			$db->quoteName('amount_paid')
		])
			->from($db->quoteName('#__guru_order'))
			->where($db->quoteName('id') . ' = ' . $db->quote($order_id));
		$db->setQuery($query);
		$order_details = $db->loadAssocList();

		$out['customer_id'] = $order_details["0"]["userid"];

		$gross_amount = $order_details["0"]["amount"];

		if($order_details["0"]["amount_paid"] != -1){
			$gross_amount = $order_details["0"]["amount_paid"];
		}
		$out['price'] = $gross_amount;

		return $out;
	}
}