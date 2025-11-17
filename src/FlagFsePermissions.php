<?php

namespace Drupal\flag_fse;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides dynamic permissions for defined flags.
 */
class FlagFsePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructs a FlagFsePermissions instance.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The flag service.
   */
  public function __construct(FlagServiceInterface $flag) {
    $this->flagService = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('flag'));
  }

  /**
   * Returns an array of dynamic flag permissions.
   *
   * @return array
   *   An array of permissions.
   *
   * @see Drupal\flag\FlagInterface::getPermissions()
   */
  public function permissions() {
    $permissions = [];

    // Get a list of flags from the FlagService.
    $flags = $this->flagService->getAllFlags();

    // Provide flag FSE permissions for each flag.
    foreach ($flags as $flag) {
      $permissions['flag fse ' . $flag->id()] = [
        'title' => $this->t('Flag for someone else : <i>%flag_title</i>', [
          '%flag_title' => $flag->label(),
        ]),
      ];
    }

    return $permissions;
  }

}
