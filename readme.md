# Forms & HTML

[![License](https://poser.pugx.org/leaphly/cart-bundle/license.svg)]
	
It is fork of popular Laravel Collective for Laravel five.

Some examples:
```
{!! Bootstrap::open(array('url' => 'foo/bar', 'formType' => 'horizontal')) !!}
{!! Bootstrap::text('test',null,array('label' => 'Laravel')) !!}
{!! Bootstrap::number('test1',null,array('label' => 'Laravel')) !!}
{!! Bootstrap::checkbox('name', 'value') !!}
{!! Bootstrap::beginFormGroup() !!}
{!! Bootstrap::label('namea', 'Label') !!}
{!! Bootstrap::beginHorizontalGroup() !!}
{!! Bootstrap::radio('namea', 'value', null, array('label' => 'Leopard','display' => 'inline', 'disabled' => 'disabled')) !!}
{!! Bootstrap::radio('namea', 'value', null, array('label' => 'Leopard','display' => 'inline', 'disabled' => 'disabled')) !!}
{!! Bootstrap::endHorizontalGroup() !!}
{!! Bootstrap::endFormGroup() !!}
{!! Bootstrap::select('animal', array('Cats' => array('leopard' => 'Leopard'),'Dogs' => array('spaniel' => 'Spaniel') )) !!}
{!! Bootstrap::select('animal', array('leopard' => 'Leopard','spaniel' => 'Spaniel' )) !!}
{!! Bootstrap::textarea("test") !!}
{!! Bootstrap::submit("Test") !!}
{!! Bootstrap::button("Test","primary", array('disabled' => 'disabled')) !!}
{!! Bootstrap::close() !!}
```
Later I will add detailed comments and documentation.
If you want to contribute, welcome ;-)

Official documentation for Forms & Html for The Laravel Framework can be found at the [LaravelCollective](http://laravelcollective.com) website.
