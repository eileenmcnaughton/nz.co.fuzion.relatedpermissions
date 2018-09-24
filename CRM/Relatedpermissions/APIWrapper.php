<?php

class CRM_Relatedpermissions_APIWrapper implements API_Wrapper {
  /**
   * the wrapper contains a method that allows you to alter the parameters of the api request (including the action and the entity)
   */
  public function fromApiInput($apiRequest) {
    if ('Invalid' == CRM_Utils_Array::value('contact_type', $apiRequest['params'])) {
      $apiRequest['params']['contact_type'] = 'Individual';
    }
    return $apiRequest;
  }

  /**
   * alter the result before returning it to the caller.
   */
  public function toApiOutput($apiRequest, $result) {
    if (($apiRequest['entity'] === 'Relationship') && ($apiRequest['action'] === 'get')) {
      foreach ($result['values'] as $index => &$relationship) {
        $relationship = CRM_Relatedpermissions_Utils_Relatedpermissions::enforcePermissions($relationship);
      }
      return $result;
    }
  }
}
