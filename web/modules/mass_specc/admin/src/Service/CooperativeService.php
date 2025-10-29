<?php
namespace Drupal\admin\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\user\Entity\User;
use Drupal\node\NodeInterface;
use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
class CooperativeService
{
    protected $entity_type_manager;
    protected $activityLogger;
    protected $currentUser;

    public function __construct(
        EntityTypeManager $entity_type_manager,
        UserActivityLogger $activityLogger,
        AccountProxyInterface $currentUser
    ) {
        $this->entity_type_manager = $entity_type_manager;
        $this->activityLogger = $activityLogger;
        $this->currentUser = $currentUser;
    }
    public static function create(ContainerInterface $container, )
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('admin.user_activity_logger'),
            $container->get('current_user')
        );
    }

    public function deactivateCooperative(
        int $id
    ) {

        $storage = $this->entity_type_manager->getStorage('node');

        $node = $storage->load($id);

        if ($node instanceof NodeInterface && $node->bundle() === 'cooperative') {
            $node->set('field_coop_status', FALSE);
            $node->save();

            $user_ids = \Drupal::entityQuery('user')
                ->condition('field_cooperative', $node->id())
                ->accessCheck(TRUE)
                ->execute();

            foreach ($user_ids as $uid) {
                $user = User::load($uid);
                if ($user) {
                    $user->block();
                    $user->save();
                }
            }

            $action = 'Deactivated cooperative ' . $node->get('field_coop_name')->value . ' - ' . $node->get('field_coop_code')->value;
            $this->activityLogger->log($action, 'node', $node->id(), [], NULL, $this->currentUser);

            \Drupal::messenger()->addMessage(t('Cooperative deactivated.'));
        } else {
            \Drupal::messenger()->addError(t('Invalid cooperative node.'));
        }
    }

    public function activateCooperative(int $id)
    {
        $storage = $this->entity_type_manager->getStorage('node');
        $node = $storage->load($id);

        if ($node instanceof NodeInterface && $node->bundle() === 'cooperative') {
            $node->set('field_coop_status', TRUE);
            $node->save();

            $user_ids = \Drupal::entityQuery('user')
                ->condition('field_cooperative', $node->id())
                ->accessCheck(TRUE)
                ->execute();

            foreach ($user_ids as $uid) {
                $user = User::load($uid);
                if ($user) {
                    $user->activate();
                    $user->save();
                }
            }
            $action = 'Activated cooperative ' . $node->get('field_coop_name')->value . ' - ' . $node->get('field_coop_code')->value;
            $this->activityLogger->log($action, 'node', $node->id(), [], NULL, $this->currentUser);

            \Drupal::messenger()->addMessage(t('Cooperative activated.'));
        } else {
            \Drupal::messenger()->addError(t('Invalid cooperative node.'));
        }
    }
}
