<?php
namespace Drupal\common\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
class ConfirmActionForm extends ConfirmFormBase
{

  protected $id;
  protected $actionLabel;
  protected $question;
  protected $description;
  protected $confirm_mode;
  protected $redirectRoute;
  protected $serviceId;
  protected $method;
  protected $requestStack;

  public function __construct(RequestStack $request_stack)
  {
    $this->requestStack = $request_stack;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('request_stack')
    );
  }

  public function getFormId()
  {
    return 'confirm_action_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
  {
    $form['#prefix'] = '<div class="confirmation-dialog">';
    $this->id = $id;
    $query = $this->requestStack->getCurrentRequest()->query;

    $this->actionLabel = $query->get('action_label', 'Confirm');
    $this->question = $query->get('question', '');
    $this->serviceId = $query->get('service');
    $this->method = $query->get('method');
    $this->description = $query->get('description', ' ');
    $this->redirectRoute = $query->get('redirect_route', '<front>');
    $this->confirm_mode = $query->get('confirm_mode', '');

    $query = $this->requestStack->getCurrentRequest()->query;
    $this->redirectRoute = $query->get('redirect_route', '<front>');

    $form_state->set('id', $this->id);
    $form_state->set('service_id', $this->serviceId);
    $form_state->set('method', $this->method);
    $form_state->set('redirect_route', $this->redirectRoute);


    $form['text'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['confirmation-text'],
      ],
    ];

    $form['text']['message'] = [
      '#type' => 'markup',
      '#markup' => '<p class="confirmation-question">' . $this->t($this->question) . '</p>',
    ];

    if (!empty($this->description)) {
      $form['text']['description'] = [
        '#type' => 'markup',
        '#markup' => '<p class="confirmation-description">' . $this->t($this->description) . '</p>',
      ];
    }

    $form['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['confirmation-actions'],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t($this->actionLabel),
      '#attributes' => [
        'class' => ['submit-modal-btn', $this->confirm_mode],
      ],
      '#ajax' => [
        'callback' => '::ajaxSubmit',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('No'),
      '#attributes' => [
        'class' => ['cancel-modal-btn'],
      ],
      '#ajax' => [
        'callback' => '::ajaxCancel',
      ],
    ];


    $form['#suffix'] = '</div>';

    return $form;
  }

  public function getQuestion()
  {
    return $this->t($this->question);
  }

  public function getCancelUrl()
  {
    return NULL;
  }

  public function getDescription()
  {
    return $this->t($this->description);
  }

  public function getConfirmText()
  {
    return $this->t($this->actionLabel);
  }

  /**
   * AJAX callback for cancel button.
   */
  public function ajaxCancel(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * AJAX callback for submit button.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();

    $id = $form_state->get('id');
    $service_id = $form_state->get('service_id');
    $method = $form_state->get('method');
    $redirect_route = $form_state->get('redirect_route');

    try {
      if ($service_id && $method) {
        $service = \Drupal::service($service_id);
        if (method_exists($service, $method)) {
          $service->{$method}((int) $id);
        }
      }

      $response->addCommand(new CloseModalDialogCommand());

      $redirect_url = Url::fromRoute($redirect_route)->toString();
      $response->addCommand(new RedirectCommand($redirect_url));

    } catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $id = $form_state->get('id');
    $service_id = $form_state->get('service_id');
    $method = $form_state->get('method');
    $redirect_route = $form_state->get('redirect_route');

    if ($service_id && $method) {
      $service = \Drupal::service($service_id);
      if (method_exists($service, $method)) {
        $service->{$method}((int) $id);
      }
    }

    $form_state->setRedirect($redirect_route);
  }
}