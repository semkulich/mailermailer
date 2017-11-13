<?php

namespace Drupal\mailermailer\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mailermailer\Services\MailerMailer;
use Drupal\Core\Config\ConfigFactoryInterface;
use MAILAPI_Client;

// Traits
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements the MailerMailerAPIkey settings form.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class MailerMailerSettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Drupal\mailermailer\Services\MailerMailer definition.
   *
   * @var \Drupal\mailermailer\Services\MailerMailer
   */
  protected $mailermailer;

  /**
   * Constructs a new MailerMailerForm.
   *
   * @param \Drupal\mailermailer\Services\MailerMailer $mailermailer
   *   The mailermailer service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailerMailer $mailermailer) {
    parent::__construct($config_factory);
    $this->mailermailer = $mailermailer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mailermailer.services')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailermailer_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mailermailer.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mailermailer.settings');
    $form['mailermailer_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MailerMailer API key'),
      '#default_value' => $config->get('mailermailer_api_key'),
      '#description' => $this->t('MailerMailer requires users to use a valid API key.'),
    ];


    $form['check_api_key'] = [
      '#type' => 'button',
      '#value' => 'Check your API key',
      '#prefix' => '<div id="key-result"></div>',
      '#ajax' => [
        'callback' => '::checkApiKey',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying API key...'),
        ],
      ],
    ];

    $form['messages'] = [
      '#markup' => '<div id="api-key-result"></div>',
      '#weight' => -100,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    Cache::invalidateTags(['mailermailer_api_key']);

    $api_key = $form_state->getValue('mailermailer_api_key');

    if (!empty($api_key)) {
      $config = $this->config('mailermailer.settings');
      $config->set('mailermailer_api_key', $api_key);
      $config->save();
      drupal_set_message($this->t('The configuration options have been saved.'));
    }
  }

  /**
   * AJAX response: verifying API key.
   *
   * @param array $form
   *   Form API array structure.
   * @param $form_state
   *   Form state information.
   *
   * @return AjaxResponse
   *   Response object.
   */
  public function checkApiKey(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $api_key = $form_state->getValue('mailermailer_api_key');
    $message = 'Required data is missing.';
    if (!empty($api_key)) {
      $mail_api = new MAILAPI_Client($api_key);
      $ping = $mail_api->ping();
      $message = $this->mailermailer->evaluateResponse($ping);
      if ($message == 1) {
        $message = $this->t('The API key is valid.');
      }
    }
    $response->addCommand(new HtmlCommand('#key-result', $message));
    return $response;
  }

}
