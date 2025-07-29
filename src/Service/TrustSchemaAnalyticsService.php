<?php

namespace Drupal\ucb_trust_schema\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Service for handling Trust Schema syndication analytics.
 */
class TrustSchemaAnalyticsService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new TrustSchemaAnalyticsService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Report a view from a consumer site.
   *
   * @param int $node_id
   *   The node ID.
   * @param string $consumer_site
   *   The consumer site domain.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function reportView($node_id, $consumer_site, Request $request) {
    $logger = $this->loggerFactory->get('ucb_trust_schema');

    try {
      $logger->info('Starting reportView for node @nid from site @site', [
        '@nid' => $node_id,
        '@site' => $consumer_site,
      ]);

      // Load the trust metadata for this node
      $query = \Drupal::entityQuery('trust_metadata')
        ->condition('node_id', $node_id)
        ->accessCheck(FALSE);
      $ids = $query->execute();

      $logger->info('Found @count trust metadata entities for node @nid', [
        '@count' => count($ids),
        '@nid' => $node_id,
      ]);

      if (empty($ids)) {
        $logger->warning('No trust metadata found for node @nid', ['@nid' => $node_id]);
        return new JsonResponse(['error' => 'Trust metadata not found'], 404);
      }

      $trust_metadata = $this->entityTypeManager->getStorage('trust_metadata')->load(reset($ids));
      
      $logger->info('Loaded trust metadata entity @id for node @nid', [
        '@id' => $trust_metadata->id(),
        '@nid' => $node_id,
      ]);

      if (!$trust_metadata->get('trust_syndication_enabled')->value) {
        $logger->warning('Syndication not enabled for node @nid', ['@nid' => $node_id]);
        return new JsonResponse(['error' => 'Syndication not enabled'], 403);
      }

      $logger->info('Syndication is enabled, calling updateAnalytics');

      // Update analytics
      $this->updateAnalytics($trust_metadata, $consumer_site);

      $logger->info('View reported for node @nid from site @site', [
        '@nid' => $node_id,
        '@site' => $consumer_site,
      ]);

      return new JsonResponse(['success' => TRUE]);

    }
    catch (\Exception $e) {
      $logger->error('Error reporting view: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Update analytics for a trust metadata entity.
   *
   * @param \Drupal\ucb_trust_schema\Entity\TrustMetadata $trust_metadata
   *   The trust metadata entity.
   * @param string $consumer_site
   *   The consumer site domain.
   */
  protected function updateAnalytics($trust_metadata, $consumer_site) {
    $logger = $this->loggerFactory->get('ucb_trust_schema');
    
    // Get current values
    $current_sites = $trust_metadata->get('syndication_consumer_sites')->value ?? 0;
    $current_views = $trust_metadata->get('syndication_total_views')->value ?? 0;
    $sites_list = $trust_metadata->get('syndication_consumer_sites_list')->value ?? '';

    $logger->info('Current analytics - Sites: @sites, Views: @views, List: @list', [
      '@sites' => $current_sites,
      '@views' => $current_views,
      '@list' => $sites_list,
    ]);

    // Parse existing sites list
    $sites_array = !empty($sites_list) ? explode(',', $sites_list) : [];
    $sites_array = array_map('trim', $sites_array);

    // Add new site if not already in list
    if (!in_array($consumer_site, $sites_array)) {
      $sites_array[] = $consumer_site;
      $logger->info('Added new site @site to list', ['@site' => $consumer_site]);
    } else {
      $logger->info('Site @site already in list', ['@site' => $consumer_site]);
    }

    // Update fields
    $new_sites_count = count($sites_array);
    $new_views_count = $current_views + 1;
    $new_sites_list = implode(', ', $sites_array);
    
    $logger->info('Setting new values - Sites: @sites, Views: @views, List: @list', [
      '@sites' => $new_sites_count,
      '@views' => $new_views_count,
      '@list' => $new_sites_list,
    ]);

    $trust_metadata->set('syndication_consumer_sites', $new_sites_count);
    $trust_metadata->set('syndication_total_views', $new_views_count);
    $trust_metadata->set('syndication_consumer_sites_list', $new_sites_list);

    $result = $trust_metadata->save();
    $logger->info('Entity save result: @result', ['@result' => $result]);
  }

  /**
   * Get analytics for a node.
   *
   * @param int $node_id
   *   The node ID.
   *
   * @return array
   *   The analytics data.
   */
  public function getAnalytics($node_id) {
    $query = \Drupal::entityQuery('trust_metadata')
      ->condition('node_id', $node_id)
      ->accessCheck(FALSE);
    $ids = $query->execute();

    if (empty($ids)) {
      return [
        'consumer_sites' => 0,
        'total_views' => 0,
        'sites_list' => '',
      ];
    }

    $trust_metadata = $this->entityTypeManager->getStorage('trust_metadata')->load(reset($ids));

    return [
      'consumer_sites' => $trust_metadata->get('syndication_consumer_sites')->value ?? 0,
      'total_views' => $trust_metadata->get('syndication_total_views')->value ?? 0,
      'sites_list' => $trust_metadata->get('syndication_consumer_sites_list')->value ?? '',
    ];
  }

} 