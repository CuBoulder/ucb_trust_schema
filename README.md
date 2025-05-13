# Trust Schema Module

The Trust Schema Module enables individual Drupal sites to declare and expose trust metadata on selected content nodes. This metadata helps establish the credibility and context of content when it's syndicated across different sites.

## Features

- Trust metadata fields for content nodes:
  - Trust Role (e.g., primary source, secondary source)
  - Trust Scope (e.g., department level, university level)
  - Trust Contact (email for verification)
  - Trust Topics (taxonomy terms categorizing the content)
  - Trust Syndication Enabled (toggle for syndication)

- JSON:API integration for exposing trust metadata
- Custom form for managing trust metadata on nodes
- Trust Topics taxonomy vocabulary for categorizing content

## Installation

1. Place the module in your Drupal installation's `modules/custom` directory
2. Enable the module using Drush or the Drupal admin interface:
   ```bash
   drush en ucb_trust_schema
   ```
3. The module will automatically create the necessary database table and taxonomy vocabulary

## Usage

### Managing Trust Metadata

1. Navigate to any content node
2. Click the "Trust Syndication" tab
3. Fill in the trust metadata fields:
   - Select a Trust Role
   - Choose a Trust Scope
   - Enter a Trust Contact email
   - Select one or more Trust Topics
   - Enable/disable syndication as needed
4. Save the changes

### Accessing Trust Metadata via JSON:API

Trust metadata is exposed through the JSON:API endpoints. 

Here is the easiest way to just view all syndicated nodes

`/api/trust-schema/syndicated-nodes`

The response will include trust metadata in this format:
```json
{
  "data": [{
    "id": "3",
    "type": "basic_page",
    "attributes": {
      "title": "Example Page",
      "trust_role": "primary_source",
      "trust_scope": "department_level",
      "trust_contact": "example@berkeley.edu",
      "trust_topics": ["Science", "Mathematics"],
      "trust_syndication_enabled": true
    }
  }]
}
```

Note: 
- Replace `{node-id}` with the actual node ID
- Replace `your-site.com` with your actual domain
- The `include=field_trust_topics` parameter ensures trust topics are included in the response
- All endpoints require appropriate permissions to access

### Trust Topics

The module creates a "Trust Topics" taxonomy vocabulary with default terms:
- Mathematics
- Science
- Arts

Additional terms can be added through the Drupal taxonomy interface.

## Permissions

The module provides two permissions:
- "Manage trust metadata" - Allows users to assign or modify trust metadata fields
- "View trust metadata" - Allows users to view trust metadata fields

## Dependencies

- Drupal Core (10.x or 11.x)
- JSON:API module

## Technical Details

For detailed technical documentation, see [TECHNICAL.md](TECHNICAL.md). 
