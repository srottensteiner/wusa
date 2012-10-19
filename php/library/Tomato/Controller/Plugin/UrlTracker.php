<?php
/**
 * TomatoCMS
 * 
 * LICENSE
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE Version 2 
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-2.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@tomatocms.com so we can send you a copy immediately.
 * 
 * @copyright	Copyright (c) 2009-2010 TIG Corporation (http://www.tig.vn)
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GNU GENERAL PUBLIC LICENSE Version 2
 * @version 	$Id: UrlTracker.php 5791 2010-12-08 09:47:48Z huuphuoc $
 * @since		2.1.0
 */

/**
 * This plugin stores the current URL in session
 */
class Tomato_Controller_Plugin_UrlTracker extends Zend_Controller_Plugin_Abstract 
{
	/**
	 * Session key which is used to save the URL
	 * @var const
	 */
	const SESSION_NS = 'Tomato_Controller_Plugin_UrlTracker';
	
	/**
	 * Store the exclude actions that we don't want to store the URL
	 * @var array
	 */
	private $_exclude = array();
	
	/**
	 * @param array $exclude Exclude actions
	 * Each exclude item is a string in the format of module_controller_action
	 */
	public function __construct($exclude = array())
	{
		$this->_exclude = $exclude;
	}	

	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		/**
		 * Combine the current module, controller and action
		 */
		$mca = implode('_', array(
								$request->getModuleName(),
								$request->getControllerName(),
								$request->getActionName(),
							));
		if (!in_array($mca, $this->_exclude)) {
			$session = new Zend_Session_Namespace(self::SESSION_NS);
			$session->url   = $request->getRequestUri();
			$session->isXhr = $request->isXmlHttpRequest();
		}
	}
}
