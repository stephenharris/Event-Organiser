Feature: View events
    In order to advertise my events
    As a user
    I need to be able to view event details

    Background:
        Given I have a vanilla wordpress installation
            | name          | email                   | username | password |
            | BDD WordPress | test.user@wordpress.dev | admin    | test     |

        And there are plugins
            | plugin                              | status  |
            | event-organiser/event-organiser.php | enabled |
            
		And there are events
            | post_title | start            | end              | post_status | all_day | schedule | schedule_meta | frequency | until            |
            | Single     | 2115-04-30 13:00 | 2115-04-30 14:00 | publish     | 0       | once     |               |           |                  |
            | Daily      | 2115-05-01       | 2115-05-01       | publish     | 1       | daily    |               | 1         | 2115-05-15       |
            | Weekly     | 2115-06-01 19:30 | 2115-06-01 21:30 | publish     | 0       | weekly   |               | 2         | 2115-06-29 19:30 |
            | Monthly 1  | 2115-01-15 09:45 | 2115-01-15 11:00 | publish     | 0       | monthly  | BYMONTHDAY    | 1         | 2115-01-15 09:45 |
            | Monthly 2  | 2115-01-15 09:45 | 2115-01-15 11:00 | publish     | 0       | monthly  | BYDAY         | 1         | 2115-01-17 09:45 |
            | Yearly     | 2112-01-01       | 2112-01-01       | publish     | 1       | yearly   |               | 1         | 2115-01-01       |

    Scenario: Single event
        When I go to "events/event/single"
    	Then I should see "Single"
    	And I should see "Start: April 30, 2115 1:00 pm"

	Scenario: Daily event
        When I go to "events/event/daily"
    	Then I should see "Daily"
    	And I should see "May 1, 2115"
    	And I should see "May 2, 2115"
    	And I should see "May 3, 2115"
    	And I should see "May 4, 2115"
    	And I should see "May 5, 2115"
    	And I should not see "May 6, 2115"
    	When I follow "Show More"
		Then I should see "May 6, 2115"