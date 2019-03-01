Feature: Calendar of events
    In order to advertise my events
    As a user
    I need to be able to display my events in a calendar

    Background:
        Given I have a vanilla WordPress installation
            | name          | email                   | username | password |
            | BDD WordPress | test.user@wordpress.dev | admin    | test     |

        And there are plugins
            | plugin                              | status  |
            | event-organiser/event-organiser.php | enabled |
            
		And there are events
            | post_title | start            | end              | post_status | all_day | schedule | schedule_meta | frequency | until            |
            | Single     | Y-m-14 13:00     | Y-m-14 13:40     | publish     | 0       | once     |               |           |                  |
            | Daily      | Y-m-10           | Y-m-10           | publish     | 1       | daily    |               | 1         | Y-m-15           |
            | Past Event | 2016-03-01 19:30 | 2016-03-01 21:30 | publish     | 0       | weekly   |               | 1         | 2016-03-30 21:30 |

	@javascript @insulated
  	Scenario: Viewing events of the current month in the calendar
    	Given there are posts
            | post_title  | post_content      | post_status | post_author |
            | Calendar    | [eo_fullcalendar] | publish     | admin       |
    	When I go to "calendar"
    	And the calendar finishes loading
    	Then I should see "Single"
    	And I should see "Daily"
    	But I should not see "Past Event"
    	
    @javascript @insulated
  	Scenario: Viewing occurrences of a particular event
    	Given there are posts
            | post_title  | post_content                                             | post_status | post_author |
            | Calendar    | [eo_fullcalendar event_series="{{id of event "Daily"}}"] | publish     | admin       |
    	When I go to "calendar"
    	And the calendar finishes loading
    	Then I should see "Daily"
    	But I should not see "Single"
    	And I should not see "Past Event"

