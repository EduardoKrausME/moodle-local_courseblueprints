@local_courseblueprints
Feature: Administrators can see the institutional blueprint catalog
  In order to provision courses consistently
  As a site administrator
  I can navigate to the institution's blueprint catalogue

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | manager1 | A | Manager | manager1@example.com |
    And the following "role assigns" exist:
      | user | role | contextlevel | reference |
      | manager1 | manager | System | |

  @javascript
  Scenario: A manager can open the blueprint catalogue
    Given I log in as "manager1"
    When I navigate to "Courses > Institutional course blueprints" in site administration
    Then I should see "Institutional blueprints"
    And I should see "Create blueprint"

  @javascript
  Scenario: A manager can create a blank blueprint draft
    Given I log in as "manager1"
    When I navigate to "Courses > Institutional course blueprints" in site administration
    And I follow "Create blueprint"
    And I set the following fields to these values:
      | Stable code | behold002 |
      | Name | Example course blueprint |
      | Number of sections | 3 |
    And I press "Create blueprint"
    Then I should see "Example course blueprint"
    And I should see "draft"
