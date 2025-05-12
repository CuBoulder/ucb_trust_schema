<?php

namespace Drupal\ucb_trust_schema\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds trust-syndicate link template to nodes.
 */
class TrustSyndicationRouteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['entity.node.link_template'] = ['onNodeLinkTemplate'];
    return $events;
  }

  /**
   * Adds the trust-syndicate link template to nodes.
   *
   * @param \Symfony\Component\EventDispatcher\GenericEvent $event
   *   The event object.
   */
  public function onNodeLinkTemplate($event) {
    $link_templates = $event->getArgument('link_templates');
    $link_templates['trust-syndicate'] = 'node/{node}/trust-syndicate';
    $event->setArgument('link_templates', $link_templates);
  }

} 