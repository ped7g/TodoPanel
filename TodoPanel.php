<?php
/**
 * Annotations Todo panel for Nette 1.0+. Displays all todos in APP_DIR folder
 *
 * @author Mikuláš Dítě, Peter Ped Helcmanovsky
 * @license MIT
 */

namespace Panel;
use \Nette\Debug;
use \Nette\IDebugPanel;
use \Nette\IO\SafeStream;
use \Nette\Object;
use \Nette\Templates\Template;
use \Nette\Templates\LatteFilter;

class TodoPanel extends Object implements IDebugPanel
{

	/**
	 * stores generated todos in one instance
	 * @var array|mixed
	 */
	private $todo = array();

	/** @var array any path or file containing one of the patterns to skip */
	private $ignoreMask = array();

	/** @var array */
	private $scanDirs;


	
	/** @var bool */
	public $highlight = TRUE;

	/** @var array|mixed list of highlighted words, does not affect todo getter itself */
	public $keywords = array('add', 'fix', 'improve', 'remove', 'delete');

	/** @var string */
	public $highlightBegin = '<span style="font-weight: bold;">';

	/** @var string */
	public $highlightEnd = '</span>';

	/** @var array catched patterns for todo comments */
	public $pattern = array('TODO', 'FIXME', 'FIX ME', 'FIXED', 'FIX', 'TO DO', 'PENDING', 'XXX');



	/**
	 * @param string|path $basedir
	 * @param array $ignoreMask
	 */
	public function __construct($basedir = APP_DIR, $ignoreMask = array( '/.svn/', '/sessions/', '/temp/', '/log/' ))
	{
		$this->scanDirs = array(realpath($basedir));
		$this->setSkipPatterns($ignoreMask);
	}



	/**
	 * Set files which are ignored when browsing files
	 * @param array $ignoreMask
	 */
	public function setSkipPatterns($ignoreMask)
	{
		$this->ignoreMask = array_merge( str_replace( '\\', '/', $ignoreMask ), str_replace( '/', '\\', $ignoreMask ) );
	}



	/**
	 * Renders HTML code for custom tab.
	 * @return void
	 */
	public function getTab()
	{
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAPnRFWHRDb21tZW50AENyZWF0ZWQgd2l0aCBUaGUgR0lNUAoKKGMpIDIwMDMgSmFrdWIgJ2ppbW1hYycgU3RlaW5lcicz71gAAAGtSURBVHjarZMxSBthFMd/d/feqVjiIAoFcQqVFoogjUSJdDBYaECH4Nq1OLiU2C52KKGi4uTgEhDcFRxEyeDSsQdKhdKSEhAFseEGCxFpa+7r0Fi8axM7+OC/vf/ve+//+ODftQKYiFa4ofLXDb7v/1GpVIrC8lcmuQaYLSzMcPDlhM2dXTzPo1KpAFAul7nb3clo6hEDD/t48WZ5FngdBQCwWXzH1naRarVKLBYDIB6PY9s2hUKBs69Hof4QwBjIZrOkUqlQk2VZAORyOYobq40BYMhknjI+PoGqIFKXKuoIjgrF9aYAEHERdVDRullQR2ibew73+jHGhPrt8PugKrC2RPv8FHv7e3jvPdpeTWKdHuP0D/11uhAgCMB1XXrK+7SffyORSPB4fRHr4hx7+i1uMk0QNJvAGEQVv/c+Vu2Sjpks+vM79vQcLck0qtp8hVoQoKp4gxP4vQ+wapccjEzSOpxGXEVFCCKAcIjG4Kow9mQMzWQQEZKiqApO/SIYGgMCA0svn/H5sIKpZxJ1xO60NgZ8+viB6sUPero6+J2VITLx/3+mG5TntuoX7nmiqfg2Y6EAAAAASUVORK5CYII=">' .
			'Todo (' . $this->getCount() . ')';
	}



