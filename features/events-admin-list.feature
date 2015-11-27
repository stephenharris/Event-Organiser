Feature: Events admin list
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
            | Monthly 1    | 2115-01-15 09:45 | 2115-01-15 10:00 | publish     | 0       | monthly  | BYMONTHDAY    | 1         | 2115-12-15 09:45 |
            | Monthly 2    | 2115-01-15 08:45 | 2115-01-15 11:00 | publish     | 0       | monthly  | BYDAY         | 1         | 2115-12-17 08:45 |
            | Yearly       | 2112-01-01       | 2112-01-01       | publish     | 1       | yearly   |               | 1         | 2115-01-01       |
            | Yearly Past  | 2012-01-01       | 2012-01-01       | publish     | 1       | yearly   |               | 1         | 2015-01-01       |

		And there are "event-category" terms
            | name         | description | slug | parent |
            | Children's   |             |      |        |
            | Food & Drink |             |      |        |
            | Literature   |             |      |        |
            | Music        |             |      |        |

		And there are venues
            | name        | address            | city      | postcode | country        | description            |
            | Akva        | 129 Fountainbridge | Edinburgh | EH3 9QG  | United Kingdom | Swedish bar & cafe     |
            | Living Room | 113-115 George St  | Edinburgh | EH2 4JN  | United Kingdom | Restaurant & piano bar |
            | Dragonfly   | 52 West Port       | Edinburgh | EH1 2LD  | United Kingdom | 20s style cocktail bar |

        And the event "Single" has event-category terms Music
        And the event "Weekly" has event-category terms Literature, Music 
        And the event "Monthly 2" has event-category terms Food & Drink 
        And the event "Yearly" has event-category terms Children's 

        And the event "Weekly" has event-venue terms Akva
        And the event "Monthly 2" has event-venue terms Akva
        And the event "Single" has event-venue terms Living Room
        And the event "Daily" has event-venue terms Dragonfly 

        And I am logged in as "admin" with password "test"
    
    @admin
  	Scenario: Viewing events in the admin list
    	When I go to "/wp-admin/edit.php?post_type=event"
    	Then the post list table looks like
	      | Event       | Comments     | Venue       | Categories        | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Yearly Past | —No comments |             |                   | Jan, 1 2012         | Jan, 1 2012          | every year until Jan, 1st 2015                        |
	      | Yearly      | —No comments |             | Children’s        | Jan, 1 2112         | Jan, 1 2112          | every year until Jan, 1st 2115                        |
	      | Monthly 2   | —No comments | Akva        | Food & Drink      | Jan, 15 21158:45 am | Jan, 15 211511:00 am | every month on the third Tuesday until Dec, 17th 2115 |
	      | Monthly 1   | —No comments |             |                   | Jan, 15 21159:45 am | Jan, 15 211510:00 am | every month on the 15th until Dec, 15th 2115          |
	      | Single      | —No comments | Living Room | Music             | Apr, 30 21151:00 pm | Apr, 30 21152:00 pm  | one time only                                         |
	      | Daily       | —No comments | Dragonfly   |                   | May, 1 2115         | May, 1 2115          | every day until May, 15th 2115                        |
	      | Weekly      | —No comments | Akva        | Literature, Music | Jun, 1 21157:30 pm  | Jun, 1 21159:30 pm   | every 2 weeks on Saturday until Jun, 29th 2115        |

    @admin
	Scenario: Sorting by start and end date
		When I go to "/wp-admin/edit.php?post_type=event"
		And I sort events by "start date" "descending"
		Then I should see the following in the repeated ".wp-list-table tr .column-title strong" element
	      | text        |
	      | Weekly      |
	      | Daily       |
	      | Single      |
	      | Monthly 1   |
	      | Monthly 2   |
	      | Yearly      |
	      | Yearly Past |
		When I sort events by "end date" "ascending"
		Then I should see the following in the repeated ".wp-list-table tr .column-title strong" element
	      | text        |
	      | Yearly Past |
	      | Yearly      |
	      | Monthly 1   |
	      | Monthly 2   |
	      | Single      |
	      | Daily       |
	      | Weekly      |
		When I sort events by "end date" "descending"
		Then I should see the following in the repeated ".wp-list-table tr .column-title strong" element
	      | text        |
	      | Weekly      |
	      | Daily       |
	      | Single      |
	      | Monthly 2   |
	      | Monthly 1   |
	      | Yearly      |
	      | Yearly Past |
		When I sort events by "start date" "ascending"
		Then I should see the following in the repeated ".wp-list-table tr .column-title strong" element
	      | text        |
	      | Yearly Past |
	      | Yearly      |
	      | Monthly 2   |
	      | Monthly 1   |
	      | Single      |
	      | Daily       |
	      | Weekly      |

    @admin	      
	Scenario: Sorting by title
		When I sort events by "title" "ascending"
		Then I should see the following in the repeated ".wp-list-table tr .column-title strong" element
	      | text        |
	      | Daily       |
	      | Monthly 1   |
	      | Monthly 2   |
	      | Single      |
	      | Weekly      |
	      | Yearly      |
	      | Yearly Past |

    @admin
	Scenario: Filtering by interval
		When I go to "/wp-admin/edit.php?post_type=event"
		And I select "Expired events" from "show-events-in-interval"
		And I press "Filter"
		Then the post list table looks like
	      | Event       | Comments     | Venue | Categories | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Yearly Past | —No comments |       |            | Jan, 1 2012         | Jan, 1 2012          | every year until Jan, 1st 2015                        |
		When I select "Future events" from "show-events-in-interval"
		And I press "Filter"
		Then the post list table looks like
	      | Event       | Comments     | Venue       | Categories        | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Yearly      | —No comments |             | Children’s        | Jan, 1 2112         | Jan, 1 2112          | every year until Jan, 1st 2115                        |
	      | Monthly 2   | —No comments | Akva        | Food & Drink      | Jan, 15 21158:45 am | Jan, 15 211511:00 am | every month on the third Tuesday until Dec, 17th 2115 |
	      | Monthly 1   | —No comments |             |                   | Jan, 15 21159:45 am | Jan, 15 211510:00 am | every month on the 15th until Dec, 15th 2115          |
	      | Single      | —No comments | Living Room | Music             | Apr, 30 21151:00 pm | Apr, 30 21152:00 pm  | one time only                                         |
	      | Daily       | —No comments | Dragonfly   |                   | May, 1 2115         | May, 1 2115          | every day until May, 15th 2115                        |
	      | Weekly      | —No comments | Akva        | Literature, Music | Jun, 1 21157:30 pm  | Jun, 1 21159:30 pm   | every 2 weeks on Saturday until Jun, 29th 2115        |
	      		
	@admin @event-category
	Scenario: Filtering by category
		When I go to "/wp-admin/edit.php?post_type=event"
		And I select "Music" from "event-category"
		And I press "Filter"
		Then the post list table looks like
	      | Event       | Comments     | Venue       | Categories        | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Single      | —No comments | Living Room | Music             | Apr, 30 21151:00 pm | Apr, 30 21152:00 pm  | one time only                                         |
	      | Weekly      | —No comments | Akva        | Literature, Music | Jun, 1 21157:30 pm  | Jun, 1 21159:30 pm   | every 2 weeks on Saturday until Jun, 29th 2115        |
		When I select "Literature" from "event-category"
		And I press "Filter"
		Then the post list table looks like
	      | Event       | Comments     | Venue | Categories        | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Weekly      | —No comments | Akva  | Literature, Music | Jun, 1 21157:30 pm  | Jun, 1 21159:30 pm   | every 2 weeks on Saturday until Jun, 29th 2115        |
	    When I follow "Music"  
		Then the post list table looks like
	      | Event       | Comments     | Venue       | Categories        | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Single      | —No comments | Living Room | Music             | Apr, 30 21151:00 pm | Apr, 30 21152:00 pm  | one time only                                         |
	      | Weekly      | —No comments | Akva        | Literature, Music | Jun, 1 21157:30 pm  | Jun, 1 21159:30 pm   | every 2 weeks on Saturday until Jun, 29th 2115        |
	
    @admin @event-venue
	Scenario: Filtering by venue
		When I go to "/wp-admin/edit.php?post_type=event"
		And I select "Akva" from "event-venue"
		And I press "Filter"
		Then the post list table looks like
	      | Event       | Comments     | Venue | Categories        | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Monthly 2   | —No comments | Akva  | Food & Drink      | Jan, 15 21158:45 am | Jan, 15 211511:00 am | every month on the third Tuesday until Dec, 17th 2115 |
	      | Weekly      | —No comments | Akva  | Literature, Music | Jun, 1 21157:30 pm  | Jun, 1 21159:30 pm   | every 2 weeks on Saturday until Jun, 29th 2115        |
		When I select "Living Room" from "event-venue"
		And I press "Filter"
		Then the post list table looks like
	      | Event       | Comments     | Venue       | Categories        | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Single      | —No comments | Living Room | Music             | Apr, 30 21151:00 pm | Apr, 30 21152:00 pm  | one time only                                         |
	    When I go to "/wp-admin/edit.php?post_type=event"
	    And I follow "Dragonfly"  
		Then the post list table looks like
	      | Event       | Comments     | Venue     | Categories        | Start Date/Time     | End Date/Time        | Recurrence                                            |
	      | Daily       | —No comments | Dragonfly |                   | May, 1 2115         | May, 1 2115          | every day until May, 15th 2115                        |