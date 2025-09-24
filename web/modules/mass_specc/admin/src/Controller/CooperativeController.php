<?php

namespace Drupal\admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class CooperativeController extends ControllerBase
{

    public function title($id)
    {
        return $this->t('Edit Cooperative');
    }

    public function addCooperative(): array
    {
        return $this->formBuilder()->getForm(\Drupal\admin\Form\CooperativeCreateForm::class);
    }

    public function editCooperative($id): array
    {
        return $this->formBuilder()->getForm('Drupal\admin\Form\CooperativeEditForm', $id);
    }
    public function editCooperativeBranches($id): array
    {
        $node = Node::load($id);
        if ($node && $node->bundle() === 'cooperative') {
            $view = \Drupal\views\Views::getView('branches_list');
            $view->setDisplay('branches_table');
            $view->setArguments([$id]);
            $view->preExecute();
            $view->execute();
            return $view->render();
        } else {
            \Drupal::messenger()->addError($this->t('Invalid cooperative node.'));
            return [
                '#markup' => $this->t('Invalid cooperative node.'),
            ];
        }
    }
}
