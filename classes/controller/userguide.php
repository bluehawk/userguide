<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana user guide and api browser.
 *
 * @package    Userguide
 * @author     Kohana Team
 */
class Controller_Userguide extends Controller_Template {

	public $template = 'userguide/template';

	// Routes
	protected $media;
	protected $api;
	protected $guide;

	public function before()
	{
		if ($this->request->action === 'media')
		{
			// Do not template media files
			$this->auto_render = FALSE;
		}
		else
		{
			// Disable eAccelerator, it messes with	the ReflectionClass->getDocComments() calls
            ini_set('eaccelerator.enable',0);

			// Grab the necessary routes
			$this->media = Route::get('docs/media');
			$this->guide = Route::get('docs/guide');

			if (isset($_GET['lang']))
			{
				$lang = $_GET['lang'];

				// Load the accepted language list
				$translations = array_keys(Kohana::message('userguide', 'translations'));

				if (in_array($lang, $translations))
				{
					// Set the language cookie
					Cookie::set('userguide_language', $lang, Date::YEAR);
				}

				// Reload the page
				$this->request->redirect($this->request->uri);
			}

			// Set the translation language
			I18n::$lang = Cookie::get('userguide_language', Kohana::config('userguide')->lang);

			// Use customized Markdown parser
			define('MARKDOWN_PARSER_CLASS', 'Kodoc_Markdown');

			// Load Markdown support
			require Kohana::find_file('vendor', 'markdown/markdown');

			// Set the base URL for links and images
			Kodoc_Markdown::$base_url  = preg_replace('#//#','/',URL::site($this->guide->uri(array('module'=>$this->request->param('module')))).'/');
			Kodoc_Markdown::$image_url = preg_replace('#//#','/',URL::site($this->media->uri(array('file'=>$this->request->param('module')))).'/');
		}

		parent::before();
	}
	
	// List all modules that have userguides
	public function index()
	{
		$this->template->title = "Userguide";
		$this->template->breadcrumb = array('User Guide');
		$this->template->content = View::factory('userguide/index',array('modules'=>Kohana::config('userguide.userguide')));
		$this->template->menu = View::factory('userguide/menu',array('modules'=>Kohana::config('userguide.userguide')));
	}
	
	// Display an error if a page isn't found
	public function error($message)
	{
		$this->request->status = 404;
		$this->template->title = "Userguide - Error";
		$this->template->content = View::factory('userguide/error',array('message'=>$message));
		
		// If we are in a module and that module has a menu, show that, otherwise use the index page menu
		if ($module = $this->request->param('module') AND $config = Kohana::config("userguide.userguide.$module"))
		{
			$menu = $this->file($config['menu']);
			$this->template->menu = Markdown(file_get_contents($menu));
			$this->template->breadcrumb = array(
				$this->guide->uri() => 'User Guide',
				$this->guide->uri().'/'.$module => $config['name'],
				'Error');
		}
		else
		{
			$this->template->menu = View::factory('userguide/menu',array('modules'=>Kohana::config('userguide.userguide')));
			$this->template->breadcrumb = array($this->guide->uri() => 'User Guide','Error');
		}
	}

	public function action_docs()
	{
		$module = $this->request->param('module');
		$page = $module.'/'.$this->request->param('page');
		
		// Trim trailing slashes, to ensure breadcrumbs work
		$page = preg_replace('/\/+$/','',$page);

		// If no module/page specified, show the index page, listing the modules
		if ( ! $page)
		{
			return $this->index();
		}

		// Find the file for this page
		$file = $this->file($page);

		// If the file wasn't found, show the error page
		if ( ! $file)
		{
			return $this->error('User guide page not found.');
		}

		// Set the page title
		$this->template->title = $this->title($page);

		// Parse the page contents into the template
		$this->template->content = Markdown(file_get_contents($file));
		
		// Find this modules menu file and send it to the template
		$menu = $this->file(Kohana::config('userguide.userguide.'.$module.'.menu'));
		$this->template->menu = Markdown(file_get_contents($menu));

		// Bind the breadcrumb
		$this->template->bind('breadcrumb', $breadcrumb);

		// Begin building the breadcrumbs backwards
		$breadcrumb = array();
		
		// Add the page name
		$breadcrumb[] = $this->template->title;
		
		// Find all the parents
		$last = $page;
		$current = null;
		while ($last !== $current = preg_replace('~/[^/]+$~','',$last))
		{
			$breadcrumb[$this->guide->uri().'/'.$current] = $this->title($current);
			$last = $current;
		}
		
		// Add the userguide root link
		$breadcrumb[$this->guide->uri()] = __('User Guide');
		
		// Now reverse the array
		$breadcrumb = array_reverse($breadcrumb);
	}

