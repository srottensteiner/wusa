<?php
/**
 * This class writes a PHP array to a file.
 * Example usage:
 * 		$config = array();
 * 		$config['a']['b'] 		  = 'c';
 *		$config['a1']['b1']['c1'] = 'd';
 *
 *		$writer = new Tomato_Config_Writer_Php();
 *		$writer->setArrayName('options');
 *		$writer->write($fileName, new Zend_Config($config));
 * Then the $fileName file will have content as follow:
 * 		<?php
 * 		$options['a']['b'] 		   = 'c';
 * 		$options['a1']['b1']['c1'] = 'd';
 * 		return $options;
 * 
 * This class is used to save the most important settings of the app to a PHP file
 * which you can find in config directory.
 */
class Tomato_Config_Writer_Php extends Zend_Config_Writer_Ini
{
	/**
	 * @var string
	 */
	private $_arrayName = 'config';
	
	/**
	 * @param string $name
	 * @return Tomato_Config_Writer_Php
	 */
	public function setArrayName($name = 'config')
	{
		$this->_arrayName = $name;
		return $this;
	}
	
	/**
	 * @see Zend_Config_Writer_Ini::render()
	 */
	public function render()
	{
		// Write the <?php to the top of file
		$content  = "<?php\n"
				 . "$" . $this->_arrayName . " = array();\n";
		
		$content .= $this->_addBranch($this->_config);
		
		// Write the return at the end of file
		$content .= "\n"
				 . "return $" . $this->_arrayName . ";";
		
		return $content;
	}
	
	/**
	 * @see Zend_Config_Writer_Ini::_addBranch()
	 */
	protected function _addBranch(Zend_Config $config, $parents = array())
	{
		$content = '';

		foreach ($config as $key => $value) {
			$group = array_merge($parents, array($key));

			if ($value instanceof Zend_Config) {
				$content .= $this->_addBranch($value, $group);
			} else {
				$content .= "$" . $this->_arrayName . "['" . implode("']['", $group) . "']"
						 .  ' = '
						 .  $this->_prepareValue($value)
						 .  ";\n";
			}
		}

		return $content;
	}
}
