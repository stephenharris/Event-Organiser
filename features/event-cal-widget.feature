Feature: Event Calendar Widget
    In order to manage events
    As a user
    I need to be able to see the events in a calendar widget on the frontend

    Background:
        Given I have a vanilla wordpress installation
            | name          | email                   | username | password |
            | BDD WordPress | test.user@wordpress.dev | admin    | test     |

        And there are plugins
            | plugin                              | status  |
            | event-organiser/event-organiser.php | enabled |

		And there are events
            | post_title   | start        | end          | post_status | all_day | schedule | until        |
            | Weekly       | Y-m-02 19:30 | Y-m-02 21:30 | publish     | 0       | weekly   | Y-m-23 19:30 |
            | Single       | Y-m-16 09:45 | Y-m-16 11:00 | publish     | 0       | once     | Y-m-16 09:45 |
            | Daily        | Y-m-15       | Y-m-15       | publish     | 1       | daily    | Y-m-17       |

		And there are "event-category" terms
            | name         | description | slug | parent |
            | Music        |             |      |        |

		And there are venues
            | name        | address            | city      | postcode | country        | description            |
            | Akva        | 129 Fountainbridge | Edinburgh | EH3 9QG  | United Kingdom | Swedish bar & cafe     |

        And the event "Weekly" has event-category terms Music
        And the event "Single" has event-category terms Music

        And the event "Single" has event-venue terms Akva
        And the event "Daily" has event-venue terms Akva

        And I include past events


    Scenario: An event calendar widget showing all events
        Given I have an event calendar widget in "Main Sidebar"
            | Title      | Include past events | Event categories | Event venue |
            | All Events | 1                   |                  |             |
        When I go to "/"
        Then I should see "All Events"
        When I follow "16"
        Then I should see "Weekly"
        And I should see "Single"
        And I should see "Daily"

    Scenario: An event calendar widget showing events at a venue
        Given I have an event calendar widget in "Main Sidebar"
            | Title          | Include past events | Event categories | Event venue |
            | Events at Akva | 1                   |                  | akva        |
        When I go to "/"
        Then I should see "Events at Akva"
        When I follow "16"
        Then I should see "Single"
        And I should see "Daily"
        And I should not see "Weekly"

    Scenario: An event calendar widget showing events in category
        Given I have an event calendar widget in "Main Sidebar"
            | Title        | Include past events | Event categories | Event venue |
            | Music Events | 1                   | music            |             |
        When I go to "/"
        Then I should see "Music Events"
        When I follow "16"
        Then I should see "Weekly"
        And I should see "Single"
        And I should not see "Daily"

    Scenario: An event calendar widget showing events at a venue of a particular category
        Given I have an event calendar widget in "Main Sidebar"
            | Title                | Include past events | Event categories | Event venue |
            | Music Events at Akva | 1                   | music            | akva        |
        When I go to "/"
        Then I should see "Music Events at Akva"
        When I follow "16"
        Then I should see "Single"
        And I should not see "Weekly"
        And I should not see "Daily"

