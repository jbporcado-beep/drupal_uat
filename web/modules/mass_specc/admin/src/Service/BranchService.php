<?php
namespace Drupal\admin\Service;

use Drupal\node\Entity\Node;

class BranchService
{

    /**
     * Get all branches for a cooperative.
     *
     * @param int $coop_id
     *   The cooperative node ID.
     *
     * @return \Drupal\node\Entity\Node[]
     *   Array of branch nodes.
     */
    public function getBranchesByCoop($coop_id)
    {
        $nids = \Drupal::entityQuery('node')
            ->condition('type', 'branch')
            ->condition('field_branch_coop', $coop_id)
            ->accessCheck(TRUE)
            ->execute();

        return Node::loadMultiple($nids);
    }

}