<?php

namespace Drupal\flag_fse\Plugin\ActionLink;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;
use Drupal\Component\Serialization\Json;
use Drupal\flag\ActionLink\ActionLinkTypeBase;
use Drupal\flag\Plugin\ActionLink\FormEntryInterface;
use Drupal\flag\ActionLink\ActionLinkPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Provides the "Flag for someone else" type.
 *
 * @ActionLinkType(
 *   id = "flag_fse",
 *   label = @Translation("For someone else"),
 *   description = "Flag for someone else if user has permission."
 * )
 */
class FlagFse extends ActionLinkTypeBase implements FormEntryInterface {
  use StringTranslationTrait;
  /**
   * The action link plugin manager.
   *
   * @var \Drupal\flag\ActionLink\ActionLinkPluginManager
   */
  protected $actionLinkManager;

  /**
   * Build a new link type instance and sets the configuration.
   *
   * @param array $configuration
   *   The configuration array with which to initialize this plugin.
   * @param string $plugin_id
   *   The ID with which to initialize this plugin.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param Drupal\flag\ActionLink\ActionLinkPluginManager $action_link_manager
   *   The ection link type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $current_user, ActionLinkPluginManager $action_link_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_user);
    $this->actionLinkManager = $action_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('plugin.manager.flag.linktype'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $options = parent::defaultConfiguration();

    $options += [
      'flag_fse_plugin_fallback' => 'ajax_link',
      'flag_fse_link_title' => $this->t('Flag for someone else'),
      'flag_fse_confirmation' => $this->t('Flag user to this content?'),
      'flag_fse_create_button' => $this->t('Create flagging'),
      'flag_fse_form_behavior' => 'default',
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $plugin_id = $this->getPluginId();
    $plugin_description = $this->pluginDefinition['label'];

    $all_link_types = $this->actionLinkManager->getAllLinkTypes();
    // List plugin types to unset.
    $unset_types = [$plugin_id, 'confirm', 'field_entry'];

    $options = array_diff_key($all_link_types, array_flip($unset_types));

    $form['display']['settings']['link_options_' . $plugin_id] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Options for the "@label" link type', ['@label' => $plugin_description]),
      '#id' => 'link-options-confirm',
    ];

    $form['display']['settings']['link_options_' . $plugin_id]['flag_fse_plugin_fallback'] = [
      '#type' => 'select',
      '#title' => $this->t('Fallback plugin'),
      '#options' => $options,
      '#default_value' => $this->configuration['flag_fse_plugin_fallback'],
      '#weight' => -5,
      '#attributes' => [
        'class' => ['flag-fse-options'],
      ],
      '#required' => TRUE,
    ];

    $form['display']['settings']['link_options_' . $plugin_id]['flag_fse_link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flag fse link text'),
      '#default_value' => $this->configuration['flag_fse_link_title'],
      '#description' => $this->t('The text for the "Flag for someone else" link for this flag.'),
      '#required' => TRUE,
    ];

    $form['display']['settings']['link_options_' . $plugin_id]['flag_fse_confirmation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flag confirmation message'),
      '#default_value' => $this->configuration['flag_fse_confirmation'],
      '#description' => $this->t('Message displayed if the user has clicked the "flag this" link and confirmation is required. Usually presented in the form of a question such as, "Are you sure you want to flag this content?"'),
      '#required' => TRUE,
    ];

    $form['display']['settings']['link_options_' . $plugin_id]['flag_fse_create_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Create flagging button text'),
      '#default_value' => $this->configuration['flag_fse_create_button'],
      '#description' => $this->t('The text for the submit button when creating a flagging.'),
      '#required' => TRUE,
    ];

    $form['display']['settings']['link_options_' . $plugin_id]['flag_fse_form_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Form behavior'),
      '#options' => [
        'default' => $this->t('New page'),
        'dialog' => $this->t('Dialog'),
        'modal' => $this->t('Modal dialog'),
      ],
      '#description' => $this->t('If an option other than <em>new page</em> is selected, the form will open via AJAX on the same page.'),
      '#default_value' => $this->configuration['flag_fse_form_behavior'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $form_values = $form_state->getValues();

    if (empty($form_values['flag_fse_plugin_fallback'])) {
      $form_state->setErrorByName('flagging_edit_title', $this->t('A fallback plugin is required when using this link type.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    foreach (array_keys($this->defaultConfiguration()) as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl($action, FlagInterface $flag, EntityInterface $entity) {
    return Url::fromRoute('flag_fse.confirm_flag', [
      'flag' => $flag->id(),
      'entity_id' => $entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity, ?string $view_mode = NULL): array {
    $action = 'flag_fse';
    $access = $flag->actionAccess($action, $this->currentUser, $entity);

    $build = [
      '#theme' => 'flag_fse',
      '#action' => $action,
      '#flag' => $flag,
      '#flaggable' => $entity,
      '#view_mode' => $view_mode,
    ];

    $fse_plugin_id = $this->getPluginId();
    $link_type_plugin = $flag->getLinkTypePlugin();

    if ($access->isAllowed()) {
      $fse_link = [
        '#theme' => 'flag_fse_link',
        '#action' => $action,
        '#flag' => $flag,
        '#flaggable' => $entity,
        '#view_mode' => $view_mode,
      ];

      if ($this->configuration['flag_fse_form_behavior'] !== 'default') {
        $fse_link['#attached']['library'][] = 'core/drupal.ajax';
        $fse_link['#attributes']['class'][] = 'use-ajax';
        $fse_link['#attributes']['data-dialog-type'] = $this->configuration['flag_fse_form_behavior'];
        $fse_link['#attributes']['data-dialog-options'] = Json::encode([
          'width' => 'auto',
        ]);
      }
      $fse_link['#title']['#markup'] = $this->configuration['flag_fse_link_title'];

      $url = $this->getUrl($action, $flag, $entity);
      $url->setRouteParameter('destination', $this->getDestination());
      $url->setRouteParameter('view_mode', $view_mode);

      $rendered_url = $url->toString(TRUE);
      $rendered_url->applyTo($fse_link);

      $fse_link['#attributes']['href'] = $rendered_url->getGeneratedUrl();

      CacheableMetadata::createFromRenderArray($fse_link)
        ->addCacheableDependency($access)
        ->applyTo($fse_link);

      $build['#flag_fse_link'] = $fse_link;
    }

    // Switch to fallback plugin to render link.
    $plugin_config = $link_type_plugin->getConfiguration();
    $flag->setLinkTypePlugin($plugin_config['flag_fse_plugin_fallback']);

    $action = $this->getFallbackAction($flag, $entity);
    $access_fallback = $flag->actionAccess($action, $this->currentUser, $entity);

    $fallback_link = $flag->getLinkTypePlugin()->getAsFlagLink($flag, $entity, $view_mode);

    CacheableMetadata::createFromRenderArray($fallback_link)
      ->addCacheableDependency($access_fallback)
      ->applyTo($fallback_link);

    $fallback_link['#theme'] = 'flag_fse_fallback_link';

    $build['#fallback_link'] = $fallback_link;

    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($access)
      ->applyTo($build);

    // Switch back to flag_fse plugin.
    $flag->setLinkTypePlugin($fse_plugin_id);

    return $build;
  }

  /**
   * Helper method to get the next flag action the user can take.
   */
  protected function getFallbackAction(FlagInterface $flag, EntityInterface $entity) {
    return $flag->isFlagged($entity) ? 'unflag' : 'flag';
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagQuestion() {
    return $this->configuration['flag_fse_confirmation'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEditFlaggingTitle() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getUnflagQuestion() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCreateButtonText() {
    return $this->configuration['flag_fse_create_button'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteButtonText() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateButtonText() {
    return '';
  }

}
