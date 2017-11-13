<?php

/**
 * @file
 * Contains \Drupal\mailermailer\Form\MailerMailerForm.
 */

namespace Drupal\mailermailer\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mailermailer\Services\MailerMailer;
use Egulias\EmailValidator\EmailValidator;

// Traits
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MailerMailerForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Drupal\mailermailer\Services\MailerMailer definition.
   *
   * @var \Drupal\mailermailer\Services\MailerMailer
   */
  protected $mailermailer;


  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new MailerMailerForm.
   *
   * @param \Drupal\mailermailer\Services\MailerMailer $mailermailer
   *   The mailermailer service.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *  The email validator.
   */
  public function __construct(MailerMailer $mailermailer, EmailValidator $email_validator) {
    $this->mailermailer = $mailermailer;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mailermailer.services'),
      $container->get('email.validator')
    );
  }

  /*
  **
  * Returns a unique string identifying the form.
  *
  * @return string
  *   The unique string identifying the form.
  */
  public function getFormId() {
    return 'mailermailer_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This will generate an anchor scroll to the form when submitting
    $form['#action'] = '#mailermailer-form';

    $form['mailermailer'] = array(
      '#type'  => 'fieldset',
    );

    $form['mailermailer']['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];

    $mailer_form = $this->mailermailer->getFormFields();
    foreach ($mailer_form as $mailer_field) {
      $required = 0;
      if ($mailer_field['visible'] == 1) {
        if ($mailer_field['required'] == 1) {
          $required = TRUE;
        }
        if ($mailer_field['type'] == 'open_text') {
          $form['mailermailer'][$mailer_field['fieldname']] = [
            '#type' => 'textfield',
            '#title' => $mailer_field['description'],
            '#required' => $required,
          ];
        }
        if ($mailer_field['type'] == 'select') {
          $form['mailermailer'][$mailer_field['fieldname']] = [
            '#type' => 'select',
            '#title' => $mailer_field['description'],
            '#required' => $required,
            '#options' => $mailer_field['choices'],
          ];
        }
      }
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-md',
          'btn-primary',
          'use-ajax-submit'
        ]
      ],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'ajax-form',
      ],
    ];

    // Disable caching
    $form['#cache']['max-age'] = 0;

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Assert the email is valid
    if (!$form_state->getValue('user_email') || !filter_var($form_state->getValue('user_email'), FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('user_email', $this->t('That e-mail address is not valid.'));
    }
  }

  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
      '#status_headings' => [
        'status' => t('Status message'),
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];
    $messages = \Drupal::service('renderer')->render($message);

    $response->addCommand(new HtmlCommand('#form-system-messages', $messages));
    if (!$form_state->hasAnyErrors()) {
      $userInput = $form_state->getUserInput();
      $keys = $form_state->getCleanValueKeys();
      $newInputArray = [];
      foreach ($keys as $key) {
        if ($key == "op")  continue;
        $newInputArray[$key] = $userInput[$key];
      }

      $form_state->setUserInput($newInputArray);
      $form_state->setRebuild();
      $response->addCommand(new PrependCommand('#mailermailer-form', $form));
    }
    return $response;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The form was submitted'));
  }

}