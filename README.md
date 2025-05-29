# UCB Trust Schema Module

A Drupal module that enables individual Drupal sites to declare and expose trust metadata on selected content nodes.

## Features

- Adds trust metadata fields to nodes:
  - Trust Role (Primary Source, Secondary Source, Subject Matter Contributor/Expert, Unverified)
  - Trust Scope (Department-level, College-level, Administrative-unit, Campus-wide)
  - Trust Contact (Contact information for content maintainer)
  - Trust Topics (Taxonomy terms for categorizing content)
  - Trust Syndication Enabled (Toggle for syndication)

## Installation

1. Place the module in your Drupal installation's `modules/custom` directory
2. Enable the module using Drush or the Drupal admin interface:
   ```bash
   drush en ucb_trust_schema
   ```
3. The module will automatically:
   - Create the necessary fields on nodes
   - Set up default trust topics
   - Migrate any existing trust metadata

## Usage

### Managing Trust Metadata

1. Navigate to `/admin/content/trust-metadata` to view and manage trust metadata for all nodes
2. Edit trust metadata for individual nodes through their edit forms
3. Filter and sort trust metadata using the provided filters

### JSON:API Integration

Access trust metadata through JSON:API endpoints:

1. For a specific node:
   ```
   /jsonapi/node/article/{node_id}
   ```

2. For all nodes:
   ```
   /jsonapi/node/article
   ```

3. Filter by trust metadata:
   ```
   /jsonapi/node/article?filter[field_trust_syndication_enabled]=1
   ```

### Trust Topics

Default trust topics include:
- Mathematics
- Science
- Arts
- Engineering
- Business
- Education
- Health
- Law
- Social Sciences
- Computer Science
- Bingus

## Permissions

The module provides two permissions:
- `manage trust metadata`: Allows users to assign or modify trust metadata fields
- `view trust metadata`: Allows users to view trust metadata fields

## Development

### Field Structure

The module adds the following fields to nodes:
- `field_trust_role`: List string field for trust roles
- `field_trust_scope`: List string field for trust scopes
- `field_trust_contact`: String field for contact information
- `field_trust_topics`: Entity reference field to taxonomy terms
- `field_trust_syndication_enabled`: Boolean field for syndication status

### Updating Trust Topics

To add new trust topics:
1. Navigate to Structure > Taxonomy > Trust Topics
2. Add new terms as needed

## Support

For issues and feature requests, please use the module's issue queue.
