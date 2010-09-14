<h1>Search</h1>

<?php foreach ($hits as $hit): ?>

<h3><?php echo $hit->title;?></h3>

<p><?php echo $hit->path; ?></p>

<?php echo kohana::debug($hit->getDocument()->getFieldNames()); ?>
<?php endforeach; ?>