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

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type/subject of the content (currently disabled)'))
      ->setSettings([
        'allowed_values' => [],
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -2.5,
      ])
      ->setDisplayConfigurable('form', FALSE)
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

    $fields['timeliness'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Timeliness'))
      ->setDescription(t('The timeliness of the content (evergreen, semester-specific, etc.).'))
      ->setSettings([
        'allowed_values' => [
          'evergreen' => 'Evergreen',
          'fall_semester' => 'Fall Semester',
          'spring_semester' => 'Spring Semester',
          'summer_semester' => 'Summer Semester',
          'winter_semester' => 'Winter Semester',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -1.5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1.5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['audience'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Audience'))
      ->setDescription(t('The target audience for the content.'))
      ->setSettings([
        'allowed_values' => [
          'students' => 'Students',
          'faculty' => 'Faculty',
          'staff' => 'Staff',
          'alumni' => 'Alumni',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -1.4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1.4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trust_topics'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Subjects'))
      ->setDescription(t('The trust subject associated with this content.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['trust_topics' => 'trust_topics']])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1,
        'settings' => [
          'multiple' => TRUE,
          'size' => 15,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['content_authority'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content Authority'))
      ->setDescription(t('The content authority from site affiliation.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -0.4,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ucb_trust_schema\Field\ContentAuthorityFieldAccessor');

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
      ->setDisplayConfigurable('form', FALSE)
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
      ->setDisplayConfigurable('form', FALSE)
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
      ->setDisplayConfigurable('form', FALSE)
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



  /**
   * Gets the content authority from site name.
   *
   * @return string
   *   The site name from system configuration.
   */
  public static function getContentAuthority() {
    return \ucb_trust_schema_get_site_name();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Since this entity doesn't have a label field, we'll use the node title
    // if available, or provide a fallback.
    $node = $this->get('node_id')->entity;
    if ($node) {
      $node_label = $node->label();
      // Ensure we return a string, never null.
      return $node_label ?? '';
    }
    // Fallback to entity ID if node is not available.
    $id = $this->id();
    if ($id) {
      return $this->t('Trust Metadata #@id', ['@id' => $id]);
    }
    // Final fallback for new unsaved entities.
    return $this->t('Trust Metadata');
  }

} 