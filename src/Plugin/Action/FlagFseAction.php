<?php

namespace Drupal\flag_fse\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlaggingInterface;

/**
 * An action plugin to flag a flagging entity directly.
 *
 * @Action(
 *   id = "flag_fse",
 *   label = @Translation("Flag for someone else"),
 *   type = "flagging"
 * )
 */
class FlagFseAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\flag\FlaggingInterface $object */
    return $object->access('flag fse', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?FlaggingInterface $flagging = NULL) {
    $flagging->save();
  }

}