	/**
	 * Renders HTML code for custom panel.
	 * @return void
	 */
	public function getPanel()
	{
		ob_start();
		$template = new Template(dirname(__FILE__) . '/bar.todo.panel.phtml');
		$template->registerFilter(new LatteFilter());
		$template->todos = $this->getTodo();
		$template->render();
		return $cache['output'] = ob_get_clean();
	}


	
	/**
	 * Returns panel ID.
	 * @return string
	 */
	public function getId()
	{
		return __CLASS__;
	}



	/**
	 * Registeres panel to Debug bar
	 */
	public static function register()
	{
		Debug::addPanel(new self);
	}



	/**
	 * Wrapper for generateTodo, performace booster in one instance
	 */
	private function getTodo()
	{
		if (empty($this->todo)) {
			$this->todo = $this->generateTodo();
		}
		return $this->todo;
	}



	/**
	 * Returns count of all second level elements
	 */
	private function getCount()
	{
		$count = 0;
		foreach ($this->getTodo() as $file) {
			$count += count($file);
		}
		return $count;
	}



	/**
	 * Returns array in format $filename => array($todos)
	 * @uses SafeStream
	 */
	private function generateTodo()
	{
		@SafeStream::register(); //intentionally @ (prevents multiple registration warning)
		foreach ($this->scanDirs as $dir) {
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
			$todo = array();
			foreach ($iterator as $path => $match) {
				$ignorethisone = false;
				foreach ( $this->ignoreMask as $pattern ) {
					if ( strpos( $path, $pattern ) !== false ) { $ignorethisone = true; break; }
				}
				if ( $ignorethisone ) continue;
				$relative = trim( str_replace($dir, '', $path), '/\\' );

				$handle = fopen("safe://" . $path, 'r');
				if (!$handle) {
					throw new InvalidStateException('File not readable, you should set proper priviledges to \'' . $relative . '\'');
				}

				$res = '';
				while(!feof($handle)) {
					$res .= fread($handle, filesize($path));
				}
				fclose($handle);
				
				if (count($this->pattern) === 0) {
					throw new InvalidStateException('No patterns specified for TodoPanel.');
				}
				preg_match_all('~
					(//) #line comments
						.*? #anything before todo pattern
						(' . implode('|', $this->pattern) . ') #annotation/line type
						(?P<todo>.*?) #todo content
					(\r|\n){1,2} #end line comments
					~mixs', $res, $m);
				preg_match_all('~/\*\*?(?P<content>.*?)\*/~mis', $res, $blocks);
				foreach ($blocks['content'] as $block) {
					foreach (explode("\n", $block) as $line) {
						if (preg_match('~(' . implode('|', $this->pattern) . ')(?P<content>.*?)$~mixs', $line, $p)) {
							array_push($m['todo'], trim($p['content']));
						}
					}
				}
				if (isset($m['todo']) && !empty($m['todo'])) {
					if ($this->highlight) {
						foreach ($m['todo'] as $k => $t) {
							$m['todo'][$k] = $this->highlight($t);
						}
					}
					$todo[$relative] = $m['todo'];
				}
			}
		}
		return $todo;
	}



	/**
	 * Add directory (or directories) to list.
	 * @param  string|array
	 * @return void
	 * @throws \DirectoryNotFoundException if path is not found
	 */
	public function addDirectory($path)
	{
		foreach ((array) $path as $val) {
			$real = realpath($val);
			if ($real === FALSE) {
				throw new /*\*/DirectoryNotFoundException("Directory '$val' not found.");
			}
			$this->scanDirs[] = $real;
		}
	}



	/**
	 * Highlights specified words in given string
	 * @param string $todo
	 * @return string
	 */
	private function highlight($todo)
	{
		foreach ($this->keywords as $kw) {
			$todo = str_replace($kw, $this->highlightBegin . $kw . $this->highlightEnd, $todo);
		}
		return $todo;
	}
}