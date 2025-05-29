<?php

namespace Drupal\ucb_trust_schema;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Trust Metadata entity.
 */
class TrustMetadataAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ucb_trust_schema\Entity\TrustMetadata $entity */
    switch ($operation) {
      case 'view':
        // Allow anonymous users to view trust metadata.
        return AccessResult::allowed();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage trust metadata');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage trust metadata');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage trust metadata');
  }

} 