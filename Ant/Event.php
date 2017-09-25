<?php
namespace Ant;

class Event
{
	private $preventParent = array();
	private static $globalEvents = array();
	private $localEvents = array();

	/**
	 * Bind global event
	 *
	 * @param string    $event event name
	 * @param mixed     $call  callback
	 * @param reference $index variable
	 *
	 * @return integer event id
	 */
	public function bind($event, $call)
	{
		self::$globalEvents[$event][] = $call;

		return key(array_slice(self::$globalEvents[$event], -1, 1, TRUE));
	}

	/**
	 * Unbind global event
	 *
	 * @param string  $event event name
	 * @param integer $index variable
	 *
	 * @return Ant\Event
	 */
	public function unbind($event, $index)
	{
		unset(self::$globalEvents[$event][$index]);

		return $this;
	}

	/**
	 * Bind local event
	 *
	 * @param string    $event event name
	 * @param mixed     $call  callback
	 * @param reference $index variable
	 *
	 * @return integer event id
	 */
	public function on($event, $call)
	{
		$this->localEvents[$event][] = $call;

		return key(array_slice($this->localEvents[$event], -1, 1, TRUE));
	}

	/**
	 * Unbind local event
	 *
	 * @param string  $event event name
	 * @param integer $index variable
	 *
	 * @return Ant\Event
	 */
	public function off($event, $index)
	{
		unset($this->localEvents[$event][$index]);

		return $this;
	}

	/**
	 * Cancel event bubble
	 *
	 * @param string $prevent event name
	 *
	 * @return Ant\Event
	 */
	public function preventParentEvent($prevent)
	{
		$this->preventParent[] = $prevent;

		return $this;
	}

	/**
	 * Trigger event
	 *
	 * @param string $event  event name
	 * @param string $string template
	 *
	 * @return string
	 */
	public function fire($event, $string)
	{
		$queue = array();

		if (isset($this->localEvents[$event])) {
			$queue = array_merge($queue, $this->localEvents[$event]);
		}

		if (false == in_array($event, $this->preventParent)) {
			if (isset(self::$globalEvents[$event])) {
				$queue = array_merge($queue, self::$globalEvents[$event]);
			}
		}

		foreach ($queue as $call) {
			$string = call_user_func($call, $string);
		}

		return $string;
	}
}
?>