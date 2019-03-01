Feature: View events
    In order to advertise my events
    As a user
    I need to be able to view event details

    Background:
        Given I have a vanilla WordPress installation
            | name          | email                   | username | password |
            | BDD WordPress | test.user@wordpress.dev | admin    | test     |

        And there are plugins
            | plugin                              | status  |
            | event-organiser/event-organiser.php | enabled |
            
		And there are events
            | post_title   | start            | end              | post_status | all_day | schedule | schedule_meta | frequency | until            |
            | Single       | 2115-04-30 13:00 | 2115-04-30 14:00 | publish     | 0       | once     |               |           |                  |
            | Daily        | 2115-05-01       | 2115-05-01       | publish     | 1       | daily    |               | 1         | 2115-05-15       |
            | Weekly       | 2115-06-01 19:30 | 2115-06-01 21:30 | publish     | 0       | weekly   |               | 2         | 2115-06-29 19:30 |
            | Monthly 1    | 2115-01-15 09:45 | 2115-01-15 11:00 | publish     | 0       | monthly  | BYMONTHDAY    | 1         | 2115-12-15 09:45 |
            | Monthly 2    | 2115-01-15 09:45 | 2115-01-15 11:00 | publish     | 0       | monthly  | BYDAY         | 1         | 2115-12-17 09:45 |
            | Yearly       | 2112-01-01       | 2112-01-01       | publish     | 1       | yearly   |               | 1         | 2115-01-01       |
            | Yearly Past  | 2012-01-01       | 2012-01-01       | publish     | 1       | yearly   |               | 1         | 2015-01-01       |

    Scenario: Single event
        When I go to "events/event/single"
    	Then I should see "Single"
    	And I should see "Date: April 30, 2115 1:00 pm – 2:00 pm"

    Scenario: All events
        When I go to "events/event"
    	Then I should see "Events"
    	And I should see an ".eo-tc-page" element
    	
    Scenario: Single event (with templates disabled)
        Given event templates are disabled
        When I go to "events/event/single"
    	Then I should see "Single"
    	And I should not see "Date:"

    Scenario: Single event (with templates enabled)
        Given event templates are enabled
        When I go to "events/event/single"
    	Then I should see "Single"
    	And I should see "Date: April 30, 2115 1:00 pm – 2:00 pm"
    	
    Scenario: All events (templates enabled)
        Given event templates are enabled
        When I go to "events/event"
    	Then I should see "Events"
    	And I should not see an ".eo-tc-page" element
    	
    Scenario: All events (theme compatability mode)
        Given theme compatability is enabled
        When I go to "events/event"
    	Then I should see "Events"
    	And I should see an ".eo-tc-page" element

    Scenario: All events
        When I go to "events/event"
    	Then I should see "Events"
    	And I should see an ".eo-tc-page" element
    	
    Scenario: Single event (with templates disabled)
        Given event templates are disabled
        When I go to "events/event/single"
    	Then I should see "Single"
    	And I should not see "Date:"
    	
    Scenario: All events (templates enabled)
        Given event templates are enabled
        When I go to "events/event"
    	Then I should see "Events"
    	And I should not see an ".eo-tc-page" element
    	
    Scenario: All events (templates enabled)
        Given theme compatability is enabled
        When I go to "events/event"
    	Then I should see "Events"
    	And I should see an ".eo-tc-page" element

	@javascript @insulated
	Scenario: Daily event
        When I go to "events/event/daily"
    	Then I should see "Daily"
    	And I should see "This event is running from 1 May 2115 until 15 May 2115. It is next occurring on May 1, 2115"
    	And I should see "May 1, 2115"
    	And I should see "May 2, 2115"
    	And I should see "May 3, 2115"
    	And I should see "May 4, 2115"
    	And I should see "May 5, 2115"
    	And I should not see "May 6, 2115"
    	When I follow "Show More"
		Then I should see "May 6, 2115"

    Scenario: Weekly event
        When I go to "events/event/weekly"
    	Then I should see "Weekly"
    	And I should see "This event is running from 1 June 2115 until 29 June 2115. It is next occurring on June 1, 2115 7:30 pm"
    	And I should see "June 1, 2115 7:30 pm – 9:30 pm"
    	And I should see "June 15, 2115 7:30 pm – 9:30 pm"
    	And I should see "June 29, 2115 7:30 pm – 9:30 pm"
    	
    Scenario: Monthly event (by date)
        When I go to "events/event/monthly-1"
    	Then I should see "Monthly 1"
		And I should see "This event is running from 15 January 2115 until 15 December 2115. It is next occurring on January 15, 2115 9:45 am"
		And I should see "January 15, 2115 9:45 am – 11:00 am"
    	And I should see "February 15, 2115 9:45 am – 11:00 am"
    	And I should see "March 15, 2115 9:45 am – 11:00 am"
    	And I should see "April 15, 2115 9:45 am – 11:00 am"
    	And I should see "May 15, 2115 9:45 am – 11:00 am"
    	And I should see "June 15, 2115 9:45 am – 11:00 am"
    	And I should see "July 15, 2115 9:45 am – 11:00 am"
    	And I should see "August 15, 2115 9:45 am – 11:00 am"
    	And I should see "September 15, 2115 9:45 am – 11:00 am"
    	And I should see "October 15, 2115 9:45 am – 11:00 am"
    	And I should see "November 15, 2115 9:45 am – 11:00 am"
    	And I should see "December 15, 2115 9:45 am – 11:00 am"

    Scenario: Monthly event (by day)
        When I go to "events/event/monthly-2"
    	Then I should see "Monthly 2"
    	And I should see "This event is running from 15 January 2115 until 17 December 2115. It is next occurring on January 15, 2115 9:45 am"
    	And I should see "January 15, 2115 9:45 am – 11:00 am"
    	And I should see "February 19, 2115 9:45 am – 11:00 am"
    	And I should see "March 19, 2115 9:45 am – 11:00 am"
    	And I should see "April 16, 2115 9:45 am – 11:00 am"
    	And I should see "May 21, 2115 9:45 am – 11:00 am"
    	And I should see "June 18, 2115 9:45 am – 11:00 am"
    	And I should see "July 16, 2115 9:45 am – 11:00 am"
    	And I should see "August 20, 2115 9:45 am – 11:00 am"
    	And I should see "September 17, 2115 9:45 am – 11:00 am"
    	And I should see "October 15, 2115 9:45 am – 11:00 am"
    	And I should see "November 19, 2115 9:45 am – 11:00 am"
    	And I should see "December 17, 2115 9:45 am – 11:00 am"
    	
    Scenario: Yearly event
        When I go to "events/event/yearly"
    	Then I should see "Yearly"
    	And I should see "This event is running from 1 January 2112 until 1 January 2115. It is next occurring on January 1, 2112"
    	And I should see "January 1, 2112"
    	And I should see "January 1, 2113"
    	And I should see "January 1, 2114"
    	And I should see "January 1, 2115"
    	
    Scenario: Yearly past event
        When I go to "events/event/yearly-past"
    	Then I should see "Yearly Past"
    	And I should see "This event finished on 01 January 2015"

	Scenario: Different event format
    	Given I set "date_format" option to "jS M y"
    	And I set "time_format" option to "H:i"
    	When I go to "events/event/weekly"
		And I should see "This event is running from 1 June 2115 until 29 June 2115. It is next occurring on 1st Jun 15 19:30"
    	And I should see "1st Jun 15 19:30 – 21:30"
    	And I should see "15th Jun 15 19:30 – 21:30"
    	And I should see "29th Jun 15 19:30 – 21:30"
    	