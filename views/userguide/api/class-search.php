
<?php echo $doc->modifiers, $doc->class->name ?>
	
<?php echo $doc->description ?>

<?php if ($doc->tags): ?>

<?php foreach ($doc->tags as $name => $set): ?>
<?php echo $name ?> - <?php echo implode(',',$set) . "\n"; ?>
<?php endforeach ?>

<?php endif; ?>

<?php if ($doc->constants): ?>

<?php echo __('Constants'); ?>

<?php foreach ($doc->constants as $name => $value): ?>
<?php echo $name ?>
<?php endforeach; ?>

<?php endif ?>

<?php if ($properties = $doc->properties()): ?>
<?php echo __('Properties'); ?>

<?php foreach ($properties as $prop): ?>
<?php echo $prop->modifiers ?> <?php echo $prop->type ?> $<?php echo $prop->property->name ?> - <?php echo $prop->description ?>

<?php endforeach ?>

<?php endif ?>

<?php if ($methods = $doc->methods()): ?>
<?php echo __('Methods'); ?>

<?php foreach ($methods as $method): ?>
<?php echo View::factory('userguide/api/method-search')->set('doc', $method)->set('route', $route) ?>
<?php endforeach ?>

<?php endif ?>
