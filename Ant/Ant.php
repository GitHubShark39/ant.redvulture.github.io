<?php
namespace Ant;

require_once __DIR__ . '/Bootstrap.php';

/**
 * Main class
 */
class Ant
{
	const VERSION = 1.0;
	private static $cache;
	private static $settings;
	private static $plugin;
	private $render;
	private $event;

	/**
	 * New instance
	 */
	public function __construct()
	{
		$this->event  = new Event;
		$this->render = new Render($this);
	}

	/**
	 * Return new instance
	 *
	 * @return Ant\Ant
	*/
	public static function init()
	{
		return new static();
	}

	/**
	 * Setup engine
	 *
	 * @param array $settings settings
	 *
	 * @return Ant\Ant
	 */
	public function setup(array $settings)
	{
		self::$settings = new Settings($settings);
		self::$cache    = new Cache($settings['cache']);
		self::$plugin   = new Plugin($this);

		Fn::apply($this);

		return $this;
	}

	/**
	 * Return settings hash
	 *
	 * @return string
	 */
	public static function snapshot()
	{
		$glue = (
			md5(print_r(self::$settings, true)) .
			md5(print_r(self::$plugin, true))
		);

		return md5($glue);
	}

	/**
	 * Magic __get
	 *
	 * @param string $key property
	 *
	 * @return mixed
	 */
	public function __get($key)
	{
		switch ($key) {
			case 'plugin':
				return self::$plugin;

			case 'event':
				return $this->event;

			case 'cache':
				return self::$cache;

			case 'settings':
				return self::$settings;

			default:
				throw new \Exception(
					sprintf('Undefined property %s', $key)
				);
		}
	}

	/**
	 * Magic __callStatic
	 *
	 * @param string $name name
	 * @param array  $args arguments
	 *
	 * @return mixed
	 */
	public static function __callStatic($name, $args)
	{
		if (method_exists('\Ant\Fn', $name) or Fn::isShared($name)) {
			return call_user_func_array('\Ant\Fn::' . $name, $args);
		} else {
			throw new Exception(
				sprintf('Undeclared method \\Ant\\Ant::%s', $name)
			);
		}
	}

	/**
	 * Magic __call
	 *
	 * @param string $name name
	 * @param array  $args arguments
	 *
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		$map = array(
			'settings'           => array(false, array(self::$settings, 'get')),
			'on'                 => array(false, array($this->event, 'on')),
			'off'                => array(true, array($this->event, 'off')),
			'bind'               => array(false, array($this->event, 'bind')),
			'unbind'             => array(true, array($this->event, 'unbind')),
			'fire'               => array(true, array($this->event, 'fire')),
			'preventParentEvent' => array(true, array($this->event, 'preventParentEvent')),
			'rule'               => array(true, '\Ant\Parser::rule'),
			'share'              => array(true, '\Ant\Fn::share'),
			'register'           => array(true, array(self::$plugin, 'register')),
			'activate'           => array(true, array(self::$plugin, 'activate')),
			'isActivated'        => array(false, array(self::$plugin, 'isActivated')),
			'deactivate'         => array(true, array(self::$plugin, 'deactivate')),
			'unregister'         => array(true, array(self::$plugin, 'unregister')),
			'fromFile'           => array(true, array($this->render, 'fromFile')),
			'fromString'         => array(true, array($this->render, 'fromString')),
			'has'                => array(false, array($this->render, 'has')),
			'logic'              => array(true, array($this->render, 'logic')),
			'get'                => array(true, array($this->render, 'get')),
			'assign'             => array(true, array($this->render, 'assign')),
			'draw'               => array(false, array($this->render, 'draw'))
		);

		if (array_key_exists($name, $map)) {
			$callres = call_user_func_array($map[$name][1], $args);
			return (true === $map[$name][0] ? $this : $callres);
		} else if (method_exists('\Ant\Fn', $name) or Fn::isShared($name)) {
			return call_user_func_array('\Ant\Fn::' . $name, $args);
		} else {
			throw new Exception(
				sprintf('Undeclared method \\Ant\\Ant::%s', $name)
			);
		}
	}

	/**
	 * Return cache instance
	 *
	 * @return Ant\Cache
	 */
	public static function getCache()
	{
		return self::$cache;
	}

	/**
	 * View helper
	 *
	 * @return string
	 */
	public static function view()
	{
		$args = func_get_args();

		switch(count($args)){
			default:
				return;

			case 1:
				list($view) = $args;

				return static::init()->get($view)->draw();

			case 2:
				$inst = static::init();

				if (is_array($args[1])) {
					list($view, $assign) = $args;
					$inst = $inst->get($view)->assign($assign);
				} else {
					list($view, $logic) = $args;
					$inst = $inst->get($view)->logic($logic);
				}

				return $inst->draw();

			case 3:
				list($view, $assign, $logic) = $args;

				return static::init()
					->get($view)
					->assign($assign)
					->logic($logic)
					->draw();
		}
	}

	public function __toString()
	{
		return '[ ANT- Awesome New Templates ]';
	}
}
?>