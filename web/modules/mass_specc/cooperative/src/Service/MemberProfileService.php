<?php

namespace Drupal\cooperative\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

class MemberProfileService
{
    protected $entityTypeManager;
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Finds or creates a Member Profile based on individual fields.
     */
    public function findOrCreateMemberProfile(array $individual_data): ?Node
    {
        $first = mb_strtolower(trim($individual_data['first_name'] ?? ''));
        $last = mb_strtolower(trim($individual_data['last_name'] ?? ''));
        $dob = $individual_data['dob'] ?? '';
        $gender = strtolower($individual_data['gender'] ?? '');

        if (empty($first) || empty($last) || empty($dob) || empty($gender)) {
            return NULL;
        }

        $query = $this->entityTypeManager->getStorage('node')->getQuery()
            ->accessCheck(FALSE)
            ->condition('type', 'member_profile')
            ->condition('status', 1)
            ->condition('field_first_name', $first)
            ->condition('field_last_name', $last)
            ->condition('field_date_of_birth', $dob)
            ->condition('field_gender', $gender);

        $results = $query->execute();

        if (!empty($results)) {
            return Node::load(reset($results));
        }

        $profile = Node::create([
            'type' => 'member_profile',
            'title' => "{$first} {$last}",
            'field_first_name' => $first,
            'field_last_name' => $last,
            'field_date_of_birth' => $dob,
            'field_gender' => $gender,
        ]);

        $profile->save();

        return $profile;
    }
}
