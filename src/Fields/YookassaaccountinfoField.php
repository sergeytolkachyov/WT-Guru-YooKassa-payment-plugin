<?php
/**
 * @package     WebTolk plugin info field
 * @version     1.1.0
 * @Author 		Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 		1.0.0
 */

namespace Joomla\Plugin\Gurupayment\WTGuruYookassa\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use YooKassa\Client;

class YookassaaccountinfoField extends NoteField
{

	protected $type = 'Yookassaaccountinfo';

	/**
	 * Method to get the field input markup for a spacer.
	 * The spacer does not have accept input.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		return ' ';
	}

	/**
	 * @return  string  The field label markup.
	 *
	 * @since   1.7.0
	 */
	protected function getLabel()
	{
		$data = $this->form->getData();
		$element = 	$data->get('element');
		$folder = 	$data->get('folder');
		$wt_plugin_info = simplexml_load_file(JPATH_SITE."/plugins/".$folder."/".$element."/".$element.".xml");
		/**
		 * @var $client \YooKassa\Client
		 */
		$plugin = PluginHelper::getPlugin($folder,$element);
		if(count((array)$plugin) == 0){
			return;
		}
		$plugin_params = new Registry($plugin->params);
		if(empty($plugin_params->get('shopId')) || empty($plugin_params->get('secretKey')))
		{
			return '';
		}
		$shop_id = trim($plugin_params->get('shopId'));
		$secret_key = trim($plugin_params->get('secretKey'));
		$client = new Client();
		$client->setAuth($shop_id, $secret_key);
		$userAgent = $client->getApiClient()->getUserAgent();
		$userAgent->setFramework('Joomla', (new Version())->getShortVersion());
		$userAgent->setCms('Joomla', (new Version())->getShortVersion());
		$userAgent->setModule('Joomla iGuru YooKassa', $wt_plugin_info->version);

		try {
			$response = $client->me();
		} catch (\Exception $e) {
			$response = $e;
		}
		if($response instanceof \Exception){
			Factory::getApplication()->enqueueMessage('<strong>YooKassa API: </strong> '.$response->getMessage(),'error');
			return '';
		}

		$account_status = ($response['status'] == 'enabled') ? Text::_('PLG_WTGURUYOOKASSA_ACCOUNT_INFO_ACCOUNT_STATUS_ENABLED') : Text::_('PLG_WTGURUYOOKASSA_ACCOUNT_INFO_ACCOUNT_STATUS_ENABLED');
		$account_test = ($response['test'] == 1) ? Text::_('JYES') : Text::_('JNO');
		$payment_methods = implode(', ',$response['payment_methods']);
		return $html = '</div>
		<div class="card shadow-sm w-100 p-0 bg-light">
			<div class="card-body">
				'.sprintf(Text::_('PLG_WTGURUYOOKASSA_ACCOUNT_INFO_FIELD_SUCCESSFULLY_CONNECTED'),

					$response['account_id'],
					$account_status,
					$account_test,
					$payment_methods
				).'
			</div>
		</div><div>
		';

	}

	/**
	 * Method to get the field title.
	 *
	 * @return  string  The field title.
	 *
	 * @since   1.7.0
	 */
	protected function getTitle()
	{
		return $this->getLabel();
	}

}

?>