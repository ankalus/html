# Forms & HTML

[![License](https://poser.pugx.org/LaravelCollective/html/license.svg)](https://packagist.org/packages/laravelcollective/html)

It is fork of popular Laravel Collective for Laravel five.

Some examples:
```php
{!! Bootstrap::open(array('url' => 'foo/bar', 'formType' => 'horizontal')) !!}
{!! Bootstrap::text('test',null,array('label' => 'Laravel')) !!}
{!! Bootstrap::number('test1',null,array('label' => 'Laravel')) !!}
{!! Bootstrap::password('test1',array('label' => 'Laravel')) !!}
{!! Bootstrap::checkbox('name', 'value') !!}
{!! Bootstrap::checkboxes('namea', array('value1' => 'Test1','value2' => 'Test2'), array('value1','value2'), array('label' => 'Leopard')) !!}
{!! Bootstrap::radios('namea', array('value1' => 'Test1','value2' => 'Test2'), 'value2', array('label' => 'Leopard')) !!}
{!! Bootstrap::select('animal', array('Cats' => array('leopard' => 'Leopard'),'Dogs' => array('spaniel' => 'Spaniel') )) !!}
{!! Bootstrap::select('animal', array('leopard' => 'Leopard','spaniel' => 'Spaniel' )) !!}
{!! Bootstrap::textarea("test") !!}
{!! Bootstrap::submit("Test") !!}
{!! Bootstrap::button("Test", array('pattern' => 'primary', 'disabled' => 'disabled')) !!}
{!! Bootstrap::close() !!}
```
Later I will add detailed comments and documentation.
If you want to contribute, welcome ;-)

Official documentation for Forms & Html for The Laravel Framework can be found at the [LaravelCollective](http://laravelcollective.com) website.
