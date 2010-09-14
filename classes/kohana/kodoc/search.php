<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Class documentation generator.
 *
 * @package    Kohana/Userguide
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Kodoc_Search  {

	private static $_index;
	
	 // Hold an instance of the class
    private static $instance;
    
    // A private constructor; prevents direct creation of object
    private function __construct() 
    {
		// Add vendor to the include path so Lucene can find its crap.
		$path = MODPATH . 'userguide/vendor';
		set_include_path(get_include_path() . PATH_SEPARATOR . $path);
		
		include(Kohana::find_file('vendor','Zend/Search/Lucene'));
		
		try
		{
			self::$_index = Zend_Search_Lucene::open(APPPATH.'data/userguide');
		}
		catch (Zend_Search_Lucene_Exception $e)
		{
			self::$_index = Zend_Search_Lucene::create(APPPATH.'data/userguide');
		}
    }

    public static function instance() 
    {
        if ( ! isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }
    
    // Example method
    public function index()
    {
		return self::$_index;
    }

    // Prevent users to clone the instance
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }
	

} 
