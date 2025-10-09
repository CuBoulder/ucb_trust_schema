<?php

namespace Drupal\ucb_trust_schema\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ucb_trust_schema\Service\TrustSchemaAnalyticsService;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for Trust Schema Analytics API endpoints.
 */
class TrustSchemaAnalyticsController extends ControllerBase {

  /**
   * The analytics service.
   *
   * @var \Drupal\ucb_trust_schema\Service\TrustSchemaAnalyticsService
   */
  protected $analyticsService;

  /**
   * Constructs a new TrustSchemaAnalyticsController.
   *
   * @param \Drupal\ucb_trust_schema\Service\TrustSchemaAnalyticsService $analytics_service
   *   The analytics service.
   */
  public function __construct(TrustSchemaAnalyticsService $analytics_service) {
    $this->analyticsService = $analytics_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ucb_trust_schema.analytics')
    );
  }

  /**
   * Report a view from a consumer site.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function reportView(NodeInterface $node, Request $request) {
    // Get consumer site from request headers or query parameters
    // Consumer sites should explicitly identify themselves with their full site path
    // e.g., "colorado.edu/biden" or "colorado.edu/trust/discovery"
    $consumer_site = $request->headers->get('X-Consumer-Site') 
      ?? $request->query->get('consumer_site');

    // Fallback: Extract domain from Referer header (without path guessing)
    if (empty($consumer_site)) {
      $referer = $request->headers->get('Referer');
      if ($referer) {
        $parsed = parse_url($referer);
        if (!empty($parsed['host'])) {
          $consumer_site = $parsed['host'];
        }
      }
    }

    // Final fallback: Use IP address
    if (empty($consumer_site)) {
      $consumer_site = $request->getClientIp();
    }

    // Basic validation
    if (empty($consumer_site)) {
      return new JsonResponse(['error' => 'Consumer site identifier required'], 400);
    }

    return $this->analyticsService->reportView($node->id(), $consumer_site, $request);
  }

  /**
   * Get analytics for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getAnalytics(NodeInterface $node) {
    if (!$this->currentUser()->hasPermission('view trust metadata')) {
      throw new AccessDeniedHttpException();
    }

    $analytics = $this->analyticsService->getAnalytics($node->id());
    return new JsonResponse($analytics);
  }

} 