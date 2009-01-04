<?php

class pkAdminQuickCreateTools
{
  // Arguments are:
  //
  // The model class we're administering, and an array like so:
  // 
  // validateEdit('Event', 
  //   array(
  //     array(
  //       "type" => "Venue"
  //     ),
  //     array(
  //       "type" => "Dj"
  //       "field" => "second_dj",
  //       "module" => "djAdmin"
  //     )
  //   ));
  //   
  // If the type is 'Venue' and the field name is venue_id,
  //  you do not have to specify the field name. Similarly,
  // if the type is 'Venue' and the the admin generator module is called
  // 'venue' (lower case), you do not have to specify the module name.
  static public function validateEdit(
    $adminType, $fields)
  {
    $user = sfContext::getInstance()->getUser();
    $request = sfContext::getInstance()->getRequest();
    foreach ($fields as $data)
    {
      $type = $data['type'];
      $module = isset($data['module']) ? $data['module'] : strtolower(
        $type);
      $field = isset($data['field']) ? $data['field'] : 
        sfInflector::underscore($type) . "_id";
      if ($request->hasParameter("quick-create-$field"))
      {
        $parameters = $request->getParameterHolder()->getAll();
        self::pushStack(
          array('adminType' => $adminType, 
            'type' => $type,
            'field' => $field,
            'parameters' => $parameters));
        self::redirectRouted("$module/edit");
        // If we return here execution continues, which we don't want
        exit(0);
      }
    }
    if ($request->hasParameter("quick-created"))
    {
      $data = self::popStack();
      if ($data['adminType'] !== $adminType)
      {
        // The user has gone off the reservation. Cancel quick creates.
        self::clearStack();
        return true;
      }
      $type = $data['type'];
      $parameters = $data['parameters'];
      $field = $data['field'];
      $id = false;
      if (isset($data['id']))
      {
        $id = $data['id'];
      }
      $object = call_user_func(array($type . "Peer", "retrieveByPK"), $id);
      if ($object)
      {
        $parameters
          [sfInflector::underscore($adminType)][$field] = $object->getId();
      }
      $request->getParameterHolder()->addByRef($parameters);
      $request->setMethod(sfRequest::POST);
      return false;
    }
    return true;
  }
  static public function executeList($type)
  {
    $user = sfContext::getInstance()->getUser();
    $data = self::peekStack();
    if ($data['type'] === $type)
    {
      $parameters = $data['parameters'];
      $module = $parameters['module'];
      $action = $parameters['action'];
      self::redirectRouted("$module/$action?quick-created=1");
      exit(0);
    }
  }
  static public function executeIndex($type)
  {
    // When the user expressly goes to the index of an admin generator
    // module they are off the reservation as far as quick creates
    // are concerned
    self::clearStack();
  }
  static public function save($object)
  {
    $type = get_class($object);
    $data = self::peekStack();
    if ($data['type'] === $type)
    {
      self::setId($object->getId());
      $parameters = $data['parameters'];
      self::redirectRouted($parameters['module'] 
        . '/' . $parameters['action'] . "?quick-created=1");
      exit(0);
    }
  }
  static private function pushStack($data)
  {
    $user = sfContext::getInstance()->getUser();
    $stack = $user->getAttribute("quick-create-stack", array());
    $stack[] = $data;
    $user->setAttribute("quick-create-stack", $stack);
  }
  static private function popStack()
  {
    $user = sfContext::getInstance()->getUser();
    $stack = $user->getAttribute("quick-create-stack", array());
    $data = false;
    if (count($stack))
    {
      $data = array_pop($stack);
      $user->setAttribute("quick-create-stack", $stack);
    }
    return $data;
  }
  static private function peekStack()
  {
    $user = sfContext::getInstance()->getUser();
    $stack = $user->getAttribute("quick-create-stack", array());
    $count = count($stack);
    if ($count)
    {
      return $stack[$count - 1];
    }
    return false;
  }
  static private function setId($id)
  {
    $user = sfContext::getInstance()->getUser();
    $stack = $user->getAttribute("quick-create-stack", array());
    $count = count($stack);
    if ($count)
    {
      $stack[$count - 1]['id'] = $id;
      $user->setAttribute("quick-create-stack", $stack);
    }
  }
  static private function clearStack()
  {
    $user = sfContext::getInstance()->getUser();
    $user->setAttribute("quick-create-stack", null);
  }
  static public function active()
  {
    $user = sfContext::getInstance()->getUser();
    $stack = $user->getAttribute("quick-create-stack", array());
    return (count($stack) != 0);
  }
  public static function redirectRouted($url)
  {
    $controller = sfContext::getInstance()->getController();
    $url = $controller->genUrl($url);
    return $controller->redirect($url);
  }
}

