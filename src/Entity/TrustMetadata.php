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
      ->setDescription(t('The type/subject of the content'))
      ->setSettings([
        'allowed_values' => [
          'advising_session' => t('Advising session'),
          'brown_bag' => t('Brown Bag'),
          'colloquium_seminar' => t('Colloquium/Seminar'),
          'commencement' => t('Commencement'),
          'community_engagement' => t('Community Engagement'),
          'competition' => t('Competition'),
          'concert_show' => t('Concert/Show'),
          'dates_deadlines' => t('Dates/Deadlines'),
          'exhibit' => t('Exhibit'),
          'featured_event' => t('Featured Event'),
          'festival' => t('Festival'),
          'film' => t('Film'),
          'information_session' => t('Information Session'),
          'lecture_presentation' => t('Lecture/Presentation'),
          'live_streams' => t('Live streams'),
          'meeting_conference' => t('Meeting/Conference'),
          'outreach' => t('Outreach'),
          'social' => t('Social'),
          'sporting_event' => t('Sporting Event'),
          'student_club' => t('Student Club'),
          'tour' => t('Tour'),
          'virtual' => t('Virtual'),
          'workshop_training' => t('Workshop/Training'),
        ],
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -2.5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2.5,
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



  /**
   * Gets the content authority from site name.
   *
   * @return string
   *   The site name from system configuration.
   */
  public static function getContentAuthority() {
    return \ucb_trust_schema_get_site_name();
  }

} 