	public function action_api()
	{
		// Get the class from the request
		$class = $this->request->param('class');

		if ($class)
		{
			$_class = Kodoc::factory($class);
			
			$this->template->title = $class;

			$this->template->content = View::factory('userguide/api/class')
				->set('doc', $_class)
				->set('route', $this->request->route);

			$this->template->menu = Kodoc::menu().View::factory('userguide/api/menu',array('doc'=>$_class));
		}
		else
		{
			$this->template->title = __('Table of Contents');

			$this->template->content = View::factory('userguide/api/toc')
				->set('classes', Kodoc::class_methods())
				->set('route', $this->request->route);

			$this->template->menu = Kodoc::menu();
		}

		// Attach the menu to the template
		

		// Bind the breadcrumb
		$this->template->bind('breadcrumb', $breadcrumb);

		// Get the docs URI
		$guide = Route::get('docs/guide');

		// Add the breadcrumb
		$breadcrumb = array();
		$breadcrumb[$this->guide->uri(array('page' => NULL))] = __('User Guide');
		$breadcrumb[$this->request->route->uri()] = 'API Reference';
		$breadcrumb[] = $this->template->title;
	}

	public function action_media()
	{
		// Get the file path from the request
		$file = $this->request->param('file');
		
		// Find the file extension
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		
		// Remove the extension from the filename
		$file = substr($file, 0, -(strlen($ext) + 1));
		
		// Find the file
		$file = Kohana::find_file('media', $file, $ext);
		
		// If it wasn't found, send a 404
		if ( ! $file )
		{
			// Return a 404 status
			$this->request->status = 404;
			return;
		}
 
		// If the browser sent a "if modified since" header, and the file hasn't changed, send a 304
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) AND strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($file))
		{
			$this->request->status = 304;
			return;
		}
 
		// Send the file content as the response, and send some basic headers
		$this->request->response = file_get_contents($file);
		$this->request->headers['Content-Type'] = File::mime_by_ext($ext);
		$this->request->headers['Content-Length'] = filesize($file);
 
		// Tell browsers to cache the file for an hour. Chrome especially seems to not want to cache things
		$cachefor = 3600;
		$this->request->headers['Cache-Control'] = 'max-age='.$cachefor.', must-revalidate, public';
		$this->request->headers['Expires'] = gmdate('D, d M Y H:i:s',time() + $cachefor).'GMT';
		$this->request->headers['Last-Modified'] = gmdate('D, d M Y H:i:s',filemtime($file)).' GMT';
	}

	public function after()
	{
		if ($this->auto_render)
		{
			// Get the media route
			$media = Route::get('docs/media');

			// Add styles
			$this->template->styles = array(
				$media->uri(array('file' => 'css/print.css'))  => 'print',
				$media->uri(array('file' => 'css/screen.css')) => 'screen',
				$media->uri(array('file' => 'css/kodoc.css'))  => 'screen',
				$media->uri(array('file' => 'css/topline.css')) => 'screen',
				$media->uri(array('file' => 'css/shCore.css')) => 'screen',
				$media->uri(array('file' => 'css/shThemeDefault.css')) => 'screen',
			);

			// Add scripts
			$this->template->scripts = array(
				$media->uri(array('file' => 'js/jquery-1.3.2.min.js')),
				$media->uri(array('file' => 'js/kodoc.js')),
				$media->uri(array('file' => 'js/shCore.js')),
				$media->uri(array('file' => 'js/shBrushPhp.js')),
			);

			// Add languages
			$this->template->translations = Kohana::message('userguide', 'translations');
		}

		return parent::after();
	}

	/**
	 * Find a userguide page
	 * @param   string   the url of the page
	 * @return  string   the name of the file
	 */
	public function file($page)
	{
		if ( ! ($file = Kohana::find_file('guide', I18n::$lang.'/'.$page, 'md')))
		{
			// Use the default file
			$file = Kohana::find_file('guide', $page, 'md');
		}
		
		// If no file has been found, try to see if $page is a folder with an index file
		if (empty($file) OR ! $file)
		{
			$file = Kohana::find_file('guide',$page.'/index','md');
		}

		return $file;
	}

	/**
	 * Find the title of a page in the menu file by looking for the url. Assuming we are looking for "url" and the following is in the menu file: [Name](url) it will return "Name".
	 * @param  string   the url to find the title of
	 * @return string   the title of the page
	 */
	public function title($page)
	{
		$markdown = $this->_get_all_menu_markdown();
		
		if (preg_match('~\[([^\]]+)\]\('.preg_quote($page).'\)~mu', $markdown, $matches))
		{
			// Found a title for this link
			return $matches[1];
		}
		
		return $page;
	}
	
	/**
	 * Get all the menu markdown merged together, and make it static so we only have to get it once
	 * @return  string   the combined markdown of all the menus
	 */
	protected function _get_all_menu_markdown()
	{
		// Only do this once per request...
		static $markdown = '';
		
		if (empty($markdown))
		{
			// Get core menu items
			$file = $this->file('menu');
	
			if ($file AND $text = file_get_contents($file))
			{
				$markdown .= $text;
			}
			
			// Look in module specific files
			foreach(Kohana::config('userguide.userguide') as $module => $options)
			{
				if ($file = Kohana::find_file('guide',$options['menu'],'md') AND $text = file_get_contents($file))
				{
					// Concatenate markdown to produce one string containing all menu items
					$markdown .="\n".$text;
				}
			}
		}
		
		return $markdown;
	}

} // End Userguide
