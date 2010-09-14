<?php echo $doc->method->name ?>

<?php echo $doc->description ?>

<?php if ( $doc->params): ?>
Parameters


<?php foreach ($doc->params as $param): ?>

<?php echo '$'.$param->name ?> - <?php echo $param->type?$param->type:'unknown' ?> - <?php echo ucfirst($param->description) ?> - <?php echo $param->default ?>

<?php endforeach; ?>

<?php endif ?>
