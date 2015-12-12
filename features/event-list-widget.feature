Feature: Event List Widget
    In order to manage events
    As a user
    I need to be able to see the events admin page in the dashboard

    Background:
        Given I have a vanilla wordpress installation
            | name          | email                   | username | password |
            | BDD WordPress | test.user@wordpress.dev | admin    | test     |

        And there are plugins
            | plugin                              | status  |
            | event-organiser/event-organiser.php | enabled |

		And there are events
            | post_title   | start            | end              | post_status | all_day | schedule | schedule_meta | frequency | until            |
            | Weekly       | 2115-02-07 19:30 | 2115-02-07 21:30 | publish     | 0       | weekly   |               | 1         | 2115-02-29 19:30 |
            | Monthly      | 2115-01-15 09:45 | 2115-01-15 11:00 | publish     | 0       | monthly  | BYMONTHDAY    | 1         | 2115-03-15 09:45 |
            | Yearly       | 2112-01-01       | 2112-01-01       | publish     | 1       | yearly   |               | 1         | 2114-01-01       |
            | Yearly Past  | 2012-01-01       | 2012-01-01       | publish     | 1       | yearly   |               | 1         | 2014-01-01       |
            
		And there are "event-category" terms
            | name         | description | slug | parent |
            | Music        |             |      |        |

		And there are venues
            | name        | address            | city      | postcode | country        | description            |
            | Akva        | 129 Fountainbridge | Edinburgh | EH3 9QG  | United Kingdom | Swedish bar & cafe     |

        And the event "Weekly" has event-category terms Music
        And the event "Monthly" has event-category terms Music 

        And the event "Weekly" has event-venue terms Akva
       

    Scenario: An event list widget showing future events
        Given I have an event list widget in "Main Sidebar" 
            | Title         | No. of events | Interval |
            | Future Events | 6             | future   |    
        When I go to "/"
        Then I should see "Future Events"
        And the Event List Widget should display
			| Yearly on January 1, 2112           |
            | Yearly on January 1, 2113           |
            | Yearly on January 1, 2114           |
            | Monthly on January 15, 2115 9:45 am |
            | Weekly on February 7, 2115 7:30 pm  |
            | Weekly on February 14, 2115 7:30 pm |

    Scenario: An event list widget showing past events
        Given I have an event list widget in "Main Sidebar" 
            | Title       | No. of events | Interval |
            | Past Events | 5             | past     |    
        When I go to "/"
        Then I should see "Past Events"
        And the Event List Widget should display
			| Yearly Past on January 1, 2012 |
            | Yearly Past on January 1, 2013 |
            | Yearly Past on January 1, 2014 |

    Scenario: An event list widget showing all events
        Given I have an event list widget in "Main Sidebar" 
            | Title      | No. of events | Interval |
            | All Events | 10            | all   |    
        When I go to "/"
        Then I should see "All Events"
        And the Event List Widget should display
			| Yearly Past on January 1, 2012       |
            | Yearly Past on January 1, 2013       |
            | Yearly Past on January 1, 2014       |
			| Yearly on January 1, 2112            |
            | Yearly on January 1, 2113            |
            | Yearly on January 1, 2114            |
            | Monthly on January 15, 2115 9:45 am  |
            | Weekly on February 7, 2115 7:30 pm   |
            | Weekly on February 14, 2115 7:30 pm  |
            | Monthly on February 15, 2115 9:45 am |

	Scenario: An event list widget showing events grouped by series
        Given I have an event list widget in "Main Sidebar" 
            | Title          | No. of events | Interval | Group events |
            | Grouped Events | 10            | all      | series       |
        When I go to "/"
        Then I should see "Grouped Events"
        And the Event List Widget should display
			| Yearly Past on January 1, 2012       |
			| Yearly on January 1, 2112            |
            | Monthly on January 15, 2115 9:45 am  |
            | Weekly on February 7, 2115 7:30 pm   |
            
	Scenario: An event list widget showing music events
        Given I have an event list widget in "Main Sidebar" 
            | Title        | No. of events | Interval | Category |
            | Music Events | 10            | all      | Music    |
        When I go to "/"
        Then I should see "Music Events"
        And the Event List Widget should display
            | Monthly on January 15, 2115 9:45 am  |
            | Weekly on February 7, 2115 7:30 pm   |
            | Weekly on February 14, 2115 7:30 pm  |
            | Monthly on February 15, 2115 9:45 am |
            | Weekly on February 21, 2115 7:30 pm  |
            | Weekly on February 28, 2115 7:30 pm  |
            | Monthly on March 15, 2115 9:45 am    |
            
	Scenario: An event list widget showing events at Akva
        Given I have an event list widget in "Main Sidebar" 
            | Title          | No. of events | Interval | Venue |
            | Events at Akva | 10            | all      | Akva  |
        When I go to "/"
        Then I should see "Events at Akva"
        And the Event List Widget should display
            | Weekly on February 7, 2115 7:30 pm   |
            | Weekly on February 14, 2115 7:30 pm  |
            | Weekly on February 21, 2115 7:30 pm  |
            | Weekly on February 28, 2115 7:30 pm  |

	Scenario: An event list widget showing events at Akva
        Given I have an event list widget in "Main Sidebar" 
            | Title          | Venue | Template                                                                            |
            | Events at Akva | Akva  | %start{jS}% @%event_venue%, %event_venue_city% for %event_duration{%h hours}% |
        When I go to "/"
        Then I should see "Events at Akva"
        And the Event List Widget should display
            | 7th @Akva, Edinburgh for 2 hours |
			| 14th @Akva, Edinburgh for 2 hours |
            | 21st @Akva, Edinburgh for 2 hours |
			| 28th @Akva, Edinburgh for 2 hours |
            
