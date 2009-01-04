<?php 

use_helper('Form');

function quick_create_tag($type, $field = false, $options = false)
{
  if ($field === false)
  {
    $field = sfInflector::underscore($type) . "_id";
  }
  $options = _parse_attributes($options);
  if (!isset($options['label']))
  {
    $options['label'] = __('Add New ' . 
      sfInflector::humanize(
        sfInflector::underscore($type)));
  }
  $options['name'] = "quick-create-$field";
  if (!isset($options['class']))
  {
    $options['class'] = "quick-create";
  }
  if (!isset($options['id']))
  {
    $options['id'] = "quick-create-$field";
  }

  return submit_tag($options['label'], $options);
}

// "Can't you make Propel cough up the type of the field
// automatically?" I'd love to. But this requires loading
// all of Propel's database maps, which is something we shouldn't
// do at runtime.

function select_or_quick_create_tag(
  $object, $type, $field, $separator = false, 
  $optionsSelect = false, $optionsButton = false)
{
  if ($separator === false)
  {
    $separator = " or ";
  }
  if ($field === false)
  {
    $field = sfInflector::underscore($type) . "_id";
  }
  $optionsSelect = _parse_attributes($optionsSelect);
  $optionsSelect['related_class'] = $type;
  $optionsSelect['control_name'] = sfInflector::underscore(get_class($object)) . "[" . $field . "]";
  $tableMap = $object->getPeer()->getTableMap();
  $columnMap = $tableMap->getColumn($field);
  if (!isset($optionsSelect['include_blank']))
  {
    $optionsSelect['include_blank'] = !($columnMap->isNotNull());
  }
  // I am leaning on PHP's case insensitivity a little here
  $result = object_select_tag($object, "get" . sfInflector::camelize($field), $optionsSelect); 
  $result .= $separator;
  $result .= quick_create_tag($type, $field, $optionsButton);
  return $result;
} 

