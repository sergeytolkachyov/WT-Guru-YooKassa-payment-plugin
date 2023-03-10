<?php

/**
 * @package GURU
 * @subpackage payment
 * @author Sergey Tolkachyov, <https://web-tolk.ru>
 * @copyright Copyright (C) 2023 Sergey Tolkachyov. All rights reserved.
 * @license GNU GPL 2.0 or higher
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die();

$response = $displayData['response'];
Factory::getApplication()->enqueueMessage($response->getCode().' '.$response->getMessage(),'error');

