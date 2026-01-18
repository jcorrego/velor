## ADDED Requirements
### Requirement: Organization and Entity Grouping
The system SHALL allow users to group entities under organizations.

#### Scenario: Create organization
- **WHEN** a user creates an organization
- **THEN** the system SHALL allow multiple entities to be associated with it

### Requirement: Access Boundaries
The system SHALL prevent users from accessing entities outside their organization.

#### Scenario: Enforce organization isolation
- **WHEN** a user requests data for an unrelated organization
- **THEN** the system SHALL deny access
