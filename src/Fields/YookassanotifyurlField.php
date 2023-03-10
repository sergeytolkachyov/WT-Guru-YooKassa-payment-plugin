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

use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use \Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class YookassanotifyurlField extends NoteField
{

	protected $type = 'Yookassanotifyurl';

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
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->addInlineStyle("
			.plugin-info-img-svg:hover * {
				cursor:pointer;
			}
		");

		$wt_plugin_info = simplexml_load_file(JPATH_SITE."/plugins/".$folder."/".$element."/".$element.".xml");

		$notify_url = Uri::root()."index.php?option=com_guru&controller=guruBuy&task=payment&processor=wtguruyookassa";
		return $html = '</div>
		<div class="alert alert-info">
		
		 <p><code class="fs-4">'.$notify_url.'</code></p><p>'.Text::_('PLG_WTGURUYOOKASSA_NOTYFY_URL').'</p>
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