<?php namespace Collective\Html;

use Illuminate\Support\ServiceProvider;

class HtmlServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerHtmlBuilder();

		$this->registerFormBuilder();
		
		$this->registerBootstrapBuilder();

		$this->app->alias('html', 'Collective\Html\HtmlBuilder');
		$this->app->alias('form', 'Collective\Html\FormBuilder');
		$this->app->alias('bootstrap', 'Collective\Html\BootstrapBuilder');
	}

	/**
	 * Register the HTML builder instance.
	 *
	 * @return void
	 */
	protected function registerHtmlBuilder()
	{
		$this->app->bindShared('html', function($app)
		{
			return new HtmlBuilder($app['url']);
		});
	}

	/**
	 * Register the form builder instance.
	 *
	 * @return void
	 */
	protected function registerFormBuilder()
	{
		$this->app->bindShared('form', function($app)
		{
			$form = new FormBuilder($app['html'], $app['url'], $app['session.store']->getToken());

			return $form->setSessionStore($app['session.store']);
		});
	}

	/**
	 * Register the bootstrap builder instance.
	 *
	 * @return void
	 */
	protected function registerBootstrapBuilder()
	{
		$this->app->bindShared('bootstrap', function($app)
		{
			$form = new BootstrapBuilder($app['html'], $app['url'], $app['session.store']->getToken());

			return $form->setSessionStore($app['session.store']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('html', 'form', 'bootstrap');
	}

}
