<?php

namespace Drupal\flag_fse\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\Plugin\ActionLink\FormEntryInterface;
use Drupal\flag\Form\FlagConfirmFormBase;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the confirm form page for flagging an entity.
 *
 * @see \Drupal\flag_fse\Plugin\ActionLink\FlagFse
 */
class FlagFseConfirmForm extends FlagConfirmFormBase {
  use StringTranslationTrait;
  /**
   * The flaggable entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The flag entity.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The password generator.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * Constructs a FlagConfirmFormBase object.
   *
   * @param \Drupal\flag\FlagService $flag_service
   *   The flag service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface $password_generator
   *   The password generator.
   */
  public function __construct(FlagService $flag_service, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, PasswordGeneratorInterface $password_generator) {
    $this->flagService = $flag_service;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->currentUser = $current_user;
    $this->passwordGenerator = $password_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('password_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flag_fse_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FlagInterface $flag = NULL, $entity_id = NULL) {

    $this->flag = $flag;
    $this->entity = $this->flagService->getFlaggableById($this->flag, $entity_id);

    $form = parent::buildForm($form, $form_state, $this->flag, $entity_id);
    $selected_user_type = $form_state->getValue(['user_type'], 'existing');
    $wrapper_id = Html::getUniqueId('user-fieldset-wrapper');

    $form['user'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User'),
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    if ($this->currentUser->hasPermission('administer users')) {
      $form['user']['user_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Event for'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['container-inline'],
        ],
        '#required' => TRUE,
        '#options' => [
          'existing' => $this->t('Existing user'),
          'new' => $this->t('New user'),
        ],
        '#default_value' => $selected_user_type,
        '#ajax' => [
          'callback' => [$this, 'userFormAjax'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }
    else {
      $form['user']['user_type'] = [
        '#type' => 'hidden',
        '#default_value' => $selected_user_type,
      ];
    }

    if ($selected_user_type == 'existing') {
      $form['user']['uid'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Search'),
        '#attributes' => [
          'class' => ['container-inline'],
        ],
        '#placeholder' => $this->t('Search by username or email address'),
        '#target_type' => 'user',
        '#required' => TRUE,
        '#selection_handler' => 'flag_fse:user',
        '#selection_settings' => [
          'match_operator' => 'CONTAINS',
          'include_anonymous' => FALSE,
        ],
      ];
    }
    else {
      if ($this->currentUser->hasPermission('administer users')) {
        // New user.
        $form['user']['uid'] = [
          '#type' => 'value',
          '#value' => 0,
        ];
        $form['user']['username'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Username'),
          '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
          '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
          '#required' => TRUE,
          '#attributes' => [
            'class' => ['username'],
            'autocorrect' => 'off',
            'autocapitalize' => 'off',
            'spellcheck' => 'false',
          ],
        ];
        $form['user']['mail'] = [
          '#type' => 'email',
          '#title' => $this->t('Email'),
          '#required' => TRUE,
        ];
        $form['user']['password'] = [
          '#type' => 'container',
        ];
        $form['user']['password']['generate'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Generate password'),
          '#default_value' => 1,
        ];
        // The password_confirm element needs to be wrapped in event for #states
        // to work properly. See https://www.drupal.org/node/1427838.
        $form['user']['password']['password_confirm_wrapper'] = [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input[name="generate"]' => ['checked' => FALSE],
            ],
          ],
        ];
        // We cannot make this required due to HTML5 validation.
        $form['user']['password']['password_confirm_wrapper']['pass'] = [
          '#type' => 'password_confirm',
          '#size' => 25,
        ];

        $form['user']['notify'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Notify user of new account'),
        ];
      }
    }

    return $form;
  }

  /**
   * Ajax callback.
   */
  public function userFormAjax(array $form, FormStateInterface $form_state) {
    return $form['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $link_plugin = $this->flag->getLinkTypePlugin();
    return $link_plugin instanceof FormEntryInterface ? $link_plugin->getFlagQuestion() : $this->t('Flag this content');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->flag->getLongText('flag');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $link_plugin = $this->flag->getLinkTypePlugin();
    return $link_plugin instanceof FormEntryInterface ? $link_plugin->getCreateButtonText() : $this->t('Create flagging');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $form_values = $form_state->getValues();
    if (isset($form_values['mail'], $form_values['user_type']) && $form_values['user_type'] == 'new') {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->userStorage->create([
        'name' => $form_values['username'],
        'mail' => $form_values['mail'],
        'pass' => ($form_values['generate']) ? $this->passwordGenerator->generate() : $form_values['pass'],
        'status' => TRUE,
      ]);
      $form_state->set('user', $user);
      $violations = $user->validate();
      foreach ($violations->getByFields(['name', 'mail']) as $violation) {
        $form_state->setErrorByName(str_replace('.', '][', $violation->getPropertyPath()), $violation->getMessage());
      }
    }

    parent::validateForm($form, $form_state);
    $form_values = $form_state->getValues();

    if ($form_values['uid'] && $form_values['user_type'] == 'existing') {
      $uid = $form_values['uid'];
      $user = User::load($uid);
      if ($this->flag->isFlagged($this->entity, $user)) {
        $form_state->setErrorByName('user_to_flag', $this->t('The user has already flagged this entity.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    if ($form_values['user_type'] == 'existing') {
      $form_values['mail'] = $this->userStorage->load($form_values['uid'])->getEmail();
    }
    else {
      $user = $form_state->get('user');
      $user->save();

      $form_values['uid'] = $user->id();

      if ($form_values['notify']) {
        _user_mail_notify('register_admin_created', $user);
      }
    }

    $uid = $form_values['uid'];
    $user = User::load($uid);

    $this->flagService->flag($this->flag, $this->entity, $user);
  }

}
