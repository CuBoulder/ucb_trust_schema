<?php

namespace Drupal\ucb_trust_schema\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Provides a form for editing trust metadata.
 */
class TrustSyndicationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trust_syndication_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form_state->set('node', $node);

    // Get existing trust metadata or set defaults
    $trust_metadata = ucb_trust_schema_get_trust_metadata($node->id()) ?: [
      'trust_role' => '',
      'trust_scope' => '',
      'type' => '',
      'trust_contact' => '',
      'timeliness' => '',
      'audience' => '',
      'trust_topics' => [],
      'trust_syndication_enabled' => FALSE,
    ];

    $form['trust_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Trust Role'),
      '#required' => TRUE,
      '#options' => [
        'primary_source' => $this->t('Primary Source'),
        'secondary_source' => $this->t('Secondary Source'),
        'subject_matter_contributor' => $this->t('Subject Matter Contributor'),
        'unverified' => $this->t('Unverified'),
      ],
      '#default_value' => $trust_metadata['trust_role'],
    ];

    $form['trust_scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Trust Scope'),
      '#required' => TRUE,
      '#options' => [
        'department_level' => $this->t('Department-level'),
        'college_level' => $this->t('College-level'),
        'campus_wide' => $this->t('Campus-wide'),
      ],
      '#default_value' => $trust_metadata['trust_scope'],
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#required' => FALSE,
      '#options' => [
        '' => $this->t('- Select -'),
        'advising_session' => $this->t('Advising session'),
        'brown_bag' => $this->t('Brown Bag'),
        'colloquium_seminar' => $this->t('Colloquium/Seminar'),
        'commencement' => $this->t('Commencement'),
        'community_engagement' => $this->t('Community Engagement'),
        'competition' => $this->t('Competition'),
        'concert_show' => $this->t('Concert/Show'),
        'dates_deadlines' => $this->t('Dates/Deadlines'),
        'exhibit' => $this->t('Exhibit'),
        'featured_event' => $this->t('Featured Event'),
        'festival' => $this->t('Festival'),
        'film' => $this->t('Film'),
        'information_session' => $this->t('Information Session'),
        'lecture_presentation' => $this->t('Lecture/Presentation'),
        'live_streams' => $this->t('Live streams'),
        'meeting_conference' => $this->t('Meeting/Conference'),
        'outreach' => $this->t('Outreach'),
        'social' => $this->t('Social'),
        'sporting_event' => $this->t('Sporting Event'),
        'student_club' => $this->t('Student Club'),
        'tour' => $this->t('Tour'),
        'virtual' => $this->t('Virtual'),
        'workshop_training' => $this->t('Workshop/Training'),
      ],
      '#default_value' => $trust_metadata['type'],
    ];

    $form['trust_contact'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maintainer Contact'),
      '#required' => FALSE,
      '#maxlength' => 255,
      '#default_value' => $trust_metadata['trust_contact'],
    ];

    $form['timeliness'] = [
      '#type' => 'select',
      '#title' => $this->t('Timeliness'),
      '#required' => FALSE,
      '#options' => [
        '' => $this->t('- Select -'),
        'evergreen' => $this->t('Evergreen'),
        'fall_semester' => $this->t('Fall Semester'),
        'spring_semester' => $this->t('Spring Semester'),
        'summer_semester' => $this->t('Summer Semester'),
        'winter_semester' => $this->t('Winter Semester'),
      ],
      '#default_value' => $trust_metadata['timeliness'],
    ];

    $form['audience'] = [
      '#type' => 'select',
      '#title' => $this->t('Audience'),
      '#required' => FALSE,
      '#options' => [
        '' => $this->t('- Select -'),
        'students' => $this->t('Students'),
        'faculty' => $this->t('Faculty'),
        'staff' => $this->t('Staff'),
        'alumni' => $this->t('Alumni'),
      ],
      '#default_value' => $trust_metadata['audience'],
    ];

    $form['trust_syndication_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Trust Syndication'),
      '#default_value' => $trust_metadata['trust_syndication_enabled'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Trust Metadata'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $form_state->get('node');
    
    // Prepare trust metadata
    $trust_metadata = [
      'trust_role' => $form_state->getValue('trust_role'),
      'trust_scope' => $form_state->getValue('trust_scope'),
      'type' => $form_state->getValue('type'),
      'trust_contact' => $form_state->getValue('trust_contact'),
      'timeliness' => $form_state->getValue('timeliness'),
      'audience' => $form_state->getValue('audience'),
      'trust_syndication_enabled' => $form_state->getValue('trust_syndication_enabled'),
    ];
    
    // Update the trust metadata
    if (ucb_trust_schema_update_trust_metadata($node->id(), $trust_metadata)) {
      $this->messenger()->addStatus($this->t('Trust metadata has been saved.'));
    }
    else {
      $this->messenger()->addError($this->t('Failed to save trust metadata.'));
    }
    
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

} 