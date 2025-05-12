<?php

namespace Drupal\ucb_trust_schema\Service;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Service for handling trust schema operations.
 */
class TrustSchemaOperations {

  /**
   * Get the operations for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get operations for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return array
   *   The operations array.
   */
  public function getOperations(NodeInterface $node, AccountInterface $account) {
    $operations = [];

    if ($account->hasPermission('manage trust metadata')) {
      $operations['trust_syndication'] = [
        'title' => t('Syndicate Content'),
        'url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()], [
          'query' => ['trust_syndication' => 'true'],
          'fragment' => 'trust-syndication',
        ]),
        'weight' => 100,
      ];
    }

    return $operations;
  }

  /**
   * Check if a user can access trust syndication.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function accessTrustSyndication(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('manage trust metadata'));
  }

} 