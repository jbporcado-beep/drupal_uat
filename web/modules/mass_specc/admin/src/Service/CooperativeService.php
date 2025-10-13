<?php
namespace Drupal\admin\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\user\Entity\User;
use Drupal\node\NodeInterface;
class CooperativeService
{
    protected $entity_type_manager;

    public function __construct(EntityTypeManager $entity_type_manager)
    {
        $this->entity_type_manager = $entity_type_manager;
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

            \Drupal::messenger()->addMessage(t('Cooperative activated.'));
        } else {
            \Drupal::messenger()->addError(t('Invalid cooperative node.'));
        }
    }
}
