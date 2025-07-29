<?php

namespace Drupal\ucb_trust_schema\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Trust Metadata entity.
 *
 * @ContentEntityType(
 *   id = "trust_metadata",
 *   label = @Translation("Trust Metadata"),
 *   base_table = "trust_metadata",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "node" = "node_id",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ucb_trust_schema\TrustMetadataListBuilder",
 *     "form" = {
 *       "default" = "Drupal\ucb_trust_schema\Form\TrustMetadataForm",
 *       "add" = "Drupal\ucb_trust_schema\Form\TrustMetadataForm",
 *       "edit" = "Drupal\ucb_trust_schema\Form\TrustMetadataForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\ucb_trust_schema\TrustMetadataAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/trust-metadata/{trust_metadata}",
 *     "add-form" = "/trust-metadata/add",
 *     "edit-form" = "/trust-metadata/{trust_metadata}/edit",
 *     "delete-form" = "/trust-metadata/{trust_metadata}/delete",
 *     "collection" = "/admin/content/trust-metadata",
 *   },
 *   admin_permission = "manage trust metadata",
 * )
 */
class TrustMetadata extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['node_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Node'))
      ->setDescription(t('The node this trust metadata belongs to.'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['trust_role'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Trust Role'))
      ->setDescription(t('The trust role of the content.'))
      ->setSettings([
        'allowed_values' => [
          'primary_source' => t('Primary Source'),
          'secondary_source' => t('Secondary Source'),
          'subject_matter_contributor' => t('Subject Matter Contributor/Expert'),
          'unverified' => t('Unverified'),
        ],
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trust_scope'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Trust Scope'))
      ->setDescription(t('The scope of the content.'))
      ->setSettings([
        'allowed_values' => [
          'department_level' => t('Department-level'),
          'college_level' => t('College-level'),
          'administrative_unit' => t('Administrative-unit'),
          'campus_wide' => t('Campus-wide'),
        ],
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trust_contact'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Trust Contact'))
      ->setDescription(t('Contact information for the content maintainer. Defaults to emails from the developer role, separated by commas.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValueCallback('Drupal\ucb_trust_schema\Entity\TrustMetadata::getDefaultTrustContact')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trust_topics'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Trust Topics'))
      ->setDescription(t('The trust topics associated with this content.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trust_syndication_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Trust Syndication Enabled'))
      ->setDescription(t('Whether trust syndication is enabled.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Syndication analytics fields
    $fields['syndication_consumer_sites'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Consumer Sites Count'))
      ->setDescription(t('Number of sites that are consuming this content.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE);

    $fields['syndication_total_views'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Views'))
      ->setDescription(t('Total number of views across all consumer sites.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE);

    $fields['syndication_consumer_sites_list'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Consumer Sites'))
      ->setDescription(t('List of sites consuming this content.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'trust_contact' field.
   *
   * @return string
   *   A comma-separated list of emails from the 'developer' role.
   */
  public static function getDefaultTrustContact() {
    $emails = [];
    $query = \Drupal::entityQuery('user')
      ->condition('roles', 'developer')
      ->accessCheck(FALSE);
    $uids = $query->execute();
    if (!empty($uids)) {
      $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($uids);
      foreach ($users as $user) {
        $emails[] = $user->getEmail();
      }
    }
    return implode(', ', $emails);
  }

} 