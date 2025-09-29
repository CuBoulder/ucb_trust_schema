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

#### Basic Syndicated Nodes Endpoint

Here is the easiest way to just view all syndicated nodes:

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

#### Advanced JSON:API Endpoint

For more detailed access to trust metadata with related node and taxonomy information, use the following JSON:API endpoint:

```
/jsonapi/trust_metadata/trust_metadata?fields[trust_metadata--trust_metadata]=trust_role,trust_scope,type,trust_contact,timeliness,audience,trust_topics,node_id,syndication_consumer_sites,syndication_total_views,syndication_consumer_sites_list&fields[taxonomy_term--trust_topic]=name&fields[node--article]=title,body,path,created,changed&include=trust_topics,node_id
```

This endpoint returns:
- Trust metadata fields (trust_role, trust_scope, type, trust_contact, timeliness, audience, trust_topics)
- Related node information (title, body, path, created, changed)
- Trust topic names
- All relationships between these entities

Example response:
```json
{
  "data": [
    {
      "type": "trust_metadata--trust_metadata",
      "id": "23d1698c-6f66-4502-8f8e-469840942807",
      "attributes": {
        "trust_role": "primary_source",
        "trust_scope": "department_level",
        "trust_contact": null,
        "trust_topics": ["Student Life"],
        "node_id": "3",
        "syndication_consumer_sites": 3,
        "syndication_total_views": 6,
        "syndication_consumer_sites_list": "consumer.ddev.site, discovery.ddev.site, secondcon.ddev.site"
      }
    }
  ]
}
```