<?php namespace Collective\Html;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Collective\Html\BootstrapBuilder
 */
class BootstrapFacade extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'bootstrap'; }

}