Feature: Create events
    In order to create events
    As a user
    I need to be able to configure events

    Background:
        Given I have a vanilla wordpress installation
            | name          | email                   | username | password |
            | BDD WordPress | test.user@wordpress.dev | admin    | test     |

        And there are plugins
            | plugin                              | status  |
            | event-organiser/event-organiser.php | enabled |
    
        And I am logged in as "admin" with password "test"

    Scenario: Single event
        When I go to "wp-admin/post-new.php?post_type=event"
    	And I fill in the following:
    		| title         | Single Event |
        	| eo-start-date | 25-10-2015   |
        	| eo-start-time | 10:30pm      |
        	| eo-end-date   | 25-10-2015   |
        	| eo-end-time   | 11:45pm      |
		And I press "save-post"
		Then I should see "Event draft updated"
		
    Scenario: Daily event
        When I go to "wp-admin/post-new.php?post_type=event"
    	And I fill in the following:
    		| title         | Daily Event  |
        	| eo-start-date | 06-11-2014   |
        	| eo-start-time | 10:00pm      |
        	| eo-end-date   | 06-11-2014   |
        	| eo-end-time   | 11:00pm      |
		And I select "daily" from "eo-event-recurrence"
		And I fill in "eo-recurrence-frequency" with "2"
		And I fill in "recend" with "13-11-2014"
		And I press "save-post"
		Then I should see "Event draft updated"
		
    Scenario: Weekly event
        When I go to "wp-admin/post-new.php?post_type=event"
    	And I fill in the following:
    		| title         | Weekly Event  |
        	| eo-start-date | 01-01-2015    |
        	| eo-start-time | 02:15pm       |
        	| eo-end-date   | 02-01-2015    |
        	| eo-end-time   | 02:30pm       |
		And I select "weekly" from "eo-event-recurrence"
		And I check "day-Sat"
		And I fill in "recend" with "29-01-2015"
		And I press "save-post"
		Then I should see "Event draft updated"
		
    Scenario: Monthly event (by date)
        When I go to "wp-admin/post-new.php?post_type=event"
    	And I fill in the following:
    		| title         | Monthly Event 1 |
        	| eo-start-date | 30-01-2016      |
        	| eo-start-time | 01:00pm         |
        	| eo-end-date   | 30-01-2016      |
        	| eo-end-time   | 02:00pm         |
		And I select "monthly" from "eo-event-recurrence"
		And I select "BYMONTHDAY=" from "eo_input[schedule_meta]"
		And I fill in "recend" with "30-12-2016"
		And I press "save-post"
		Then I should see "Event draft updated"

    Scenario: Monthly event (by day of the month)
        When I go to "wp-admin/post-new.php?post_type=event"
    	And I fill in the following:
    		| title         | Monthly Event 2 |
        	| eo-start-date | 30-01-2016      |
        	| eo-start-time | 01:00pm         |
        	| eo-end-date   | 30-01-2016      |
        	| eo-end-time   | 02:00pm         |
		And I select "monthly" from "eo-event-recurrence"
		And I select "BYDAY=" from "eo_input[schedule_meta]"
		And I fill in "recend" with "31-12-2016"
		And I press "save-post"
		Then I should see "Event draft updated"

    Scenario: Yearly event
        When I go to "wp-admin/post-new.php?post_type=event"
    	And I fill in the following:
    		| title         | Yearly Event  |
        	| eo-start-date | 29-02-2016    |
        	| eo-start-time | 05:00pm       |
        	| eo-end-date   | 29-02-2016    |
        	| eo-end-time   | 06:00pm       |
		And I select "yearly" from "eo-event-recurrence"
		And I fill in "recend" with "29-02-2024"
		And I press "save-post"
		Then I should see "Event draft updated"

