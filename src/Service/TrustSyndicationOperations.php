<?php

namespace Drupal\ucb_trust_schema\Service;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Service for trust syndication operations.
 */
class TrustSyndicationOperations {

  /**
   * Gets the trust syndication operations for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get operations for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return array
   *   An array of operations.
   */
  public function getOperations(NodeInterface $node, AccountInterface $account) {
    $operations = [];

    if ($account->hasPermission('manage trust metadata')) {
      $operations['trust_syndicate'] = [
        'title' => t('Trust Syndicate'),
        'weight' => 0,
        'url' => Url::fromRoute('ucb_trust_schema.trust_syndication', ['node' => $node->id()]),
      ];
    }

    return $operations;
  }

} 