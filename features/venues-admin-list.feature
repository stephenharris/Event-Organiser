Feature: Venues admin list
    In order to manage my events
    As a user
    I need to be able to see a list my events in the admin

    Background:
        Given I have a vanilla wordpress installation
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

		And there are venues
            | name        | address            | city      | postcode | country        | description            |
            | Akva        | 129 Fountainbridge | Edinburgh | EH3 9QG  | United Kingdom | Swedish bar & cafe     |
            | Living Room | 113-115 George St  | Edinburgh | EH2 4JN  | United Kingdom | Restaurant & piano bar |
            | Dragonfly   | 52 West Port       | Edinburgh | EH1 2LD  | United Kingdom | 20s style cocktail bar |

        And the event "Single" has event-venue terms Living Room
        And the event "Daily" has event-venue terms Akva 
        And the event "Weekly" has event-venue terms Akva
        
        And I am logged in as "admin" with password "test"
    
    @admin
  	Scenario: Viewing events in the admin list
    	When I go to "/wp-admin/edit.php?post_type=event&page=venues"
    	Then the post list table looks like
	      | Venue       | Address            | City      | State / Province | Post Code | Country        | Slug        | Events |
	      | Akva        | 129 Fountainbridge | Edinburgh |                  | EH3 9QG   | United Kingdom | akva        | 2      |
	      | Living Room | 113-115 George St  | Edinburgh |                  | EH2 4JN   | United Kingdom | living-room | 1      |
	      | Dragonfly   | 52 West Port       | Edinburgh |                  | EH1 2LD   | United Kingdom | dragonfly   | 0      |


    @admin
	Scenario: Sorting by name, address and postcode.
		When I go to "/wp-admin/edit.php?post_type=event&page=venues"
		And I sort venues by "name" "ascending"
		Then I should see the following in the repeated ".wp-list-table tr .column-name .row-title" element
	      | Akva        |
	      | Dragonfly   |
	      | Living Room |
		When I sort venues by "name" "descending"
		Then I should see the following in the repeated ".wp-list-table tr .column-name .row-title" element
	      | Living Room |
	      | Dragonfly   |
	      | Akva        |

		And I sort venues by "address" "ascending"
		Then I should see the following in the repeated ".wp-list-table tr .column-name .row-title" element
	      | Living Room |
	      | Akva        |
	      | Dragonfly   |

		When I sort venues by "postcode" "descending"
		Then I should see the following in the repeated ".wp-list-table tr .column-name .row-title" element
	      | Akva        |
	      | Living Room |
	      | Dragonfly   |
	      
		When I sort venues by "count" "ascending"
		Then I should see the following in the repeated ".wp-list-table tr .column-name .row-title" element
	      | Dragonfly   |
	      | Living Room |
	      | Akva        |

    @admin
	Scenario: Navigating to the
		Given I am on "/wp-admin/edit.php?post_type=event&page=venues"
		When I follow "Add New Venue"
		Then I should be on the "Add New Venue" page

		
    @admin
	Scenario: Navigating to the
		Given I am on "/wp-admin/edit.php?post_type=event&page=venues"
		When I follow "Akva"
		Then I should be on the "Edit Venue" page