<?php

namespace Drupal\ucb_trust_schema\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes trust metadata for JSON:API responses.
 */
class TrustMetadataNormalizer extends NormalizerBase implements NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\node\NodeInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $data = parent::normalize($object, $format, $context);

    // Get trust metadata from the database
    $query = \Drupal::database()->select('trust_metadata', 'tm')
      ->fields('tm')
      ->condition('nid', $object->id())
      ->execute();
    
    $record = $query->fetchAssoc();
    
    if ($record) {
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

      // Update the trust topics in the response
      if (isset($data['attributes']['trust_topics'])) {
        $data['attributes']['trust_topics'] = $term_names;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, ?string $format = NULL, array $context = []): bool {
    return $data instanceof \Drupal\node\NodeInterface;
  }

} 