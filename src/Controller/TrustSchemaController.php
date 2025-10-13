<?php

namespace Drupal\ucb_trust_schema\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for Trust Schema API endpoints.
 */
class TrustSchemaController extends ControllerBase {

  /**
   * Get trust metadata for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get trust metadata for.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getTrustMetadata(NodeInterface $node) {
    $query = \Drupal::database()->select('trust_metadata', 'tm')
      ->fields('tm')
      ->condition('nid', $node->id())
      ->execute();
    
    $record = $query->fetchAssoc();
    
    if (!$record) {
      $record = [
        'nid' => $node->id(),
        'trust_role' => '',
        'trust_scope' => '',
        'type' => '',
        'trust_contact' => '',
        'timeliness' => '',
        'audience' => '',
        'trust_topics' => '[]',
        'trust_syndication_enabled' => 0,
      ];
    }

    // Parse trust topics from JSON and load term names
    $trust_topics = json_decode($record['trust_topics'], TRUE) ?: [];
    $term_names = [];
    
    if (!empty($trust_topics)) {
      // Convert string IDs to integers
      $term_ids = array_map('intval', $trust_topics);
      
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadMultiple($term_ids);
      
      foreach ($terms as $term) {
        $term_names[] = $term->getName();
      }
    }

    $trust_metadata = [
      'node_id' => $node->id(),
      'uuid' => $node->uuid(),
      'trust_role' => $record['trust_role'],
      'trust_scope' => $record['trust_scope'],
      'type' => $record['type'],
      'trust_contact' => $record['trust_contact'],
      'timeliness' => $record['timeliness'],
      'audience' => $record['audience'],
      'trust_topics' => $term_names,
      'trust_syndication_enabled' => (bool) $record['trust_syndication_enabled'],
    ];

    return new JsonResponse($trust_metadata);
  }

  /**
   * Update trust metadata for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to update trust metadata for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function updateTrustMetadata(NodeInterface $node, Request $request) {
    if (!$this->currentUser()->hasPermission('manage trust metadata')) {
      throw new AccessDeniedHttpException();
    }

    $content = json_decode($request->getContent(), TRUE);
    if (!$content) {
      return new JsonResponse(['error' => 'Invalid JSON'], 400);
    }

    // Update trust metadata fields
    $node->set('trust_role', $content['trust_role']);
    $node->set('trust_scope', $content['trust_scope']);
    $node->set('trust_contact', $content['trust_contact']);
    $node->set('trust_topics', $content['trust_topics']);

    try {
      $node->save();
      return new JsonResponse(['message' => 'Trust metadata updated successfully']);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Access callback for trust metadata operations.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(NodeInterface $node) {
    return AccessResult::allowedIf($this->currentUser()->hasPermission('manage trust metadata'));
  }

  /**
   * Get all trust topics from taxonomy.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing the terms.
   */
  public function getTopics() {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'trust_topics']);

    $terms_data = [];
    foreach ($terms as $term) {
      $terms_data[] = [
        'tid' => $term->id(),
        'name' => $term->getName(),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'terms' => $terms_data,
    ]);
  }

  /**
   * View trust syndication data for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to view trust syndication data for.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function view(NodeInterface $node) {
    try {
      $query = \Drupal::database()->select('trust_metadata', 'tm')
        ->fields('tm')
        ->condition('nid', $node->id())
        ->execute();
      
      $record = $query->fetchAssoc();
      
      if (!$record) {
        // If no record exists, create a default one
        $record = [
          'nid' => $node->id(),
          'trust_role' => '',
          'trust_scope' => '',
          'trust_contact' => '',
          'trust_topics' => '[]',
          'trust_syndication_enabled' => 0,
        ];
      }

      // Parse trust topics from JSON and load term names
      $trust_topics = json_decode($record['trust_topics'], TRUE) ?: [];
      $term_names = [];
      
      if (!empty($trust_topics)) {
        // Convert string IDs to integers
        $term_ids = array_map('intval', $trust_topics);
        
        $terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadMultiple($term_ids);
        
        foreach ($terms as $term) {
          $term_names[] = $term->getName();
        }
      }

      // Get the summary based on content type
      $summary = '';
      if ($node->hasField('body')) {
        $body = $node->get('body')->first();
        if ($body && !$body->isEmpty()) {
          $summary = $body->summary ?: $body->value;
        }
      }
      
      // Check for article summary field
      if (empty($summary) && $node->hasField('field_ucb_article_summary')) {
        $article_summary = $node->get('field_ucb_article_summary')->first();
        if ($article_summary && !$article_summary->isEmpty()) {
          $summary = $article_summary->value;
        }
      }
      
      // Check for generic summary field
      if (empty($summary) && $node->hasField('field_summary')) {
        $generic_summary = $node->get('field_summary')->first();
        if ($generic_summary && !$generic_summary->isEmpty()) {
          $summary = $generic_summary->value;
        }
      }

      // Get the abstract
      $abstract = '';
      if ($node->hasField('field_abstract')) {
        $abstract_field = $node->get('field_abstract')->first();
        if ($abstract_field && !$abstract_field->isEmpty()) {
          $abstract = $abstract_field->value;
        }
      }

      $trust_metadata = [
        'node_id' => $node->id(),
        'uuid' => $node->uuid(),
        'trust_role' => $record['trust_role'],
        'trust_scope' => $record['trust_scope'],
        'timeliness' => $record['timeliness'] ?? '',
        'audience' => $record['audience'] ?? '',
        'trust_contact' => $record['trust_contact'],
        'trust_topics' => $term_names,
        'trust_syndication_enabled' => (bool) $record['trust_syndication_enabled'],
        'node_summary' => $summary,
        'node_abstract' => $abstract,
      ];

      return new JsonResponse([
        'success' => TRUE,
        'data' => $trust_metadata,
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('ucb_trust_schema')->error('Error in view method: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'An error occurred while fetching trust metadata.',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Save trust syndication data for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to save trust syndication data for.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function save(NodeInterface $node, Request $request) {
    if (!$this->currentUser()->hasPermission('manage trust metadata')) {
      throw new AccessDeniedHttpException();
    }

    try {
      $content = json_decode($request->getContent(), TRUE);
      if (!$content) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'Invalid JSON data'
        ], 400);
      }

      // Convert trust topics to integers
      $trust_topics = array_map('intval', $content['trust_topics']);

      // Update trust metadata in the custom table
      $record = [
        'nid' => $node->id(),
        'uuid' => $node->uuid(),
        'trust_role' => $content['trust_role'],
        'trust_scope' => $content['trust_scope'],
        'type' => $content['type'] ?? '',
        'timeliness' => $content['timeliness'] ?? '',
        'audience' => $content['audience'] ?? '',
        'trust_contact' => $content['trust_contact'],
        'trust_topics' => json_encode($trust_topics),
        'trust_syndication_enabled' => (int) $content['trust_syndication_enabled'],
        'changed' => \Drupal::time()->getRequestTime(),
      ];

      // Check if record exists to determine if we need to set created timestamp
      $exists = \Drupal::database()->select('trust_metadata', 'tm')
        ->condition('nid', $node->id())
        ->countQuery()
        ->execute()
        ->fetchField();

      if (!$exists) {
        $record['created'] = $record['changed'];
      }

      \Drupal::database()->merge('trust_metadata')
        ->key(['nid' => $node->id()])
        ->fields($record)
        ->execute();

      // Get the summary based on content type
      $summary = '';
      if ($node->hasField('body')) {
        $body = $node->get('body')->first();
        if ($body && !$body->isEmpty()) {
          $summary = $body->summary ?: $body->value;
        }
      }
      
      // Check for article summary field
      if (empty($summary) && $node->hasField('field_ucb_article_summary')) {
        $article_summary = $node->get('field_ucb_article_summary')->first();
        if ($article_summary && !$article_summary->isEmpty()) {
          $summary = $article_summary->value;
        }
      }
      
      // Check for generic summary field
      if (empty($summary) && $node->hasField('field_summary')) {
        $generic_summary = $node->get('field_summary')->first();
        if ($generic_summary && !$generic_summary->isEmpty()) {
          $summary = $generic_summary->value;
        }
      }

      // Get the abstract
      $abstract = '';
      if ($node->hasField('field_abstract')) {
        $abstract_field = $node->get('field_abstract')->first();
        if ($abstract_field && !$abstract_field->isEmpty()) {
          $abstract = $abstract_field->value;
        }
      }

      // Load term names for the response
      $term_names = [];
      if (!empty($trust_topics)) {
        $terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadMultiple($trust_topics);
        
        foreach ($terms as $term) {
          $term_names[] = $term->getName();
        }
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'node_id' => $node->id(),
          'uuid' => $node->uuid(),
          'trust_role' => $content['trust_role'],
          'trust_scope' => $content['trust_scope'],
          'trust_contact' => $content['trust_contact'],
          'trust_topics' => $term_names,
          'trust_syndication_enabled' => (bool) $content['trust_syndication_enabled'],
          'node_summary' => $summary,
          'node_abstract' => $abstract,
        ],
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('ucb_trust_schema')->error('Error saving trust metadata: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Failed to save trust metadata',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

} 