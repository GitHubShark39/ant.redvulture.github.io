<?php
namespace Ant;
/**
 * Extends templates
 */
class Inherit
{
	private $ant;

	public function __construct(Ant $ant)
	{
		$this->ant = $ant;
	}

	/**
	 * Get parent template
	 *
	 * @param string $view template string
	 *
	 * @return array
	 */
	public function checkNext($view)
	{
		$name = array();
		preg_match('/@extends.+?\)/', $view, $name);

		if (!$name) {
			return false;
		}

		$name = Helper::clean(array('@extends', '(', ')', '"', "'"), $name[0]);

		$path = $this->ant->settings('view') . '/'  . Helper::realPath($name) . '.' . $this->ant->settings('extension');

		if (false == file_exists($path)) {
			throw new Exception(
				sprintf('Template file not found at %s', $path)
			);
		}

		$io = IO::init()->in($path);
		$nextview = $io->get();
		$io->out();

		return array(
			'path' => $path,
			'view' => $nextview
		);
	}

	/**
	 * Get templates chain
	 *
	 * @param string $view
	 * @param string $path
	 *
	 * @return array
	 */
	public function resolveChain($view, $path)
	{
		$view = preg_replace_callback('/@skip.+?@endskip/ms', '\Ant\Parser::skip', $view);
		$view = preg_replace_callback('/@php.+?@endphp/ms', '\Ant\Parser::skip', $view);

		$next = array(
			'path' => $path,
			'view' => $view
		);

		$chain = array($next['view']);
		$checks = array();

		while (true) {
			$next = $this->checkNext($next['view']);

			if (false === $next) {
				break;
			} else {
				$next['view'] = preg_replace_callback('/@skip.+?@endskip/ms', '\Ant\Parser::skip', $next['view']);
				$next['view'] = preg_replace_callback('/@php.+?@endphp/ms', '\Ant\Parser::skip', $next['view']);

				$chain[] = $next['view'];
			}

			$checks[] = $next['path'];
		}

		if (null !== $path) {
			Ant::getCache()->chain($path, $checks);
		}

		return array_reverse($chain);
	}

	/**
	 * Clear tags
	 *
	 * @param string $view teplate string
	 *
	 * @return string
	 */
	public function clear($view)
	{
		return preg_replace(
			'/@(rewrite|append|prepend|endblock)/',
			'',
			preg_replace('/@(block|inject).+?\)/', '', $view)
		);
	}

	/**
	 * Extends logic function
	 *
	 * @param string $view
	 * @param string $path
	 *
	 * @return string
	 */
	public function extend($view, $path)
	{
		$chain = $this->resolveChain($view, $path);

		$view = array_shift($chain);

		foreach ($chain as $item) {
			$injects = array();
			preg_match_all('/@inject.*?@(rewrite|append|prepend)/ms', $item, $injects);

			$map = array();
			if (isset($injects[0])) {
				foreach ($injects[0] as $k => $s) {
					$m = array();
					preg_match('/@inject.+?\)/', $s, $m);
					$name = Helper::clean(array('@inject', '(', ')', '"', '\''), $m[0]);

					$map[] = array(
						$name,
						trim($this->clear($s)),
						$injects[1][$k]
					);
				}
			}

			foreach ($map as $key=>$value) {
				$view = preg_replace_callback(
					'/@block\s*?\(\s*?(\'|")' . $value[0] . '(\'|")\s*?\).*?@endblock/ms',
					function ($e) use ($value) {
						switch ($value[2]) {
							case 'prepend':
								return '@block(\'' . $value[0] . '\')' . $value[1] . Inherit::clear($e[0]) . '@endblock';
							break;

							case 'append':
								return '@block(\'' . $value[0] . '\')' . Inherit::clear($e[0]) . $value[1] . '@endblock';
							break;

							case 'rewrite':
								return '@block(\'' . $value[0] . '\')' . $value[1] . '@endblock';
							break;
						}
					},
					$view
				);
			}
		}

		return $this->clear($view);
	}
}
?>