<?php

if ($modifiers = $class->getModifiers())
{
	echo implode(' ', Reflection::getModifierNames($modifiers));
}

echo $class->name;

echo "\n\n";

$parent = $class;

do
{
	if ($comment = $parent->getDocComment())
	{
		// Found a description for this class
		break;
	}
}
while ($parent = $parent->getParentClass());

// Normalize all new lines to \n
$comment = str_replace(array("\r\n", "\n"), "\n", $comment);

// Remove the phpdoc open/close tags and split
$comment = array_slice(explode("\n", $comment), 1, -1);

foreach ($comment as $i => $line)
{
	// Remove all leading whitespace
	$line = preg_replace('/^\s*\* ?/m', '', $line);

	// Search this line for a tag
	if (preg_match('/^@(\S+)(?:\s*(.+))?$/', $line, $matches))
	{
		// This is a tag line
		unset($comment[$i]);

	}
	else
	{
		// Overwrite the comment line
		$comment[$i] = (string) $line;
	}
}

// Concat the comment lines back to a block of text
$comment = trim(implode("\n", $comment));

echo $comment;